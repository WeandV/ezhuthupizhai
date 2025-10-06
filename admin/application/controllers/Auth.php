<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

class Auth extends CI_Controller
{
    protected $gmailEmail;
    protected $clientId;
    protected $clientSecret;
    protected $refreshToken;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->model('Order_model');
        $this->load->library('form_validation');
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->config('email');
        $this->load->config('config');

        $this->gmailEmail = $this->config->item('gmail_email');
        $this->clientId = $this->config->item('gmail_client_id');
        $this->clientSecret = $this->config->item('gmail_client_secret');
        $this->refreshToken = $this->config->item('gmail_refresh_token');

        header('Content-Type: application/json');
        header("Access-Control-Allow-Origin: http://localhost:4200");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");
        header("Access-Control-Max-Age: 86400");
        header("Access-Control-Allow-Credentials: true");

        if ($this->input->method() === 'options') {
            http_response_code(200);
            exit();
        }
    }

    private function refreshAccessToken()
    {
        $client = new GuzzleHttp\Client();
        try {
            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token',
                ],
                'verify' => FALSE
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['access_token'] ?? null;
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $error_details = $e->getMessage();
            if ($e->hasResponse()) {
                $error_details .= ' Response: ' . $e->getResponse()->getBody()->getContents();
            }
            log_message('error', 'Guzzle Request Exception refreshing token: ' . $error_details);
            return null;
        } catch (Exception $e) {
            log_message('error', 'General Exception refreshing token: ' . $e->getMessage());
            return null;
        }
    }

    public function send_otp()
    {
        $input_data = json_decode($this->input->raw_input_stream, true);
        $email = $input_data['email'] ?? null;
        $phone = $input_data['phone'] ?? null;

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->output->set_status_header(400)->set_output(json_encode(['success' => false, 'message' => 'A valid email address is required.']));
            return;
        }

        $otp = rand(100000, 999999);
        $otp_validity_minutes = 5;

        $save_otp_success = $this->User_model->save_otp($email, $otp, $phone, $otp_validity_minutes);

        if (!$save_otp_success) {
            $this->output->set_status_header(500)->set_output(json_encode(['success' => false, 'message' => 'Failed to save OTP to database. Please try again.']));
            log_message('error', 'SEND_OTP: Failed to save OTP to database for email: ' . $email);
            return;
        }

        $accessToken = $this->refreshAccessToken();

        if (!$accessToken) {
            $this->output->set_status_header(500)->set_output(json_encode(['success' => false, 'message' => 'Error: Could not obtain Google API access token.']));
            log_message('error', 'OTP Email Error: Failed to get Google API access token for email: ' . $email);
            return;
        }

        $client = new Google_Client();
        $client->setAccessToken($accessToken);
        $service = new Google_Service_Gmail($client);

        try {
            $from_email = $this->config->item('from_email');
            $from_name = $this->config->item('from_name');
            $subject_template = $this->config->item('otp_email_subject_template');
            $body_template = $this->config->item('otp_email_body_template');

            $app_name = $from_name;
            $subject = str_replace('{APP_NAME}', $app_name, $subject_template);
            $email_message_html = str_replace(
                ['{OTP_CODE}', '{OTP_VALIDITY_MINUTES}', '{APP_NAME}'],
                [$otp, $otp_validity_minutes, $app_name],
                $body_template
            );

            $message = new Google_Service_Gmail_Message();
            $rawMessage = "From: {$from_name} <{$from_email}>\r\n";
            $rawMessage .= "To: <{$email}>\r\n";
            $rawMessage .= "Subject: {$subject}\r\n";
            $rawMessage .= "MIME-Version: 1.0\r\n";
            $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
            $rawMessage .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
            $rawMessage .= $email_message_html;

            $mime = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');
            $message->setRaw($mime);

            $sentMessage = $service->users_messages->send('me', $message);

            $this->output->set_status_header(200)->set_output(json_encode(['success' => true, 'message' => 'OTP sent successfully to ' . $email . '.']));
            log_message('info', 'OTP sent: ' . $otp . ' to ' . $email . ' via Gmail API. Message ID: ' . $sentMessage->getId());
        } catch (Google_Service_Exception $e) {
            $error_message = 'Error sending OTP via Gmail API: ' . $e->getMessage();
            log_message('error', 'OTP Email Error (Google Service): ' . $error_message);
            $this->output->set_status_header(500)->set_output(json_encode(['success' => false, 'message' => $error_message]));
        } catch (Exception $e) {
            $error_message = 'Error sending OTP (general): ' . $e->getMessage();
            log_message('error', 'OTP Email Error (General): ' . $error_message);
            $this->output->set_status_header(500)->set_output(json_encode(['success' => false, 'message' => $error_message]));
        }
    }

    public function verify_otp_and_get_addresses()
    {
        $input_data = json_decode($this->input->raw_input_stream, true);
        $email = $input_data['email'] ?? null;
        $entered_otp = $input_data['otp'] ?? null;

        $response_data = ['success' => false, 'message' => ''];
        $http_status = 400;

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response_data['message'] = 'Email is required for OTP verification.';
        } elseif (empty($entered_otp)) {
            $response_data['message'] = 'OTP not provided.';
        } else {
            $otp_record = $this->User_model->find_valid_otp($email, $entered_otp);

            if (!$otp_record) {
                $response_data['message'] = 'Invalid, expired, or already used OTP. Please request a new one.';
                $http_status = 401;
            } else {
                $mark_used_success = $this->User_model->mark_otp_as_used($otp_record['id']);
                if (!$mark_used_success) {
                    log_message('error', 'VERIFY_OTP_AND_GET_ADDRESSES: Failed to mark OTP as used for ID: ' . $otp_record['id']);
                }

                // Check if a user with this email already exists
                $user = $this->User_model->get_user_by_email($email);

                $user_id = null;
                $addresses = [];
                $message_suffix = '';

                if ($user) {
                    // User exists, get their details
                    $user_id = $user->id;
                    $addresses = $this->User_model->get_user_addresses($user_id);
                    $message_suffix = ' Account found.';
                } else {
                    // No user exists yet. The account will be created later.
                    $message_suffix = ' New user account will be created upon first order.';
                }

                $response_data['success'] = true;
                $response_data['message'] = 'OTP verified successfully!' . $message_suffix;
                $response_data['user_id'] = $user_id;
                $response_data['email'] = $email;
                $response_data['phone'] = $otp_record['phone'];
                $response_data['addresses'] = $addresses;
                $http_status = 200;
            }
        }

        log_message('debug', 'VERIFY_OTP_AND_GET_ADDRESSES: Received email: ' . $email . ', OTP: ' . $entered_otp);
        $this->output->set_status_header($http_status)->set_output(json_encode($response_data));
    }

    public function get_addresses_by_user_id()
    {
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        $user_id = $data['user_id'] ?? null;

        if (empty($user_id) || !is_numeric($user_id) || $user_id <= 0) {
            $response = ['success' => false, 'message' => 'Invalid user ID provided.'];
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
            return;
        }

        $addresses = $this->User_model->get_user_addresses($user_id);

        if ($addresses !== null) {
            $response = ['success' => true, 'addresses' => $addresses];
        } else {
            $response = ['success' => false, 'message' => 'Failed to retrieve addresses.'];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function get_all()
    {
        $users = $this->User_model->get_all_users();

        if ($users) {
            $response = [
                'success' => true,
                'message' => 'Users retrieved successfully.',
                'data'    => $users
            ];
            $this->output->set_status_header(200);
        } else {
            $response = [
                'success' => false,
                'message' => 'No users found.',
                'data'    => []
            ];
            $this->output->set_status_header(404);
        }

        $this->output->set_output(json_encode($response));
    }

    public function login()
    {
        log_message('debug', 'Auth/login method called.');
        $json_data = file_get_contents('php://input');
        log_message('debug', 'Raw input stream from Angular: ' . $json_data);

        $data = json_decode($json_data, true);
        log_message('debug', 'Decoded JSON data: ' . print_r($data, true));

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required');

        if ($this->form_validation->run() == FALSE) {
            $response = [
                'status' => 'error',
                'message' => validation_errors()
            ];
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
            return;
        }

        $email = $data['email'];
        $password = $data['password'];

        $user = $this->User_model->get_user_by_email($email);

        if ($user && password_verify($password, $user->password)) {
            $response = [
                'status' => 'success',
                'message' => 'Login successful!',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name
                ]
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Invalid email or password.'
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function register()
    {

        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[users.email]');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
        $this->form_validation->set_rules('first_name', 'First Name', 'required');

        if ($this->form_validation->run() == FALSE) {
            $response = [
                'status' => 'error',
                'message' => validation_errors()
            ];
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
            return;
        }

        $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);

        $user_data = [
            'email' => $data['email'],
            'password' => $hashed_password,
            'first_name' => $data['first_name'],
            'last_name' => isset($data['last_name']) ? $data['last_name'] : null,
            'phone' => isset($data['phone']) ? $data['phone'] : null,
            'status' => 'active',
            'is_active' => 1
        ];

        if ($this->User_model->insert_user($user_data)) {
            $response = [
                'status' => 'success',
                'message' => 'Registration successful!'
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Registration failed. Please try again.'
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
    public function reset_password()
    {
        $input_data = json_decode($this->input->raw_input_stream, true);
        $email = $input_data['email'] ?? null;
        $entered_otp = $input_data['otp'] ?? null;
        $new_password = $input_data['new_password'] ?? null;

        $response_data = ['success' => false, 'message' => ''];
        $http_status = 400;

        // 1. Validate inputs
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response_data['message'] = 'A valid email address is required.';
        } elseif (empty($entered_otp)) {
            $response_data['message'] = 'OTP is required.';
        } elseif (empty($new_password) || strlen($new_password) < 6) { // Basic password length validation
            $response_data['message'] = 'New password is required and must be at least 6 characters long.';
        } else {
            // 2. Find and validate the OTP
            $otp_record = $this->User_model->find_valid_otp($email, $entered_otp);

            if (!$otp_record) {
                $response_data['message'] = 'Invalid, expired, or already used OTP.';
                $http_status = 401;
            } else {
                // 3. Get the user
                $user = $this->User_model->get_user_by_email($email);

                if (!$user) {
                    $response_data['message'] = 'User not found.';
                    $http_status = 404;
                } else {
                    // 4. Hash the new password
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                    // 5. Update the user's password
                    $update_success = $this->User_model->update_user($user->id, ['password' => $hashed_password]);

                    if ($update_success) {
                        // 6. Mark OTP as used
                        $this->User_model->mark_otp_as_used($otp_record['id']);
                        $response_data['success'] = true;
                        $response_data['message'] = 'Password has been reset successfully!';
                        $http_status = 200;
                    } else {
                        $response_data['message'] = 'Failed to update password. Please try again.';
                        $http_status = 500;
                        log_message('error', 'RESET_PASSWORD: Failed to update password for user ID: ' . $user->id);
                    }
                }
            }
        }

        $this->output->set_status_header($http_status)->set_output(json_encode($response_data));
    }
}
