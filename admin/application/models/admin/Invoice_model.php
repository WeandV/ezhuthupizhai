<?php
class Invoice_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function generate_secure_password($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    private function find_user_by_contact($phone, $email)
    {
        $this->db->group_start();
        $this->db->where('phone', $phone);
        $this->db->or_where('email', $email);
        $this->db->group_end();
        $query = $this->db->get('users');
        if ($query->num_rows() > 0) {
            return $query->row();
        }
        return false;
    }

    private function update_user($user_id, $data)
    {
        $this->db->where('id', $user_id);
        $this->db->update('users', $data);
        return $this->db->affected_rows() > 0;
    }

    private function update_user_address($user_id, $data)
    {
        $this->db->where('user_id', $user_id);
        $this->db->update('user_addresses', $data);
        return $this->db->affected_rows() > 0;
    }

    private function find_vendor_by_contact($phone, $email)
    {
        $this->db->group_start();
        $this->db->where('phone', $phone);
        $this->db->or_where('email', $email);
        $this->db->group_end();
        $query = $this->db->get('vendors');
        if ($query->num_rows() > 0) {
            return $query->row();
        }
        return false;
    }

    private function update_vendor($vendor_id, $data)
    {
        $this->db->where('id', $vendor_id);
        $this->db->update('vendors', $data);
        return $this->db->affected_rows() > 0;
    }

    public function get_all_products()
    {
        $this->db->select('id, name as title, mrp_price as price');
        $query = $this->db->get('products');
        return $query->result_array();
    }

    // Function to get a single product's type (required for inventory logic)
    public function get_product_by_id($product_id)
    {
        $this->db->select('type');
        $this->db->where('id', $product_id);
        $query = $this->db->get('products');
        return $query->row();
    }

    public function save_full_invoice($invoice_data)
    {
        $required_fields = [
            'invoice_date',
            'invoice_number',
            'invoice_for',
            'customer_name',
            'payment_status',
        ];

        foreach ($required_fields as $field) {
            if (!isset($invoice_data[$field]) || empty($invoice_data[$field])) {
                return ['success' => false, 'message' => "Required field '$field' is missing or empty."];
            }
        }

        if ($invoice_data['payment_status'] !== 'Unpaid') {
            if (!isset($invoice_data['payment_mode']) || empty($invoice_data['payment_mode'])) {
                return ['success' => false, 'message' => "Required field 'payment_mode' is missing or empty."];
            }
        }

        if (empty($invoice_data['items'])) {
            return ['success' => false, 'message' => "Invoice must contain at least one item."];
        }

        $this->db->trans_begin();

        try {
            $recipient_type = $invoice_data['invoice_for'];
            $phone_number = $invoice_data['phone_number'] ?? null;
            $customer_name = $invoice_data['customer_name'] ?? null;
            $customer_email = $invoice_data['customer_email'] ?? null;
            $billing_address1 = $invoice_data['billing_address1'] ?? null;
            $billing_address2 = $invoice_data['billing_address2'] ?? null;
            $city = $invoice_data['city'] ?? null;
            $state = $invoice_data['state'] ?? null;
            $pincode = $invoice_data['pincode'] ?? null;

            if ($recipient_type === 'customer') {
                $user = $this->find_user_by_contact($phone_number, $customer_email);

                $user_data = [
                    'first_name' => $customer_name,
                    'email' => $customer_email,
                    'phone' => $phone_number,
                ];

                $address_data = [
                    'phone' => $phone_number,
                    'email' => $customer_email,
                    'first_name' => $customer_name,
                    'address1' => $billing_address1,
                    'address2' => $billing_address2,
                    'city' => $city,
                    'state' => $state,
                    'zip_code' => $pincode,
                    'country' => 'India',
                ];

                if ($user) {
                    $this->update_user($user->id, $user_data);
                    $this->update_user_address($user->id, $address_data);
                } else {
                    // New user, create them
                    $new_password = $this->generate_secure_password();
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $user_data['password'] = $hashed_password;
                    $user_data['created_at'] = date('Y-m-d H:i:s');

                    $this->db->insert('users', $user_data);
                    $new_user_id = $this->db->insert_id();

                    if (!$new_user_id) {
                        throw new Exception('Failed to create new user.');
                    }

                    $address_data['user_id'] = $new_user_id;
                    $address_data['created_at'] = date('Y-m-d H:i:s');
                    $this->db->insert('user_addresses', $address_data);
                }
            } elseif ($recipient_type === 'vendor') {
                $vendor = $this->find_vendor_by_contact($phone_number, $customer_email);

                $vendor_data = [
                    'name' => $customer_name,
                    'email' => $customer_email,
                    'phone' => $phone_number,
                    'address_line1' => $billing_address1,
                    'address_line2' => $billing_address2,
                    'city' => $city,
                    'state' => $state,
                    'pincode' => $pincode,
                    'country' => 'India',
                ];

                if ($vendor) {
                    $this->update_vendor($vendor->id, $vendor_data);
                } else {
                    $vendor_data['created_at'] = date('Y-m-d H:i:s');
                    $this->db->insert('vendors', $vendor_data);
                }
            }

            $invoice_header_data = [
                'invoice_date' => $invoice_data['invoice_date'],
                'invoice_number' => $invoice_data['invoice_number'],
                'recipient_type' => $invoice_data['invoice_for'],
                'customer_name' => $invoice_data['customer_name'],
                'phone_number' => $invoice_data['phone_number'] ?? null,
                'customer_email' => $invoice_data['customer_email'] ?? null,
                'billing_address1' => $invoice_data['billing_address1'] ?? null,
                'billing_address2' => $invoice_data['billing_address2'] ?? null,
                'city' => $invoice_data['city'] ?? null,
                'state' => $invoice_data['state'] ?? null,
                'pincode' => $invoice_data['pincode'] ?? null,
                'payment_status' => $invoice_data['payment_status'],
                'payment_mode' => $invoice_data['payment_mode'] ?? null,
                'total_amount' => $invoice_data['total_amount'],
                'discount_percentage' => $invoice_data['discount_percentage'],
                'flat_discount' => $invoice_data['flat_discount'],
                'delivery_charge' => $invoice_data['delivery_charge'],
                'sub_total' => $invoice_data['sub_total'],
                'discount_amount' => $invoice_data['discount_amount'], // Added this line
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->insert('invoices', $invoice_header_data);
            $invoice_id = $this->db->insert_id();

            if (!$invoice_id) {
                throw new Exception('Failed to save the invoice header.');
            }

            $invoice_items_data = [];
            foreach ($invoice_data['items'] as $item) {
                $product_id = $item['book_id'] ?? null;
                $quantity = $item['quantity'] ?? 0;
                $price = $item['price'] ?? 0.00;
                $total = $item['total'] ?? 0.00;

                if (!$product_id || !$quantity) {
                    continue;
                }

                $invoice_items_data[] = [
                    'invoice_id' => $invoice_id,
                    'product_id' => $product_id,
                    'price' => $price,
                    'quantity' => $quantity,
                    'total' => $total,
                ];

                $product = $this->get_product_by_id($product_id);

                if (!$product) {
                    throw new Exception('Product not found for ID: ' . $product_id);
                }

                if ($product->type === 'single') {
                    $this->db->set('stock_quantity', 'stock_quantity - ' . $quantity, FALSE);
                    $this->db->where('item_id', $product_id);
                    $result = $this->db->update('inventory');
                    if (!$result || $this->db->affected_rows() === 0) {
                        throw new Exception('Failed to update stock for single item ID: ' . $product_id);
                    }
                } elseif ($product->type === 'combo' || $product->type === 'giftbox') {
                    $components_query = $this->db->get_where('product_components', ['product_id' => $product_id]);
                    $components = $components_query->result();

                    if (empty($components)) {
                        throw new Exception('Combo or giftbox has no defined components. Product ID: ' . $product_id);
                    }

                    foreach ($components as $component) {
                        $item_id = $component->item_id;
                        $component_quantity = $component->quantity;
                        $total_quantity_to_decrement = $quantity * $component_quantity;

                        $this->db->set('stock_quantity', 'stock_quantity - ' . $total_quantity_to_decrement, FALSE);
                        $this->db->where('item_id', $item_id);
                        $result = $this->db->update('inventory');

                        if (!$result || $this->db->affected_rows() === 0) {
                            throw new Exception('Failed to update stock for component ID: ' . $item_id);
                        }
                    }
                }
            }

            $this->db->insert_batch('invoice_items', $invoice_items_data);

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Failed to save invoice items.');
            }

            $this->db->trans_commit();
            return ['success' => true, 'message' => 'Invoice saved successfully.'];
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Invoice and inventory update failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function get_invoices()
    {
        $this->db->select('*');
        $this->db->order_by('id', 'DESC');
        $this->db->from('invoices');
        $query = $this->db->get();
        return $query->result();
    }

    public function get_dealer()
    {
        $query = $this->db->get('vendors');
        return $query->result();
    }

    public function insert_vendor($data)
    {
        $this->db->insert('vendors', $data);
        return $this->db->insert_id();
    }

    public function vendor_exists($field, $value)
    {
        $this->db->where($field, $value);
        $query = $this->db->get('vendors');
        return ($query->num_rows() > 0);
    }
    public function delete_dealer($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('vendors');
        return $this->db->affected_rows() > 0;
    }

    public function update_dealer($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('vendors', $data);

        return $this->db->affected_rows() > 0;
    }

    public function get_full_invoice_details($invoice_id)
    {
        $invoice = $this->db->get_where('invoices', ['id' => $invoice_id])->row_array();

        if (!$invoice) {
            return null;
        }

        $this->db->where('invoice_id', $invoice_id);
        $items = $this->db->get('invoice_items')->result_array();

        foreach ($items as &$item) {
            $product = $this->db
                ->select('name, sku, length_cm, breadth_cm, height_cm, weight_kg, categories')
                ->get_where('products', ['id' => $item['product_id']])
                ->row_array();

            // Prepare item data structure to match what the controller's private send_to_shiprocket expects
            $item['product_name'] = $product['name'] ?? 'Product';
            $item['sku'] = $product['sku'] ?? 'SKU-NA';
            $item['length_cm'] = $product['length_cm'] ?? 30; // Default size
            $item['breadth_cm'] = $product['breadth_cm'] ?? 30;
            $item['height_cm'] = $product['height_cm'] ?? 3;
            $item['weight_kg'] = $product['weight_kg'] ?? 0.5;
            $item['categories'] = $product['categories'] ?? 'Default';
            $item['price_at_order'] = $item['price'];
        }
        unset($item);
        $invoice['items'] = $items;
        return $invoice;
    }

    public function update_shiprocket_ids($invoice_id, $shipment_id, $awb_code)
    {
        $data = array(
            'shiprocket_id' => $shipment_id,
            'awb_code' => $awb_code,
            'shipment_status' => 'Order Created',
        );
        $this->db->where('id', $invoice_id);
        return $this->db->update('invoices', $data);
    }

    public function update_awb($invoice_id, $awb_code)
    {
        $data = [
            'awb_code' => $awb_code,
            'shipment_status' => 'Ready to Ship',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $invoice_id);
        $this->db->update('invoices', $data);

        return $this->db->affected_rows() > 0;
    }

    public function update_shipment_status($invoice_id, $status)
    {
        $data = [
            'shipment_status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $invoice_id);
        $this->db->update('invoices', $data);

        return $this->db->affected_rows() > 0;
    }
}
