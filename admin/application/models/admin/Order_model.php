<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Order_model extends CI_Model
{
    public function get_all_orders()
    {
        $this->db->select('id, first_name, last_name, payment_method, final_total, created_at, status');
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

        return $this->db->affected_rows() > 0;
    }
}
