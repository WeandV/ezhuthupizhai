<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Razorpay\Api\Api;

class Checkout extends CI_Controller
{
    private $api;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Order_model');
        $this->load->model('User_model');
        $this->load->config('razorpay');
        $this->load->model('Payment_model');
        $this->load->library(['form_validation', 'session', 'email']);
        $this->load->helper(['url', 'form']);

        require_once APPPATH . '../vendor/autoload.php';
        $this->config->load('email', TRUE);
        $this->clientId = $this->config->item('gmail_client_id', 'email');
        $this->clientSecret = $this->config->item('gmail_client_secret', 'email');
        $this->refreshToken = $this->config->item('gmail_refresh_token', 'email');


        $this->api = new Api(
            $this->config->item('razorpay_key_id'),
            $this->config->item('razorpay_key_secret')
        );

        header('Access-Control-Allow-Origin: http://localhost:4200');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');

        if ($this->input->method() === 'options') {
            $this->output->set_status_header(200);
            exit();
        }
    }

    public function place_order_cod()
    {
        $input = json_decode($this->input->raw_input_stream, true);

        if (empty($input)) {
            $this->output->set_status_header(400)->set_output(json_encode(['success' => false, 'message' => 'No data provided.']));
            return;
        }

        $user_auth_context = $input['user_auth_context'] ?? [];
        $shippingDetails = $input['shipping_details'] ?? [];
        $orderSummary = $input['order_summary'] ?? [];
        $cartItems = $input['cart_items'] ?? [];
        $agreedToTerms = $input['agreed_to_terms'] ?? false;

        // Server-Side Validation
        if (empty($cartItems) || !$agreedToTerms) {
            $this->output->set_status_header(400)->set_output(json_encode(['success' => false, 'message' => 'Validation failed. Please check your cart and terms agreement.']));
            return;
        }

        $this->db->trans_begin();

        try {
            $user_id = $this->handleUserAndAddressCreation($user_auth_context, $shippingDetails);
            if ($user_id === false) {
                throw new Exception('Failed to identify or create user.');
            }

            // --- CHANGE MADE HERE ---
            // Set the initial status to 'processing' for COD orders, as payment is yet to be made.
            $orderData = [
                'user_id' => $user_id,
                'first_name' => $shippingDetails['firstName'],
                'last_name' => $shippingDetails['lastName'] ?? null,
                'email' => $shippingDetails['email'],
                'phone' => $shippingDetails['phone'],
                'address1' => $shippingDetails['address1'],
                'address2' => $shippingDetails['address2'] ?? null,
                'city' => $shippingDetails['city'],
                'state' => $shippingDetails['state'],
                'zip_code' => $shippingDetails['zipCode'],
                'country' => $shippingDetails['country'],
                'order_notes' => $shippingDetails['orderNotes'] ?? null,
                'payment_method' => 'COD(Not Paid)',
                'subtotal' => $orderSummary['subtotal'],
                'coupon_discount' => $orderSummary['coupon_discount'],
                'subtotal_after_discount' => $orderSummary['subtotal_after_discount'],
                'delivery_charge' => $orderSummary['delivery_charge'],
                'final_total' => $orderSummary['final_total'],
                'status' => 'processing',
            ];
            $order_id = $this->Order_model->insert_order($orderData);
            if (!$order_id) {
                throw new Exception('Failed to save order to database.');
            }

            $orderItemsToInsert = $this->prepareOrderItems($cartItems, $order_id);
            if (!$this->Order_model->insert_order_items($orderItemsToInsert)) {
                throw new Exception('Failed to save order items.');
            }
            $this->updateInventory($orderItemsToInsert);

            $this->db->trans_commit();

            // --- THIS IS THE CORRECTED CODE ---
            // Get the complete order details from the database after a successful transaction
            $completeOrderData = $this->Order_model->get_order_by_id($order_id);
            $orderItems = $this->Order_model->get_order_items($order_id);

            // Send email AFTER the transaction is committed
            $this->_sendOrderConfirmationEmail($order_id, $completeOrderData, $orderItems);
            // --- END CORRECTED CODE ---

            $this->output->set_content_type('application/json')->set_status_header(201);
            echo json_encode(['success' => true, 'message' => 'Order placed successfully!', 'order_id' => $order_id]);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'COD Order placement failed: ' . $e->getMessage());
            $this->output->set_content_type('application/json')->set_status_header(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    public function place_order_razorpay()
    {
        $input = json_decode($this->input->raw_input_stream, true);

        if (empty($input)) {
            $this->output->set_status_header(400)->set_output(json_encode(['success' => false, 'message' => 'No data provided.']));
            return;
        }

        $orderSummary = $input['order_summary'] ?? [];

        if (empty($orderSummary) || !isset($orderSummary['final_total'])) {
            $this->output->set_status_header(400)->set_output(json_encode(['success' => false, 'message' => 'Missing order summary or final total.']));
            return;
        }

        try {
            $amount_in_paise = intval($orderSummary['final_total'] * 100);
            $razorpayOrderData = [
                'amount' => $amount_in_paise,
                'currency' => $this->config->item('razorpay_currency'),
                'payment_capture' => 1
            ];

            $razorpayOrder = $this->api->order->create($razorpayOrderData);

            $this->output->set_content_type('application/json')->set_status_header(201);
            echo json_encode([
                'success' => true,
                'message' => 'Razorpay order created successfully.',
                'razorpayOrderId' => $razorpayOrder['id'],
                'key' => $this->config->item('razorpay_key_id'),
                'amount' => $amount_in_paise,
                'currency' => $this->config->item('razorpay_currency'),
                'name' => "Ezhuthupizhai",
                'description' => "Payment for your order",
                'prefill' => [
                    'name' => $input['shipping_details']['firstName'] . ' ' . ($input['shipping_details']['lastName'] ?? ''),
                    'email' => $input['shipping_details']['email'],
                    'contact' => $input['shipping_details']['phone'],
                ]
            ]);
        } catch (Exception $e) {
            log_message('error', 'Razorpay Order Creation Failed: ' . $e->getMessage());
            $this->output->set_content_type('application/json')->set_status_header(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    public function verify_payment()
    {
        $input = json_decode($this->input->raw_input_stream, true);
        $this->output->set_content_type('application/json');

        if (!empty($input['razorpay_payment_id']) && !empty($input['razorpay_order_id']) && !empty($input['razorpay_signature'])) {
            $attributes = [
                'razorpay_order_id' => $input['razorpay_order_id'],
                'razorpay_payment_id' => $input['razorpay_payment_id'],
                'razorpay_signature' => $input['razorpay_signature']
            ];

            $this->db->trans_begin();

            try {
                // Step 1: Verify the payment signature with Razorpay's API.
                // This is the most crucial step for security.
                $this->api->utility->verifyPaymentSignature($attributes);

                // Step 2: Extract all order data from the frontend payload.
                // This data is sent by your Angular app's payment handler.
                $user_auth_context = $input['user_auth_context'] ?? [];
                $shippingDetails = $input['shipping_details'] ?? [];
                $orderSummary = $input['order_summary'] ?? [];
                $cartItems = $input['cart_items'] ?? [];

                // Step 3: Handle user and address creation/lookup.
                $user_id = $this->handleUserAndAddressCreation($user_auth_context, $shippingDetails);
                if ($user_id === false) {
                    throw new Exception('Failed to identify or create user.');
                }

                // Step 4: Create the new order in the database.
                // This is the correct place to save the order and order items.
                // --- NOTE ON ORDER STATUS ---
                // For Razorpay, the payment has been successfully verified, so the status should be 'paid'.
                // Setting it to 'processing' could lead to security issues if the payment isn't handled correctly.
                $orderData = [
                    'user_id' => $user_id,
                    'first_name' => $shippingDetails['firstName'],
                    'last_name' => $shippingDetails['lastName'] ?? null,
                    'email' => $shippingDetails['email'],
                    'phone' => $shippingDetails['phone'],
                    'address1' => $shippingDetails['address1'],
                    'address2' => $shippingDetails['address2'] ?? null,
                    'city' => $shippingDetails['city'],
                    'state' => $shippingDetails['state'],
                    'zip_code' => $shippingDetails['zipCode'],
                    'country' => $shippingDetails['country'],
                    'order_notes' => $shippingDetails['orderNotes'] ?? null,
                    'payment_method' => 'Razorpay(Paid)',
                    'subtotal' => $orderSummary['subtotal'],
                    'coupon_discount' => $orderSummary['coupon_discount'],
                    'subtotal_after_discount' => $orderSummary['subtotal_after_discount'],
                    'delivery_charge' => $orderSummary['delivery_charge'],
                    'final_total' => $orderSummary['final_total'],
                    'status' => 'processing',
                ];
                $order_id = $this->Order_model->insert_order($orderData);
                if (!$order_id) {
                    throw new Exception('Failed to save order to database.');
                }

                // Step 5: Save the order items and update inventory.
                $orderItemsToInsert = $this->prepareOrderItems($cartItems, $order_id);
                if (empty($orderItemsToInsert) || !$this->Order_model->insert_order_items($orderItemsToInsert)) {
                    throw new Exception('No valid cart items or failed to save order items.');
                }
                $this->updateInventory($orderItemsToInsert);

                // Step 6: Create a permanent payment record linking to the new order.
                $this->Payment_model->create_order([
                    'order_id' => $order_id,
                    'razorpay_order_id' => $input['razorpay_order_id'],
                    'razorpay_payment_id' => $input['razorpay_payment_id'],
                    'razorpay_signature' => $input['razorpay_signature'],
                    'amount' => $orderSummary['final_total'],
                    'currency' => $this->config->item('razorpay_currency'),
                    'status' => 'paid',
                ]);

                // Step 7: Commit the transaction after all database operations are successful.
                $this->db->trans_commit();

                // Step 8: Get the complete order details for the email.
                $order_details = $this->Order_model->get_order_by_id($order_id);
                $order_items = $this->Order_model->get_order_items($order_id);

                // Step 9: Send the email.
                $this->_sendOrderConfirmationEmail($order_id, $order_details, $order_items);

                // Step 10: Send a success response back to the frontend.
                $this->output->set_status_header(200);
                echo json_encode(['success' => true, 'message' => 'Payment verified and order created successfully.', 'order_id' => $order_id]);
            } catch (Exception $e) {
                $this->db->trans_rollback();
                log_message('error', 'Razorpay Verify Error: ' . $e->getMessage());
                $this->output->set_status_header(400);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'Invalid payment data provided.']);
        }
    }
    private function _sendOrderConfirmationEmail($order_id, $order_details, $order_items)
    {
        // Require the Google API Client library
        require_once APPPATH . '../vendor/autoload.php';

        // Get access token using the working function you already have
        $accessToken = $this->refreshAccessToken();

        if (!$accessToken) {
            log_message('error', 'Order Confirmation Email Error: Failed to get Google API access token for order #' . $order_id);
            return false;
        }

        // Initialize the Gmail API client
        $client = new Google_Client();
        $client->setAccessToken($accessToken);
        $service = new Google_Service_Gmail($client);

        try {
            // Get email details from config
            $from_email = $this->config->item('gmail_email');
            $from_name = $this->config->item('from_name');
            $to_email = $order_details['email'];

            // Prepare data for the view file
            $data = [
                'order_id' => $order_id,
                'order_details' => $order_details,
                'order_items' => $order_items,
                'from_email' => $from_email,
            ];

            // Load the view file and get the rendered HTML as a string
            $email_body_html = $this->load->view('emails/order_confirmation', $data, true);

            // Create a plain text version by stripping tags
            $email_body_text = strip_tags(str_replace(['<br>', '<br/>'], "\n", $email_body_html));
            $email_body_text = preg_replace("/\r?\n\r?\n/", "\n\n", $email_body_text);

            // Generate a unique boundary string
            $boundary = uniqid('', true);

            // Construct the full multipart/alternative raw message
            $rawMessage = "From: {$from_name} <{$from_email}>\r\n";
            $rawMessage .= "To: <{$to_email}>\r\n";
            $rawMessage .= "Subject: Order Confirmation #" . $order_id . "\r\n";
            $rawMessage .= "MIME-Version: 1.0\r\n";
            $rawMessage .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";

            // Plain text part
            $rawMessage .= "--$boundary\r\n";
            $rawMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $rawMessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $rawMessage .= $email_body_text . "\r\n\r\n";

            // HTML part
            $rawMessage .= "--$boundary\r\n";
            $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
            $rawMessage .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
            $rawMessage .= quoted_printable_encode($email_body_html) . "\r\n\r\n";

            // End boundary
            $rawMessage .= "--$boundary--\r\n";

            // Encode the message and send it
            $mime = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');
            $message = new Google_Service_Gmail_Message();
            $message->setRaw($mime);
            $sentMessage = $service->users_messages->send('me', $message);

            log_message('info', 'Order confirmation email sent to ' . $to_email . ' for order #' . $order_id . '. Message ID: ' . $sentMessage->getId());
            return true;
        } catch (Google_Service_Exception $e) {
            $error_message = 'Error sending order confirmation via Gmail API: ' . $e->getMessage();
            log_message('error', 'Order Email Error (Google Service): ' . $error_message);
            return false;
        } catch (Exception $e) {
            $error_message = 'Error sending order confirmation (general): ' . $e->getMessage();
            log_message('error', 'Order Email Error (General): ' . $error_message);
            return false;
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


    private function handleUserAndAddressCreation($user_auth_context, $shippingDetails)
    {
        $user_id_from_auth_context = $user_auth_context['user_id'] ?? null;
        $email_from_auth_context = $user_auth_context['email'] ?? null;
        $otp_verified = $user_auth_context['otp_verified'] ?? false;
        $is_address_from_saved_selection = $shippingDetails['is_address_from_saved'] ?? false;
        $address_id_from_frontend = $shippingDetails['address_id'] ?? null;
        $create_account_flag_from_form = $shippingDetails['createAccount'] ?? false;

        $user_id = null;

        $common_user_data_from_shipping = [
            'first_name' => $shippingDetails['firstName'] ?? null,
            'last_name'  => $shippingDetails['lastName'] ?? null,
            'phone'      => $shippingDetails['phone'] ?? null
        ];

        if ($user_id_from_auth_context !== null) {
            $user_id = $user_id_from_auth_context;
        } elseif ($this->session->userdata('user_id')) {
            $user_id = $this->session->userdata('user_id');
        } elseif ($otp_verified && !empty($email_from_auth_context)) {
            $result = $this->User_model->create_user_if_not_exists($email_from_auth_context, $common_user_data_from_shipping);
            if (is_array($result) && isset($result['status']) && $result['status'] === 'error') {
                throw new Exception($result['message']);
            }
            if ($result === false || is_null($result)) {
                throw new Exception('Failed to identify or create user account after OTP verification.');
            }
            $user_id = $result;
        } elseif (!empty($shippingDetails['email'])) {
            $user_from_shipping_email = $this->User_model->get_user_by_identifier($shippingDetails['email']);

            if ($user_from_shipping_email) {
                $user_id = $user_from_shipping_email['id'];
                $update_result = $this->User_model->update_user($user_id, $common_user_data_from_shipping);
                if ($update_result === false) {
                    throw new Exception('Failed to update user details: phone number may be in use.');
                }
            } elseif ($create_account_flag_from_form) {
                $result = $this->User_model->create_user_if_not_exists($shippingDetails['email'], $common_user_data_from_shipping);
                if (is_array($result) && isset($result['status']) && $result['status'] === 'error') {
                    throw new Exception($result['message']);
                }
                if ($result === false || is_null($result)) {
                    throw new Exception('Failed to create new user account from checkout form.');
                }
                $user_id = $result;
            }
        }

        if ($user_id !== null) {
            $address_to_save_update = [
                'user_id'    => $user_id,
                'first_name' => $shippingDetails['firstName'],
                'last_name'  => $shippingDetails['lastName'] ?? null,
                'phone'      => $shippingDetails['phone'],
                'email'      => $shippingDetails['email'],
                'address1'   => $shippingDetails['address1'],
                'address2'   => $shippingDetails['address2'] ?? null,
                'city'       => $shippingDetails['city'],
                'state'      => $shippingDetails['state'],
                'zip_code'   => $shippingDetails['zipCode'],
                'country'    => $shippingDetails['country'],
                'type'       => 'shipping',
                'is_default_billing' => 0,
                'is_default_shipping' => 1,
                'is_active'  => 1
            ];

            if ($is_address_from_saved_selection && $address_id_from_frontend) {
                // Do nothing, address is already saved and selected
            } else {
                if ($address_id_from_frontend) {
                    $this->User_model->update_user_address($address_id_from_frontend, $user_id, $address_to_save_update);
                } else {
                    $saved_address_id = $this->User_model->save_user_address($address_to_save_update);
                    if (!$saved_address_id) {
                        throw new Exception('Failed to save new shipping address.');
                    }
                }
            }
        }

        return $user_id;
    }

    // Private helper method to prepare order items for database insertion
    private function prepareOrderItems($cartItems, $order_id)
    {
        $orderItemsToInsert = [];
        foreach ($cartItems as $item) {
            $productId = $item['product_id'] ?? null;
            $productName = $item['product_name'] ?? 'Unknown Product';
            $priceAtOrder = floatval($item['price_at_order'] ?? 0.00);
            $quantity = intval($item['quantity'] ?? 1);
            $byobItemsList = $item['byob_items_list'] ?? null;

            if ($quantity <= 0 || $priceAtOrder < 0) {
                continue;
            }

            $orderItemsToInsert[] = [
                'order_id'       => $order_id,
                'product_id'     => $productId,
                'product_name'   => $productName,
                'quantity'       => $quantity,
                'price_at_order' => $priceAtOrder,
                'total'          => $quantity * $priceAtOrder,
                'byob_items_list' => $byobItemsList,
            ];
        }
        return $orderItemsToInsert;
    }

    // Private helper method to update inventory stock quantities
    private function updateInventory($orderItemsToInsert)
    {
        foreach ($orderItemsToInsert as $item) {
            $productId = $item['product_id'];
            $orderQuantity = $item['quantity'];

            $product_query = $this->db->get_where('products', ['id' => $productId]);
            $product = $product_query->row();

            if (!$product) {
                throw new Exception('Product not found for ID: ' . $productId);
            }

            if ($product->type === 'single') {
                $this->db->set('stock_quantity', 'stock_quantity - ' . $orderQuantity, FALSE);
                $this->db->where('item_id', $productId);
                $result = $this->db->update('inventory');

                if (!$result || $this->db->affected_rows() === 0) {
                    throw new Exception('Failed to update stock for single item ID: ' . $productId);
                }
            } elseif ($product->type === 'combo' || $product->type === 'giftbox') {
                $components_query = $this->db->get_where('product_components', ['product_id' => $productId]);
                $components = $components_query->result();

                if (empty($components)) {
                    throw new Exception('Combo or giftbox has no defined components. Product ID: ' . $productId);
                }

                foreach ($components as $component) {
                    $item_id = $component->item_id;
                    $component_quantity = $component->quantity;
                    $total_quantity_to_decrement = $orderQuantity * $component_quantity;

                    $this->db->set('stock_quantity', 'stock_quantity - ' . $total_quantity_to_decrement, FALSE);
                    $this->db->where('item_id', $item_id);
                    $result = $this->db->update('inventory');

                    if (!$result || $this->db->affected_rows() === 0) {
                        throw new Exception('Failed to update stock for component ID: ' . $item_id);
                    }
                }
            }
        }
    }

    public function get_order_details($order_id = null)
    {
        $this->output->set_content_type('application/json');

        if (empty($order_id) || !is_numeric($order_id)) {
            $this->output->set_status_header(400)->set_output(json_encode(['success' => false, 'message' => 'Invalid or missing Order ID.']));
            return;
        }

        $order = $this->Order_model->get_order_by_id($order_id);

        if (!$order) {
            $this->output->set_status_header(404)->set_output(json_encode(['success' => false, 'message' => 'Order not found.']));
            return;
        }

        $order_items = $this->Order_model->get_order_items($order_id);

        $response_data = [
            'success' => true,
            'message' => 'Order details fetched successfully.',
            'order_details' => [
                'id'                     => $order['id'],
                'first_name'             => $order['first_name'],
                'last_name'              => $order['last_name'],
                'email'                  => $order['email'],
                'phone'                  => $order['phone'],
                'address1'               => $order['address1'],
                'address2'               => $order['address2'],
                'city'                   => $order['city'],
                'state'                  => $order['state'],
                'zip_code'               => $order['zip_code'],
                'country'                => $order['country'],
                'order_notes'            => $order['order_notes'],
                'payment_method'         => $order['payment_method'],
                'subtotal'               => floatval($order['subtotal']),
                'coupon_discount'        => floatval($order['coupon_discount']),
                'subtotal_after_discount' => floatval($order['subtotal_after_discount']),
                'delivery_charge'        => floatval($order['delivery_charge']),
                'final_total'            => floatval($order['final_total']),
                'status'                 => $order['status'],
                'created_at'             => $order['created_at'],
                'order_items'            => $order_items,
            ]
        ];

        $this->output->set_status_header(200)->set_output(json_encode($response_data));
    }
}
