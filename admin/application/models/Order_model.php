<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Order_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    public function insert_order($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->insert('orders', $data);

        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }
        return FALSE;
    }

    public function insert_order_items($data)
    {
        foreach ($data as &$item) {
            $item['created_at'] = date('Y-m-d H:i:s');
            $item['updated_at'] = date('Y-m-d H:i:s');

            if (!isset($item['byob_items_list'])) {
                $item['byob_items_list'] = null;
            }
        }

        $this->db->insert_batch('order_items', $data);

        return ($this->db->affected_rows() > 0);
    }


    public function get_order_by_id($order_id)
    {
        $this->db->where('id', $order_id);
        $query = $this->db->get('orders');

        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        return NULL;
    }

    public function get_order_items($order_id)
    {
        $this->db->where('order_id', $order_id);
        $query = $this->db->get('order_items');

        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return [];
    }
    public function get_orders_by_user($user_id, $status = null)
    {
        $this->db->where('user_id', $user_id);
        if ($status) {
            $this->db->where('status', $status);
        }
        $query = $this->db->get('orders');
        return $query->result_array();
    }

    public function get_orders_list()
    {
        $this->db->select('o.id, o.first_name, o.last_name, o.payment_method, o.status, o.final_total, o.created_at, GROUP_CONCAT(oi.product_name SEPARATOR ", ") AS product_list');
        $this->db->from('orders o');
        $this->db->join('order_items oi', 'oi.order_id = o.id', 'left');
        $this->db->group_by('o.id');
        $this->db->order_by('o.created_at', 'DESC');
        $query = $this->db->get();
        return $query->result();
    }

    public function get_order_with_items($order_id)
    {
        $this->db->where('id', $order_id);
        $order_details = $this->db->get('orders')->row();

        if ($order_details) {
            $this->db->where('order_id', $order_id);
            $order_items = $this->db->get('order_items')->result();

            $order_details->items = $order_items;
        }

        return $order_details;
    }

    public function get_orders_by_user_with_items($userId)
    {
        $this->db->select('o.id, o.created_at, o.status, o.final_total, o.status, o.payment_method');
        $this->db->from('orders o');
        $this->db->where('o.user_id', $userId);
        $this->db->order_by('o.created_at', 'DESC');
        $query = $this->db->get();

        $orders = $query->result_array();

        // Fetch order items for each order
        foreach ($orders as &$order) {
            $this->db->select('product_name, quantity, price_at_order');
            $this->db->where('order_id', $order['id']);
            $item_query = $this->db->get('order_items');
            $order['items'] = $item_query->result_array();
        }

        return $orders;
    }

    public function get_all_orders()
    {
        $this->db->select('id, first_name, last_name, payment_method, final_total, created_at, status');
        $query = $this->db->get('orders');
        return $query->result();
    }


    public function get_total_orders_by_user($userId)
    {
        $this->db->where('user_id', $userId);
        return $this->db->count_all_results('orders');
    }

    public function get_pending_orders_by_user($user_id)
    {
        $this->db->where('user_id', $user_id);
        // Based on your new data, only 'On Hold' is truly pending
        $this->db->where_in('status', ['On Hold']);
        return $this->db->count_all_results('orders');
    }

    public function get_dashboard_orders_for_user($user_id)
    {
        $this->db->select("
        id,
        created_at,
        final_total,
        status,
        payment_method,
        payment_status
    ");
        $this->db->from('orders');
        $this->db->where('user_id', $user_id);
        $this->db->order_by('created_at', 'DESC');

        $query = $this->db->get();
        return $query->result_array();
    }


    public function update_shiprocket_ids($order_id, $shiprocket_id, $awb_code)
    {
        $data = [
            'shiprocket_id' => $shiprocket_id,
            'awb_code' => $awb_code
        ];

        // The provided code assumes $this->db is a valid CodeIgniter DB object.
        // I will assume it is for this example.
        $this->db->where('id', $order_id);
        $updated = $this->db->update('orders', $data);

        if ($updated) {
            log_message('info', "Order {$order_id} updated with Shiprocket ID: {$shiprocket_id}, AWB: {$awb_code}");
        } else {
            // Check the last query to see if the WHERE clause found a match.
            log_message('error', "Failed to update Order {$order_id} in DB. Last query: " . $this->db->last_query());
        }

        return $updated;
    }

    public function get_order($order_id)
    {
        return $this->db->get_where('orders', ['id' => $order_id])->row_array();
    }

    public function update_awb($order_id, $awb_code)
    {
        $this->db->where('id', $order_id);
        return $this->db->update('orders', ['awb_code' => $awb_code]);
    }
}
