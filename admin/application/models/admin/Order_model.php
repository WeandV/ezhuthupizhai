<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Order_model extends CI_Model
{
    public function get_all_orders()
    {
        $this->db->select('id, invoice_id, first_name, last_name, payment_method, final_total, created_at, status');
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('orders');
        return $query->result();
    }

    public function get_order_with_items($order_id)
    {
        $this->db->where('id', $order_id);
        $order_details = $this->db->get('orders')->row();

        if ($order_details) {
            $this->db->select('oi.*, p.mrp_price, p.special_price, p.name as product_name');
            $this->db->from('order_items as oi');
            $this->db->join('products as p', 'oi.product_id = p.id', 'left');
            $this->db->where('oi.order_id', $order_id);
            $order_items = $this->db->get()->result();

            $order_details->items = $order_items;
        }
        return $order_details;
    }
    public function update_order_status($order_id, $new_status)
    {
        $this->db->set('status', $new_status);
        $this->db->where('id', $order_id);
        $this->db->update('orders');

        return true;
    }

    public function save_order_by_id($id, $update_data)
    {
        $order = $this->db->get_where('orders', ['id' => $id])->row_array();

        if ($order) {
            $this->db->where('id', $id);
            $success = $this->db->update('orders', $update_data);
            if ($success) {
                log_message('info', "✅ Order ID {$id} updated successfully in DB. Updated fields: " . json_encode($update_data));
            } else {
                $error = $this->db->error();
                log_message('error', "❌ Failed to update Order ID {$id}. Error: " . json_encode($error));
            }
            return $success;
        } else {
            log_message('warning', "⚠️ Order ID {$id} not found in DB. Update skipped. Data: " . json_encode($update_data));
            return false;
        }
    }
}
