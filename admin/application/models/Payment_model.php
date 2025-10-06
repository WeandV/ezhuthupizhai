<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payment_model extends CI_Model
{
 private $table = 'razorpay_payments'; // your table name

 public function __construct()
 {
  parent::__construct();
 }

 // Insert Razorpay order details
 public function create_order($data)
 {
  $data['created_at'] = date('Y-m-d H:i:s');
  $data['updated_at'] = date('Y-m-d H:i:s');
  $insert = $this->db->insert($this->table, $data);
  if ($insert) {
   // Check for insert success
   if ($this->db->affected_rows() > 0) {
    return $this->db->insert_id();
   }
  }
  log_message('error', 'DB Insert Failed: ' . $this->db->last_query() . ' | Error: ' . print_r($this->db->error(), true));
  return false;
 }

 // Update payment after verification
 public function update_payment($razorpay_order_id, $data)
 {
  $data['updated_at'] = date('Y-m-d H:i:s');
  $this->db->where('razorpay_order_id', $razorpay_order_id);
  return $this->db->update($this->table, $data);
 }

 // Fetch payment by Razorpay order ID
 public function get_payment($razorpay_order_id)
 {
  return $this->db->get_where($this->table, ['razorpay_order_id' => $razorpay_order_id])->row_array();
 }
}