<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_user_by_identifier($identifier)
    {
        $this->db->where('email', $identifier);
        $query = $this->db->get('users');
        return $query->row_array();
    }

    private function generate_random_password($length = 12)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()-_=+';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function hash_password($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    private function _is_phone_unique($phone, $exclude_user_id = null)
    {
        $this->db->where('phone', $phone);
        if ($exclude_user_id) {
            $this->db->where('id !=', $exclude_user_id);
        }
        $query = $this->db->get('users');
        return $query->num_rows() == 0;
    }

    public function create_user_if_not_exists($email, $additional_user_data = [])
    {
        $user = $this->get_user_by_identifier($email);
        if ($user) {
            if (!empty($additional_user_data)) {
                $result = $this->update_user($user['id'], $additional_user_data);
                if ($result === false) {
                    return ['status' => 'error', 'message' => 'The phone number is already registered to another account.'];
                }
            }
            return $user['id'];
        } else {
            $plain_password = $this->generate_random_password();
            $hashed_password = $this->hash_password($plain_password);

            $data = [
                'email'      => $email,
                'password'   => $hashed_password,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'status'     => 'active',
            ];

            if (isset($additional_user_data['first_name'])) {
                $data['first_name'] = $additional_user_data['first_name'];
            } else {
                $data['first_name'] = '';
            }
            if (isset($additional_user_data['last_name'])) {
                $data['last_name'] = $additional_user_data['last_name'];
            }
            if (isset($additional_user_data['phone'])) {
                if ($this->_is_phone_unique($additional_user_data['phone'])) {
                    $data['phone'] = $additional_user_data['phone'];
                } else {
                    return ['status' => 'error', 'message' => 'The phone number is already registered.'];
                }
            }

            $this->db->insert('users', $data);

            if ($this->db->affected_rows() > 0) {
                $new_user_id = $this->db->insert_id();
                return $new_user_id;
            }
            return FALSE;
        }
    }


    public function update_user($user_id, $data)
    {
        if (isset($data['phone'])) {
            if (!$this->_is_phone_unique($data['phone'], $user_id)) {
                log_message('error', 'Update failed: Attempted to use duplicate phone number ' . $data['phone'] . ' for user ID ' . $user_id);
                return false;
            }
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $user_id);

        // This is the key change: ensure we check the result of the update query
        $result = $this->db->update('users', $data);

        if ($result === FALSE) {
            log_message('error', 'Database Error updating user ID ' . $user_id . ': ' . $this->db->error()['message']);
            return false;
        }

        // Return true if the update was successful, even if no rows were affected
        return true;
    }

    public function save_user_address($address_data)
    {
        $address_data['created_at'] = date('Y-m-d H:i:s');
        $address_data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('user_addresses', $address_data);
        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }
        return FALSE;
    }

    public function get_user_addresses($user_id)
    {
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('user_addresses');
        return $query->result_array();
    }

    public function save_otp($email, $otp_code, $phone = null, $expiry_minutes = 5)
    {
        $expires_at = date('Y-m-d H:i:s', strtotime("+$expiry_minutes minutes"));

        $data = [
            'email'      => $email,
            'otp_code'   => $otp_code,
            'phone'      => $phone,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expires_at,
            'ip_address' => $this->input->ip_address(),
            'is_used'    => 0
        ];

        $this->db->where('email', $email);
        $this->db->where('is_used', 0);
        $this->db->where('expires_at >', date('Y-m-d H:i:s'));
        $this->db->delete('user_otp');

        return $this->db->insert('user_otp', $data);
    }

    public function find_valid_otp($email, $otp_code)
    {
        $this->db->where('email', $email);
        $this->db->where('otp_code', $otp_code);
        $this->db->where('is_used', 0);
        $this->db->where('expires_at >', date('Y-m-d H:i:s'));
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get('user_otp');
        return $query->row_array();
    }

    public function mark_otp_as_used($otp_id)
    {
        $this->db->where('id', $otp_id);
        return $this->db->update('user_otp', ['is_used' => 1]);
    }

    public function get_user_by_email($email)
    {
        $this->db->where('email', $email);
        $query = $this->db->get('users');
        return $query->row();
    }

    public function insert_user($data)
    {
        return $this->db->insert('users', $data);
    }

    public function get_user_by_id($user_id)
    {
        $this->db->where('id', $user_id);
        $query = $this->db->get('users');
        return $query->row_array();
    }

    public function get_all_users()
    {
        $this->db->select('u.id, u.email, u.first_name, u.last_name, u.phone');
        $this->db->from('users u');
        $this->db->where('u.is_active', 1);
        $query = $this->db->get();
        return $query->result();
    }
    public function get_user_details($user_id)
    {
        $this->db->select('u.id, u.email, u.first_name, u.last_name, u.phone, u.created_at, u.status');
        $this->db->from('users u');
        $this->db->where('u.id', $user_id);
        $user_query = $this->db->get();
        $user = $user_query->row();

        if ($user) {
            $this->db->select('*');
            $this->db->from('user_addresses');
            $this->db->where('user_id', $user_id);
            $this->db->where('is_active', 1);
            $addresses_query = $this->db->get();
            $user->addresses = $addresses_query->result();
        }

        return $user;
    }

    public function get_user_orders($user_id)
    {
        $this->db->select('*');
        $this->db->from('orders');
        $this->db->where('user_id', $user_id);
        $this->db->order_by('created_at', 'DESC');
        $orders_query = $this->db->get();
        $orders = $orders_query->result();

        if (!empty($orders)) {
            foreach ($orders as &$order) {
                $this->db->select('*');
                $this->db->from('order_items');
                $this->db->where('order_id', $order->id);
                $items_query = $this->db->get();
                $order->items = $items_query->result();
            }
        }

        return $orders;
    }

    public function get_user_details_by_id($user_id)
    {        $this->db->select('id, first_name, last_name, email, phone, password');
        $this->db->from('users');
        $this->db->where('id', $user_id);

        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->row_array();
        } else {
            return false;
        }
    }
}