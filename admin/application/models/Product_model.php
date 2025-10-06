<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // Optionally load the database library here if not auto-loaded
    }

    /**
     * Fetches a product by its ID from the 'products' table.
     * Used for validation in prepareOrderItems and for type lookup in updateInventory.
     * * @param int $product_id
     * @return object|null
     */
    public function get_product_by_id($product_id) {
        if (empty($product_id)) {
            return null;
        }
        // Your existing logic from updateInventory to fetch the product
        $query = $this->db->get_where('products', ['id' => $product_id]);
        return $query->row();
    }
}