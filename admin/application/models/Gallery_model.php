<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Gallery_model extends CI_Model {

    public function get_all_products() {
        $this->db->select('product');
        $this->db->group_by('product');
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('gallery');
        return $query->result();
    }

    public function get_images($product = null) {
        if ($product) {
            $this->db->where('product', urldecode($product));
        }
        $query = $this->db->get('gallery');
        return $query->result();
    }
}
