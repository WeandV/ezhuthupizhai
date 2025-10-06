<?php
class Inventory_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Gets all individual items and their stock for the main inventory table.
     * Combos and giftboxes are not included as their stock is virtual.
     */
    public function get_inventory()
    {
        $this->db->select('
            inv.item_id,
            inv.stock_quantity,
            p.id,
            p.name AS product_name,
            p.sku AS product_sku,
            p.mrp_price AS product_mrp_price,
            pi.image_url AS product_main_image
        ');
        $this->db->from('inventory AS inv');
        $this->db->join('products AS p', 'p.id = inv.item_id'); // Correct join to `products` table
        $this->db->join('product_images AS pi', 'pi.product_id = p.id AND pi.is_thumbnail = 1', 'left');

        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Gets all single products and their stock for the update modal dropdown.
     * Only 'single' type products can have their stock updated manually.
     */
    public function get_all_products_with_stock()
    {
        $this->db->select('p.id, p.name, inv.stock_quantity');
        $this->db->from('products AS p');
        $this->db->join('inventory AS inv', 'inv.item_id = p.id', 'left');
        $this->db->where('p.type', 'single'); // Crucial: Only select individual items
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Updates the stock of a single, individual item.
     * This method prevents updating stock for combo products.
     */
    public function update_stock($product_id, $new_stock)
    {
        // First, verify that the product is a 'single' item and exists in the inventory table
        $this->db->where('item_id', $product_id);
        $query = $this->db->get('inventory');
        
        if ($query->num_rows() > 0) {
            // If it exists, update the stock_quantity
            $data = array('stock_quantity' => $new_stock);
            $this->db->where('item_id', $product_id);
            $this->db->update('inventory', $data);
            return $this->db->affected_rows();
        }
        
        // Return 0 if the product doesn't exist in the inventory or is not a single item
        return 0;
    }
}