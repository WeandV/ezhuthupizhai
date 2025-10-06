<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Razorpay\Api\Api;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Google\Client as Google_Client;
use Google\Service\Gmail as Google_Service_Gmail;
use Google\Service\Gmail\Message as Google_Service_Gmail_Message;

class Checkout extends CI_Controller
{
    private $api;
    private $shiprocket_token;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Order_model');
        $this->load->model('User_model');
        $this->load->config('razorpay');
        $this->load->config('shiprocket');
        $this->load->model('Payment_model');
        $this->load->library(['form_validation', 'session', 'email']);
        $this->load->helper(['url', 'form']);
        $this->email->set_mailtype("html");

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
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit(0);
        }

        if ($this->input->method() === 'options') {
            $this->output->set_status_header(200);
            exit();
        }
    }

    public function place_order_Magic_razorpay()
    {
        try {
            $input = json_decode($this->input->raw_input_stream, true);
            if (empty($input)) {
                return $this->output
                    ->set_status_header(400)
                    ->set_output(json_encode(['success' => false, 'message' => 'No data provided.']));
            }

            $shippingDetails = $input['shipping_details'] ?? [];
            $orderSummary    = $input['order_summary'] ?? [];
            $cartItems       = $input['cart_items'] ?? [];

            // 🚀 NEW: Retrieve Pincode and Courier ID from Angular payload
            $delivery_pincode = $input['pincode'] ?? null;
            $courier_id       = $input['courier_id'] ?? null;

            if (empty($shippingDetails) || empty($orderSummary) || empty($cartItems) || empty($delivery_pincode) || empty($courier_id)) {
                return $this->output
                    ->set_status_header(400)
                    ->set_output(json_encode(['success' => false, 'message' => 'Missing order, cart, or shipping details (Pincode/Courier).']));
            }

            // --- 1. Calculate Amounts (Existing Logic - Correct) ---
            $subtotal_paise  = (int)round(((float)($orderSummary['subtotal'] ?? 0)) * 100);
            $coupon_paise    = (int)round(((float)($orderSummary['coupon_discount'] ?? 0)) * 100);
            $delivery_paise  = (int)round(((float)($orderSummary['delivery_charge'] ?? 0)) * 100);

            $subtotal_after_discount_paise = max(0, $subtotal_paise - $coupon_paise);
            $amount_for_razorpay = (int)($subtotal_after_discount_paise + $delivery_paise);

            $subtotal_before_coupon  = round($subtotal_paise / 100, 2);
            $coupon_discount         = round($coupon_paise / 100, 2);
            $subtotal_after_discount = round($subtotal_after_discount_paise / 100, 2);
            $delivery_charge         = round($delivery_paise / 100, 2);
            $final_total             = round($amount_for_razorpay / 100, 2);

            // ... (Line item and total_mrp_price_paise calculation - No Change) ...
            $total_mrp_price_paise = 0;
            foreach ($cartItems as $item) {
                $item_price = (float)($item['effectivePrice'] ?? 0);
                if ($item_price <= 0) {
                    $item_price = (float)($item['mrpPriceNumeric'] ?? 0);
                }
                if ($item_price <= 0) {
                    continue;
                }
                $price_paise = (int)round($item_price * 100);
                $quantity = (int)($item['quantity'] ?? 1);
                $total_mrp_price_paise += ($price_paise * $quantity);
            }

            // --- 2. Create Line Items with Discount Distributed (Existing Logic - Correct) ---
            $line_items = [];
            $line_items_total = 0;

            $target_product_total = $subtotal_after_discount_paise;
            $discount_difference = $total_mrp_price_paise - $target_product_total;

            foreach ($cartItems as $item) {
                $product = $item['product'];
                $item_price = (float)($item['effectivePrice'] ?? 0);
                if ($item_price <= 0) {
                    $item_price = (float)($item['mrpPriceNumeric'] ?? 0);
                }
                if ($item_price <= 0) {
                    continue;
                }

                $original_price_paise = (int)round($item_price * 100);
                $quantity = (int)($item['quantity'] ?? 1);

                $proportion = ($original_price_paise * $quantity > 0) ? (($original_price_paise * $quantity) / $total_mrp_price_paise) : 0;
                $item_discount = (int)round($discount_difference * $proportion);

                $final_item_price_paise = max(0, ($original_price_paise * $quantity) - $item_discount);
                $price_per_unit_paise = (int)round($final_item_price_paise / $quantity);

                if ($price_per_unit_paise == 0) continue;

                $line_item = [
                    'sku' => $product['sku'] ?? '',
                    'variant_id' => $product['id'] ?? '',
                    'price' => $price_per_unit_paise,
                    'tax_amount' => 0,
                    'quantity' => $quantity,
                    'name' => $product['name'] ?? 'Product',
                    'image_url' => $product['thumbnail_image'] ?? null
                ];
                // ... (Weight and Dimensions logic - No Change) ...
                if (!empty($product['weight_kg'])) {
                    $line_item['weight'] = (int)round((float)$product['weight_kg'] * 1000);
                }
                if (!empty($product['length_cm'])) {
                    $line_item['dimensions'] = [
                        'length' => (string)(float)$product['length_cm'],
                        'width'  => (string)(float)$product['breadth_cm'],
                        'height' => (string)(float)$product['height_cm'],
                    ];
                }

                $line_items[] = $line_item;
                $line_items_total += ($price_per_unit_paise * $quantity);
            }

            if ($delivery_paise > 0) {
                $line_items[] = [
                    'sku' => 'DELIVERY',
                    'name' => 'Delivery Charge',
                    'price' => $delivery_paise,
                    'quantity' => 1,
                    'tax_amount' => 0
                ];
                $line_items_total += $delivery_paise;
            }

            $line_items_total = (int)$line_items_total;

            $next_id = $this->db->select_max('id', 'max_id')->get('orders')->row()->max_id;
            $next_id = $next_id ? $next_id + 1 : 1;
            $invoice_id = 'INEPWB0' . $next_id;

            $sanitized_line_items = array_map(function ($item) {
                $item['price'] = (int)$item['price'];
                $item['tax_amount'] = (int)$item['tax_amount'];
                $item['quantity'] = (int)$item['quantity'];
                return $item;
            }, $line_items);

            // --- 3. Create Razorpay Order ---
            $razorpayOrder = $this->api->order->create([
                'receipt' => $invoice_id,
                'amount' => (int)$line_items_total,
                'currency' => $this->config->item('razorpay_currency') ?? 'INR',
                'payment_capture' => 1,
                'line_items_total' => (int)$line_items_total,
                'line_items' => array_map(function ($item) {
                    $mapped = [
                        'sku'       => $item['sku'],
                        'price'     => (int)$item['price'],
                        'quantity'  => (int)$item['quantity'],
                        'tax_amount' => (int)$item['tax_amount'],
                        'name'      => $item['name'],
                        'image_url' => $item['image_url'] ?? null,
                    ];
                    if (isset($item['variant_id'])) {
                        $mapped['variant_id'] = $item['variant_id'];
                    }
                    return $mapped;
                }, $sanitized_line_items),
                'notes' => [
                    'coupon_discount' => (int)$coupon_paise,
                    'delivery_charge' => (int)$delivery_paise,
                    'client_pincode' => $delivery_pincode, // Store for debugging/tracking
                ]
            ]);

            // 🛑 CRITICAL CHANGE: Only store a temporary tracking record.
            // The final order/payment record creation MUST be in the handler/webhook.
            $this->db->insert('razorpay_payments', [
                'order_id' => 0, // Zero means no final application order yet
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $final_total,
                'currency' => $this->config->item('razorpay_currency') ?? 'INR',
                'status' => 'created',
                'invoice_id' => $invoice_id,
                'delivery_pincode' => $delivery_pincode,     // 👈 Store Pincode
                'courier_id' => $courier_id,                 // 👈 Store Courier ID (Crucial for serviceability check)
                'cart_items_json' => json_encode($cartItems), // Store cart for final order creation
                'order_summary_json' => json_encode($orderSummary), // Store summary
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // --- 4. Return Response (Existing Logic - Correct) ---
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(201)
                ->set_output(json_encode([
                    'success' => true,
                    'message' => 'Razorpay order created successfully.',
                    'razorpayOrderId' => $razorpayOrder['id'],
                    'key' => $this->config->item('razorpay_key_id'),
                    'amount' => (int)$line_items_total,
                    'currency' => $this->config->item('razorpay_currency') ?? 'INR',
                    'invoice_id' => $invoice_id,
                    'notes' => $razorpayOrder['notes'],
                    'name' => "Ezhuthupizhai",
                    'description' => "Payment for your order",
                    'prefill' => [
                        'name' => trim($shippingDetails['firstName'] . ' ' . ($shippingDetails['lastName'] ?? '')),
                        'email' => $shippingDetails['email'] ?? '',
                        'contact' => $shippingDetails['phone'] ?? ''
                    ],
                    'order_summary' => [
                        'subtotal' => $subtotal_before_coupon,
                        'coupon_discount' => $coupon_discount,
                        'subtotal_after_discount' => $subtotal_after_discount,
                        'delivery_charge' => $delivery_charge,
                        'final_total' => $final_total
                    ]
                ]));
        } catch (Exception $e) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    public function verify_Magic_payment()
    {
        $input = json_decode($this->input->raw_input_stream, true);
        $this->output->set_content_type('application/json');


        if (empty($input['razorpay_payment_id']) || empty($input['razorpay_order_id']) || empty($input['razorpay_signature'])) {
            $this->output->set_status_header(400);
            log_message('error', 'Invalid payment data provided: ', json_encode($input));
            echo json_encode(['success' => false, 'message' => 'Invalid payment data provided.']);
            return;
        }

        $attributes = [
            'razorpay_order_id' => $input['razorpay_order_id'],
            'razorpay_payment_id' => $input['razorpay_payment_id'],
            'razorpay_signature' => $input['razorpay_signature']
        ];
        log_message('debug', 'Razorpay verification attributes: ' . json_encode($attributes, true));

        $this->db->trans_begin();

        try {
            log_message('debug', 'Verifying Razorpay signature...');
            $this->api->utility->verifyPaymentSignature($attributes);

            log_message('debug', 'Fetching Razorpay payment: ' . $input['razorpay_payment_id']);
            $payment = $this->api->payment->fetch($input['razorpay_payment_id']);
            log_message('debug', 'Payment fetch response: ' . json_encode($payment, true));

            if ($payment['status'] !== 'captured') {
                $this->db->trans_rollback();
                $this->output->set_status_header(400);
                log_message('error', 'Payment not captured: ' . json_encode($payment, true));
                echo json_encode(['success' => false, 'message' => 'Payment not completed.']);
                return;
            }

            log_message('debug', 'Fetching Razorpay order: ' . $input['razorpay_order_id']);
            $razorpayOrder = $this->api->order->fetch($input['razorpay_order_id']);
            $razorpayShipping = $razorpayOrder['customer_details']['shipping_address'] ?? [];
            log_message('debug', 'Razorpay order fetch response: ' . json_encode($razorpayOrder, true));

            $invoice_id = $razorpayOrder['receipt'] ?? null;

            // 🛑 CRITICAL FIX START
            // 1. Fetch the temporary record saved during order creation
            $temp_payment_record = $this->db->get_where('razorpay_payments', ['razorpay_order_id' => $input['razorpay_order_id']])->row();

            if (!$temp_payment_record) {
                throw new Exception('Temporary order record not found for Razorpay Order ID: ' . $input['razorpay_order_id']);
            }

            // 2. Decode the stored JSON data to retrieve full order summary and cart details
            // This replaces the unreliable $input['order_summary'] and $input['cart_items']
            $orderSummary = json_decode($temp_payment_record->order_summary_json, true) ?? [];
            $cartItems    = json_decode($temp_payment_record->cart_items_json, true) ?? [];
            $delivery_pincode = $temp_payment_record->delivery_pincode ?? null;
            $courier_id       = $temp_payment_record->courier_id ?? null;

            // 🛑 CRITICAL FIX END

            $shippingDetails = [
                'firstName' => $razorpayShipping['name'] ?? $input['shipping_details']['firstName'] ?? '',
                'lastName'  => $input['shipping_details']['lastName'] ?? '',
                'email'     => $razorpayOrder['customer_details']['email'] ?? $input['shipping_details']['email'] ?? '',
                'phone'     => $razorpayShipping['contact'] ?? $input['shipping_details']['phone'] ?? '',
                'address1'  => $razorpayShipping['line1'] ?? $input['shipping_details']['address1'] ?? '',
                'address2'  => $razorpayShipping['line2'] ?? $input['shipping_details']['address2'] ?? '',
                'city'      => $razorpayShipping['city'] ?? $input['shipping_details']['city'] ?? '',
                'state'     => $razorpayShipping['state'] ?? $input['shipping_details']['state'] ?? '',
                'zipCode'   => $razorpayShipping['zipcode'] ?? $input['shipping_details']['zipCode'] ?? '',
                'country'   => strtoupper($razorpayShipping['country'] ?? $input['shipping_details']['country'] ?? ''),
                // Note: orderNotes is often missing from Razorpay data, use from $input if possible or log it
                'orderNotes' => $input['shipping_details']['orderNotes'] ?? null,
            ];

            // Re-fetch user context if needed, though typically this is passed in $input
            $user_auth_context = $input['user_auth_context'] ?? [];


            log_message('debug', 'Order summary (from DB): ' . json_encode($orderSummary, true));
            log_message('debug', 'Cart items (from DB): ' . json_encode($cartItems, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            log_message('debug', 'User auth context: ' . json_encode($user_auth_context, true));

            // User creation
            $user_id = $this->handleUserAndAddressCreation($user_auth_context, $shippingDetails);
            log_message('debug', 'User ID after handleUserAndAddressCreation: ' . json_encode($user_id, true));

            if (!$user_id) throw new Exception('Failed to create user.');

            // Order creation - NOW using the reliable $orderSummary from the database
            $orderData = [
                'user_id' => $user_id,
                'invoice_id' => $invoice_id,
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
                // These keys MUST be present in $orderSummary from the DB
                'subtotal' => $orderSummary['subtotal'],
                // Using $orderSummary for coupon_discount is safer as it's already in the correct currency format
                'coupon_discount' => $orderSummary['coupon_discount'] ?? 0,
                'subtotal_after_discount' => $orderSummary['subtotal_after_discount'],
                'delivery_charge' => $orderSummary['delivery_charge'] ?? 0,
                'final_total' => $orderSummary['final_total'],
                'status' => 'processing',
            ];

            log_message('debug', 'Order data prepared: ' . json_encode($orderData, true));

            $order_id = $this->Order_model->insert_order($orderData);
            log_message('debug', 'Inserted order_id: ' . json_encode($order_id, true));

            if (!$order_id) throw new Exception('Failed to create order.');

            $orderItemsToInsert = $this->prepareOrderItemsMagic($cartItems, $order_id);
            log_message('debug', 'Order items to insert: ' . json_encode($orderItemsToInsert, true));

            if (empty($orderItemsToInsert) || !$this->Order_model->insert_order_items($orderItemsToInsert)) {
                throw new Exception('Failed to save order items.');
            }

            log_message('debug', 'Updating inventory...');
            $this->updateInventory($orderItemsToInsert);

            log_message('debug', 'Updating payment details...');
            $this->Payment_model->update_payment($input['razorpay_order_id'], [
                'order_id' => $order_id,
                'razorpay_payment_id' => $input['razorpay_payment_id'],
                'razorpay_signature' => $input['razorpay_signature'],
                'status' => 'paid',
                'invoice_id' => $invoice_id
            ]);

            $this->db->trans_commit();
            log_message('debug', 'Transaction committed successfully.');

            $order_details = $this->Order_model->get_order_by_id($order_id);
            $order_items = $this->Order_model->get_order_items($order_id);

            try {
                log_message('debug', 'Sending order to Shiprocket...');
                $this->send_to_shiprocket($order_id, $order_details, $order_items);
            } catch (Exception $e) {
                log_message('error', 'Shiprocket API error: ' . $e->getMessage());
            }

            log_message('debug', 'Sending confirmation emails...');
            $this->_sendOrderConfirmationEmail($order_id, $order_details, $order_items);
            $this->_sendAdminNewOrderEmail($order_id, $order_details, $order_items);

            $this->output->set_status_header(200);
            echo json_encode(['success' => true, 'message' => 'Payment verified and order created successfully.', 'order_id' => $order_id]);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Razorpay Verify Error: ' . $e->getMessage());
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function place_order_razorpay()
    {
        try {
            $input = json_decode($this->input->raw_input_stream, true);

            if (empty($input)) {
                return $this->output
                    ->set_status_header(400)
                    ->set_output(json_encode(['success' => false, 'message' => 'No data provided.']));
            }

            $shippingDetails = $input['shipping_details'] ?? [];
            $orderSummary    = $input['order_summary'] ?? [];
            $cartItems       = $input['cart_items'] ?? [];

            if (empty($shippingDetails) || empty($orderSummary)) {
                return $this->output
                    ->set_status_header(400)
                    ->set_output(json_encode(['success' => false, 'message' => 'Missing order details.']));
            }
            $delivery_charge = $orderSummary['delivery_charge'] ?? 0;
            $final_total = ($orderSummary['subtotal_after_discount'] ?? 0) + $delivery_charge;
            $amount_in_paise = intval(round($final_total * 100));
            $next_id = $this->db->select_max('id', 'max_id')->get('orders')->row()->max_id;
            $next_id = $next_id ? $next_id + 1 : 1;
            $invoice_id = 'INEPWB0' . $next_id;
            $razorpayOrder = $this->api->order->create([
                'receipt' => $invoice_id,
                'amount' => $amount_in_paise,
                'currency' => $this->config->item('razorpay_currency') ?? 'INR',
                'payment_capture' => 1,
                'notes' => [
                    'app_name'        => 'EP New Website',
                    'Invoice'        => $invoice_id,
                    'app_id'          => 'EPNGW001',
                ]
            ]);
            $this->db->insert('razorpay_payments', [
                'order_id' => 0,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $final_total,
                'currency' => $this->config->item('razorpay_currency') ?? 'INR',
                'status' => 'created',
                'invoice_id' => $invoice_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(201)
                ->set_output(json_encode([
                    'success' => true,
                    'message' => 'Razorpay order created successfully.',
                    'razorpayOrderId' => $razorpayOrder['id'],
                    'key' => $this->config->item('razorpay_key_id'),
                    'amount' => $amount_in_paise,
                    'currency' => $this->config->item('razorpay_currency') ?? 'INR',
                    'invoice_id' => $invoice_id,
                    'notes' => $razorpayOrder['notes'],
                    'name' => "Ezhuthupizhai",
                    'description' => "Payment for your order",
                    'prefill' => [
                        'name' => $shippingDetails['firstName'] . ' ' . ($shippingDetails['lastName'] ?? ''),
                        'email' => $shippingDetails['email'] ?? '',
                        'contact' => $shippingDetails['phone'] ?? ''
                    ]
                ]));
        } catch (Exception $e) {
            log_message('error', 'Razorpay Order Creation Failed: ' . $e->getMessage());
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }


    public function verify_payment()
    {
        $input = json_decode($this->input->raw_input_stream, true);
        $this->output->set_content_type('application/json');

        if (empty($input['razorpay_payment_id']) || empty($input['razorpay_order_id']) || empty($input['razorpay_signature'])) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'Invalid payment data provided.']);
            return;
        }

        $attributes = [
            'razorpay_order_id' => $input['razorpay_order_id'],
            'razorpay_payment_id' => $input['razorpay_payment_id'],
            'razorpay_signature' => $input['razorpay_signature']
        ];

        $this->db->trans_begin();

        try {
            $this->api->utility->verifyPaymentSignature($attributes);
            $payment = $this->api->payment->fetch($input['razorpay_payment_id']);
            if ($payment['status'] !== 'captured') {
                $this->db->trans_rollback();
                $this->output->set_status_header(400);
                echo json_encode(['success' => false, 'message' => 'Payment not completed.']);
                return;
            }
            $razorpayOrder = $this->api->order->fetch($input['razorpay_order_id']);
            $invoice_id = $razorpayOrder['receipt'] ?? null;
            $shippingDetails = $input['shipping_details'] ?? [];
            $orderSummary = $input['order_summary'] ?? [];
            $cartItems = $input['cart_items'] ?? [];
            $user_auth_context = $input['user_auth_context'] ?? [];
            $user_id = $this->handleUserAndAddressCreation($user_auth_context, $shippingDetails);
            if (!$user_id) throw new Exception('Failed to create user.');
            $orderData = [
                'user_id' => $user_id,
                'invoice_id' => $invoice_id,
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
            if (!$order_id) throw new Exception('Failed to create order.');
            $orderItemsToInsert = $this->prepareOrderItems($cartItems, $order_id);
            if (empty($orderItemsToInsert) || !$this->Order_model->insert_order_items($orderItemsToInsert)) {
                throw new Exception('Failed to save order items.');
            }
            $this->updateInventory($orderItemsToInsert);
            $this->Payment_model->update_payment($input['razorpay_order_id'], [
                'order_id' => $order_id,
                'razorpay_payment_id' => $input['razorpay_payment_id'],
                'razorpay_signature' => $input['razorpay_signature'],
                'status' => 'paid',
                'invoice_id' => $invoice_id
            ]);
            $this->db->trans_commit();
            $order_details = $this->Order_model->get_order_by_id($order_id);
            $order_items = $this->Order_model->get_order_items($order_id);
            try {
                $this->send_to_shiprocket($order_id, $order_details, $order_items);
            } catch (Exception $e) {
                log_message('error', $e->getMessage());
            }
            $this->_sendOrderConfirmationEmail($order_id, $order_details, $order_items);
            $this->_sendAdminNewOrderEmail($order_id, $order_details, $order_items);

            $this->output->set_status_header(200);
            echo json_encode(['success' => true, 'message' => 'Payment verified and order created successfully.', 'order_id' => $order_id]);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Razorpay Verify Error: ' . $e->getMessage());
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function _sendOrderConfirmationEmail($order_id, $order_details, $order_items)
    {
        require_once APPPATH . '../vendor/autoload.php';

        $accessToken = $this->refreshAccessToken();

        if (!$accessToken) {
            log_message('error', 'Order Confirmation Email Error: Failed to get Google API access token for order #' . $order_id);
            return false;
        }

        $client = new Google_Client();
        $client->setAccessToken($accessToken);
        $service = new Google_Service_Gmail($client);

        try {
            $from_email = $this->config->item('gmail_email');
            $from_name = $this->config->item('from_name');
            $to_email = $order_details['email'];

            $data = [
                'order_id' => $order_id,
                'order_details' => $order_details,
                'order_items' => $order_items,
                'from_email' => $from_email,
            ];

            $email_body_html = $this->load->view('emails/order_confirmation', $data, true);

            $email_body_text = strip_tags(str_replace(['<br>', '<br/>'], "\n", $email_body_html));
            $email_body_text = preg_replace("/\r?\n\r?\n/", "\n\n", $email_body_text);

            $boundary = uniqid('', true);

            $rawMessage = "From: {$from_name} <{$from_email}>\r\n";
            $rawMessage .= "To: <{$to_email}>\r\n";
            $rawMessage .= "Subject: Order Confirmation #" . $order_id . "\r\n";
            $rawMessage .= "MIME-Version: 1.0\r\n";
            $rawMessage .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";

            $rawMessage .= "--$boundary\r\n";
            $rawMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $rawMessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $rawMessage .= $email_body_text . "\r\n\r\n";

            $rawMessage .= "--$boundary\r\n";
            $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
            $rawMessage .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
            $rawMessage .= quoted_printable_encode($email_body_html) . "\r\n\r\n";

            $rawMessage .= "--$boundary--\r\n";

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

    private function _sendAdminNewOrderEmail($order_id, $order_details, $order_items)
    {
        require_once APPPATH . '../vendor/autoload.php';

        $accessToken = $this->refreshAccessToken();
        if (!$accessToken) {
            log_message('error', 'Admin Email Error: Failed to get Google API access token for order #' . $order_id);
            return false;
        }

        $client = new Google_Client();
        $client->setAccessToken($accessToken);
        $service = new Google_Service_Gmail($client);

        try {
            $from_email   = $this->config->item('gmail_email');
            $from_name    = $this->config->item('from_name');
            $admin_email  = $this->config->item('admin_email');

            $data = [
                'order_id'      => $order_id,
                'order_details' => $order_details,
                'order_items'   => $order_items,
                'from_email'    => $from_email,
            ];

            $email_body_html = $this->load->view('emails/admin_new_order', $data, true);

            $email_body_text = strip_tags(str_replace(['<br>', '<br/>'], "\n", $email_body_html));
            $boundary = uniqid('', true);

            $rawMessage  = "From: {$from_name} <{$from_email}>\r\n";
            $rawMessage .= "To: <{$admin_email}>\r\n";
            $rawMessage .= "Subject: New Order Received #" . $order_id . "\r\n";
            $rawMessage .= "MIME-Version: 1.0\r\n";
            $rawMessage .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";

            $rawMessage .= "--$boundary\r\n";
            $rawMessage .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $rawMessage .= $email_body_text . "\r\n\r\n";

            $rawMessage .= "--$boundary\r\n";
            $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
            $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $rawMessage .= chunk_split(base64_encode($email_body_html)) . "\r\n\r\n";

            $rawMessage .= "--$boundary--\r\n";

            $mime = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');
            $message = new Google_Service_Gmail_Message();
            $message->setRaw($mime);
            $service->users_messages->send('me', $message);

            log_message('info', 'Admin notified for new order #' . $order_id);
            return true;
        } catch (Exception $e) {
            log_message('error', 'Admin Email Error: ' . $e->getMessage());
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
            'last_name' => $shippingDetails['lastName'] ?? null,
            'phone' => $shippingDetails['phone'] ?? null
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
                'user_id' => $user_id,
                'first_name' => $shippingDetails['firstName'],
                'last_name' => $shippingDetails['lastName'] ?? null,
                'phone' => $shippingDetails['phone'],
                'email' => $shippingDetails['email'],
                'address1' => $shippingDetails['address1'],
                'address2' => $shippingDetails['address2'] ?? null,
                'city' => $shippingDetails['city'],
                'state' => $shippingDetails['state'],
                'zip_code' => $shippingDetails['zipCode'],
                'country' => $shippingDetails['country'],
                'type' => 'shipping',
                'is_default_billing' => 0,
                'is_default_shipping' => 1,
                'is_active' => 1
            ];

            if ($is_address_from_saved_selection && $address_id_from_frontend) {
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

    private function prepareOrderItemsMagic($cartItems, $order_id)
    {
        $orderItemsToInsert = [];
        foreach ($cartItems as $item) {
            $productId = $item['product']['id'] ?? null;
            $productName = $item['product']['name'] ?? 'Unknown Product';
            $priceAtOrder = floatval($item['effectivePrice'] ?? 0.00);
            $quantity = intval($item['quantity'] ?? 1);
            $byobItemsList = $item['byob_items_list'] ?? null;
            if (empty($productId) || $quantity <= 0 || $priceAtOrder < 0) {
                continue;
            }
            $orderItemsToInsert[] = [
                'order_id' => $order_id,
                'product_id' => $productId,
                'product_name' => $productName,
                'quantity' => $quantity,
                'price_at_order' => $priceAtOrder,
                'total' => $quantity * $priceAtOrder,
                'byob_items_list' => $byobItemsList,
            ];
        }
        return $orderItemsToInsert;
    }

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
                'order_id' => $order_id,
                'product_id' => $productId,
                'product_name' => $productName,
                'quantity' => $quantity,
                'price_at_order' => $priceAtOrder,
                'total' => $quantity * $priceAtOrder,
                'byob_items_list' => $byobItemsList,
            ];
        }
        return $orderItemsToInsert;
    }


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
                'id' => $order['id'],
                'first_name' => $order['first_name'],
                'last_name' => $order['last_name'],
                'email' => $order['email'],
                'phone' => $order['phone'],
                'address1' => $order['address1'],
                'address2' => $order['address2'],
                'city' => $order['city'],
                'state' => $order['state'],
                'zip_code' => $order['zip_code'],
                'country' => $order['country'],
                'order_notes' => $order['order_notes'],
                'payment_method' => $order['payment_method'],
                'subtotal' => floatval($order['subtotal']),
                'coupon_discount' => floatval($order['coupon_discount']),
                'subtotal_after_discount' => floatval($order['subtotal_after_discount']),
                'delivery_charge' => floatval($order['delivery_charge']),
                'final_total' => floatval($order['final_total']),
                'status' => $order['status'],
                'created_at' => $order['created_at'],
                'order_items' => $order_items,
            ]
        ];

        $this->output->set_status_header(200)->set_output(json_encode($response_data));
    }

    private function authenticate_shiprocket()
    {
        $email = $this->config->item('shiprocket_email');
        $password = $this->config->item('shiprocket_password');

        // Log the credentials being used (for debugging only, do not do this in production)
        log_message('info', 'Attempting Shiprocket authentication with email: ' . $email);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->config->item('shiprocket_api_url') . "auth/login",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'email' => $email,
                'password' => $password
            ]),
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            log_message('error', 'Shiprocket cURL Error during authentication: ' . $err);
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['token'])) {
            $this->shiprocket_token = $data['token'];
            log_message('info', 'Shiprocket authentication successful. Token received.');
            return $this->shiprocket_token;
        } else {
            // THIS IS THE MOST IMPORTANT DEBUG MESSAGE
            // The JSON response from Shiprocket will tell you exactly why it failed.
            log_message('error', 'Shiprocket authentication failed. API Response: ' . json_encode($data));
            return false;
        }
    }

    private function send_to_shiprocket($order_id, $orderData, $orderItems)
    {
        if (!$this->shiprocket_token) {
            $this->authenticate_shiprocket();
        }

        if (!$this->shiprocket_token) {
            log_message('error', 'Shiprocket authentication failed. Cannot send order.');
            return ['status' => 'error', 'message' => 'Shiprocket authentication failed.'];
        }

        $items = [];
        $total_order_discount = floatval($orderData['coupon_discount'] ?? 0);
        $total_order_amount = floatval($orderData['subtotal'] ?? 1);

        $max_length = 0;
        $max_breadth = 0;
        $max_height = 0;
        $total_weight = 0;

        $shiprocket_categories = [
            'Ezhuthupizhai Giftbox' => 'Special Gift Box',
            'Ezhuthupizhai Bundle' => 'Combo',
            'Oru Tea Sapdalama' => 'Poetry',
            'Kan Simittal' => 'Stories',
            'Kannamma' => 'Poetry',
            'Mittaai Payal' => 'Poetry',
            'Kadha Kelu...' => 'Combo',
            'Theeraa kaadhal...' => 'Combo',
            'Nee kavidhaigalaa' => 'Combo',
            'Highlighter' => 'byob',
            'Bookmarks (Set of 5)' => 'byob',
            'Oru Tea Sapdalama Badge' => 'byob',
            'Message Card' => 'byob',
            'To-Do List Pad (Set of 2)' => 'byob',
            '2025 Calendar Card with Stand' => 'byob',
            'Stickers (Assorted)' => 'byob',
            'Thank You Card' => 'byob'
        ];

        foreach ($orderItems as $item) {
            $product_id = $item['product_id'] ?? null;
            $product = $this->db->get_where('products', ['id' => $product_id])->row_array();
            if (!$product) {
                log_message('warning', 'Product ID ' . $product_id . ' not found in DB. Skipping item.');
                continue;
            }

            $sku = !empty($product['sku']) ? $product['sku'] : "SKU" . $product_id;
            $categories = !empty($product['categories']) ? $product['categories'] : "Default";
            $length_cm = floatval($product['length_cm'] ?? 30);
            $breadth_cm = floatval($product['breadth_cm'] ?? 30);
            $height_cm = floatval($product['height_cm'] ?? 3);
            $weight_kg = floatval($product['weight_kg'] ?? 0.5);

            $max_length = max($max_length, $length_cm);
            $max_breadth = max($max_breadth, $breadth_cm);
            $max_height = max($max_height, $height_cm);
            $total_weight += $weight_kg * intval($item['quantity'] ?? 1);

            $item_total = floatval($item['price_at_order'] ?? 0) * intval($item['quantity'] ?? 1);
            $discount = ($total_order_amount > 0) ? ($total_order_discount / $total_order_amount) * $item_total : 0;

            $items[] = [
                'name' => $item['product_name'] ?? 'Product',
                'sku' => $sku,
                'units' => intval($item['quantity'] ?? 1),
                'selling_price' => floatval($item['price_at_order'] ?? 0),
                'discount' => round($discount, 2),
                'tax' => 0,
                'category' => $categories,
                'hsn' => '1000'
            ];
        }

        $payment_method = strtolower($orderData['payment_method'] ?? 'prepaid');
        $shiprocket_payment_method = (in_array($payment_method, ['cod', 'cod(not paid)'])) ? 'cod' : 'prepaid';

        $payload = [
            "order_id" => "TESTWB" . (string)$order_id,
            // "order_id" => "INEPWB0" . (string)$order_id,
            "channel_order_id" => (string)$order_id,
            // "channel_id" => 8291407,
            "channel_id" => 1234, // channel ID,
            "order_date" => date("Y-m-d H:i:s"),
            "pickup_location" => "work",
            "billing_customer_name" => $orderData['first_name'] ?? 'Customer',
            "billing_last_name" => $orderData['last_name'] ?? '',
            "billing_address" => $orderData['address1'] ?? 'Default Address',
            "billing_city" => $orderData['city'] ?? 'Default City',
            "billing_pincode" => $orderData['zip_code'] ?? '110001',
            "billing_state" => $orderData['state'] ?? 'Default State',
            "billing_country" => "India",
            "billing_email" => $orderData['email'] ?? 'test@example.com',
            "billing_phone" => $orderData['phone'] ?? '9876543210',
            "shipping_charges" => floatval($orderData['delivery_charge'] ?? 0),
            "shipping_currency" => "INR",
            "shipping_is_billing" => true,
            "order_items" => $items,
            "payment_method" => $shiprocket_payment_method,
            "sub_total" => floatval($orderData['subtotal_after_discount'] ?? 0),
            "length" => $max_length > 0 ? $max_length : 30,
            "breadth" => $max_breadth > 0 ? $max_breadth : 30,
            "height" => $max_height > 0 ? $max_height : 3,
            "weight" => $total_weight > 0 ? $total_weight : 0.5,
        ];

        log_message('info', 'Shiprocket API Payload for Order ' . $order_id . ': ' . json_encode($payload));

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->config->item('shiprocket_api_url') . "orders/create/adhoc",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->shiprocket_token
            ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        log_message('info', 'Shiprocket API Response for Order ' . $order_id . ': ' . $response);

        if ($err) {
            log_message('error', 'Shiprocket cURL Error for Order ' . $order_id . ': ' . $err);
            return ['status' => 'error', 'message' => 'Shiprocket request error'];
        }

        $result = json_decode($response, true);

        if ((isset($result['status_code']) && $result['status_code'] == 1) && isset($result['shipment_id'])) {
            $shipment_id = $result['shipment_id'];

            // 🚫 Do NOT auto-assign AWB here
            $this->Order_model->update_shiprocket_ids($order_id, $shipment_id, null);

            log_message('info', "Order {$order_id} created in Shiprocket with Shipment ID: {$shipment_id} (AWB not assigned yet)");

            return [
                'status' => 'success',
                'shiprocket_id' => $shipment_id,
                'shiprocket_response' => $result
            ];
        } else {
            $error_message = $result['message'] ?? 'Unknown Shiprocket API error';
            if (isset($result['errors']) && is_array($result['errors'])) {
                $error_message .= ' - Errors: ' . json_encode($result['errors']);
            }
            log_message('error', 'Shiprocket API Error for Order ' . $order_id . ': ' . $error_message);
            return ['status' => 'error', 'message' => $error_message];
        }
    }

    private function assign_awb($shipment_id, $courier_id)
    {
        $url = $this->config->item('shiprocket_api_url') . "courier/assign/awb";
        $payload = [
            "shipment_id" => $shipment_id,
            "courier_id"  => $courier_id
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->shiprocket_token
            ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            log_message('error', 'Shiprocket AWB Assignment Error: ' . $err);
            return ['status' => 'error', 'message' => $err];
        }

        $result = json_decode($response, true);
        log_message('info', 'AWB Assignment Response: ' . $response);

        if (isset($result['awb_code']) && !empty($result['awb_code'])) {
            return [
                'status' => 'success',
                'awb_code' => $result['awb_code'],
                'courier_company_id' => $result['courier_company_id'] ?? null,
                'courier_name' => $result['courier_name'] ?? null
            ];
        }

        return ['status' => 'error', 'message' => $result['message'] ?? 'Unable to assign AWB'];
    }

    private function get_shiprocket_rates($delivery_postcode, $length, $breadth, $height, $weight, $cod = 1)
    {
        if (!$this->shiprocket_token) {
            $this->authenticate_shiprocket();
        }

        if (!$this->shiprocket_token) {
            return [];
        }

        $pickup_postcode = $this->config->item('shiprocket_pickup_postcode');

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->config->item('shiprocket_api_url') . "courier/serviceability",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->shiprocket_token
            ],
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => null,
            CURLOPT_HTTPGET => true,
        ]);

        $url = $this->config->item('shiprocket_api_url') . "courier/serviceability" .
            "?pickup_postcode=" . urlencode($pickup_postcode) .
            "&delivery_postcode=" . urlencode($delivery_postcode) .
            "&cod=" . intval($cod) .
            "&weight=" . floatval($weight) .
            "&length=" . intval($length) .
            "&breadth=" . intval($breadth) .
            "&height=" . intval($height);

        curl_setopt($curl, CURLOPT_URL, $url);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            log_message('error', 'Shiprocket Rates API Error: ' . $err);
            return [];
        }

        $data = json_decode($response, true);

        if (!isset($data['data']['available_courier_companies']) || empty($data['data']['available_courier_companies'])) {
            return [];
        }

        // sort by rate ascending
        usort($data['data']['available_courier_companies'], function ($a, $b) {
            return $a['rate'] <=> $b['rate'];
        });

        return $data['data']['available_courier_companies'];
    }

    public function ready_to_ship($order_id)
    {
        $this->output->set_content_type('application/json');
        $order = $this->Order_model->get_order_by_id($order_id);

        if (!$order || empty($order['shiprocket_id'])) {
            $this->output->set_status_header(404)->set_output(json_encode([
                'success' => false,
                'message' => 'Order not found or Shiprocket ID missing.'
            ]));
            return;
        }

        $shipment_id = $order['shiprocket_id'];
        $rates = $this->get_shiprocket_rates(
            $order['zip_code'],
            30, // Example length
            30, // Example breadth
            3,  // Example height
            0.5 // Example weight
        );

        if (empty($rates)) {
            $this->output->set_status_header(404)->set_output(json_encode([
                'success' => false,
                'message' => 'No courier services available for this pincode.'
            ]));
            return;
        }

        // Use the cheapest courier
        $courier_id = $rates[0]['courier_company_id'];

        // Step 3: Call assign_awb with both required parameters
        $awb_response = $this->assign_awb($shipment_id, $courier_id);

        if ($awb_response['status'] === 'success') {
            // Step 4: Save AWB in DB and return success
            $this->Order_model->update_awb($order_id, $awb_response['awb_code']);

            $this->output->set_status_header(200)->set_output(json_encode([
                'success' => true,
                'message' => 'AWB assigned successfully',
                'awb_code' => $awb_response['awb_code']
            ]));
        } else {
            // Step 5: Handle failure
            $this->output->set_status_header(500)->set_output(json_encode([
                'success' => false,
                'message' => 'Failed to assign AWB',
                'error' => $awb_response['message'] ?? 'Unknown error'
            ]));
        }
    }

    public function get_tracking_by_shipment_id($shiprocket_id = null)
    {
        $this->output->set_content_type('application/json');

        if (empty($shiprocket_id) || !is_numeric($shiprocket_id)) {
            $this->output->set_status_header(400)->set_output(json_encode(['success' => false, 'message' => 'Invalid or missing Shiprocket ID.']));
            return;
        }

        try {
            if (!$this->shiprocket_token) {
                $this->authenticate_shiprocket();
            }

            if (!$this->shiprocket_token) {
                throw new Exception('Shiprocket authentication failed.');
            }

            $url = $this->config->item('shiprocket_api_url') . "courier/track/shipment/" . $shiprocket_id;

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $this->shiprocket_token
                ],
                CURLOPT_CUSTOMREQUEST => "GET"
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                log_message('error', 'Shiprocket Tracking by Shipment ID cURL Error: ' . $err);
                throw new Exception('Failed to communicate with Shiprocket API.');
            }

            $data = json_decode($response, true);

            // Find the tracking data based on the dynamic key (the shipment ID)
            $tracking_data = $data[$shiprocket_id]['tracking_data'] ?? null;

            if ($tracking_data) {
                // Check for tracking activities
                if (isset($tracking_data['track_status']) && $tracking_data['track_status'] > 0) {
                    // Tracking is successful and there is a status
                    $this->output->set_status_header(200)->set_output(json_encode([
                        'success' => true,
                        'message' => 'Tracking details fetched successfully.',
                        'tracking_details' => $tracking_data
                    ]));
                } else {
                    // Tracking is not available yet, or there's an API-specific error message
                    $error_message = $tracking_data['error'] ?? 'Tracking details are not yet available. Please try again later.';
                    $this->output->set_status_header(200)->set_output(json_encode([
                        'success' => false,
                        'message' => $error_message
                    ]));
                }
            } else {
                // No tracking data found in the response at all (e.g., API returned an error message at the top level)
                $error_message = $data['message'] ?? 'Unable to retrieve tracking data.';
                $this->output->set_status_header(500)->set_output(json_encode([
                    'success' => false,
                    'message' => 'Failed to fetch tracking details from Shiprocket. ' . $error_message
                ]));
            }
        } catch (Exception $e) {
            log_message('error', 'Tracking by Shipment ID Exception: ' . $e->getMessage());
            $this->output->set_status_header(500)->set_output(json_encode([
                'success' => false,
                'message' => 'An internal error occurred: ' . $e->getMessage()
            ]));
        }
    }

    private function _calculate_order_dimensions_and_weight($cartItems)
    {
        $max_length = 0;
        $max_breadth = 0;
        $max_height = 0;
        $total_weight = 0;

        foreach ($cartItems as $item) {
            $product_id = $item['product_id'] ?? null;
            if (!$product_id) continue;

            $product = $this->db->get_where('products', ['id' => $product_id])->row_array();
            if (!$product) continue;

            $length_cm = floatval($product['length_cm'] ?? 0);
            $breadth_cm = floatval($product['breadth_cm'] ?? 0);
            $height_cm = floatval($product['height_cm'] ?? 0);
            $weight_kg = floatval($product['weight_kg'] ?? 0);

            $max_length = max($max_length, $length_cm);
            $max_breadth = max($max_breadth, $breadth_cm);
            $max_height = max($max_height, $height_cm);
            $total_weight += $weight_kg * intval($item['quantity'] ?? 1);
        }

        return [
            'length' => $max_length > 0 ? $max_length : 30,
            'breadth' => $max_breadth > 0 ? $max_breadth : 30,
            'height' => $max_height > 0 ? $max_height : 3,
            'weight' => $total_weight > 0 ? $total_weight : 0.5,
        ];
    }

    public function get_enabled_couriers()
    {
        if (!$this->shiprocket_token) {
            $this->authenticate_shiprocket();
        }

        $url = "https://apiv2.shiprocket.in/v1/external/courier/courierListWithCounts?type=active";

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->shiprocket_token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            if (!empty($result)) {
                echo json_encode([
                    'status' => 'success',
                    'enabled_couriers' => $result
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No enabled couriers found'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function get_enabled_courier_charge()
    {
        // CORS headers
        $this->output
            ->set_header("Access-Control-Allow-Origin: *")
            ->set_header("Access-Control-Allow-Headers: Content-Type, Authorization")
            ->set_header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $pickup_pincode   = $data['pickup_pincode'] ?? '';
        $delivery_pincode = $data['delivery_pincode'] ?? '';
        $weight           = $data['weight'] ?? '';
        $cod              = $data['cod'] ?? 0;

        if (empty($pickup_pincode) || empty($delivery_pincode) || empty($weight)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required fields'
            ]);
            return;
        }

        if (!$this->shiprocket_token) {
            $this->authenticate_shiprocket();
        }

        $client = new \GuzzleHttp\Client();

        try {
            // 1. Enabled couriers
            $enabledResponse = $client->get("https://apiv2.shiprocket.in/v1/external/courier/courierListWithCounts?type=active", [
                'headers' => ['Authorization' => 'Bearer ' . $this->shiprocket_token]
            ]);
            $enabledData = json_decode($enabledResponse->getBody(), true);
            $enabledCouriers = $enabledData['courier_data'] ?? [];

            // Debugging: if structure unexpected, log it (CodeIgniter)
            if (!is_array($enabledCouriers)) {
                log_message('error', 'Unexpected enabled couriers structure: ' . print_r($enabledData, true));
                $enabledCouriers = [];
            }

            // 2. Serviceable couriers with rates
            $url = "https://apiv2.shiprocket.in/v1/external/courier/serviceability/?" .
                "pickup_postcode={$pickup_pincode}&delivery_postcode={$delivery_pincode}" .
                "&weight={$weight}&cod={$cod}";

            $serviceResponse = $client->get($url, [
                'headers' => ['Authorization' => 'Bearer ' . $this->shiprocket_token]
            ]);
            $serviceData = json_decode($serviceResponse->getBody(), true);
            $serviceable = $serviceData['data']['available_courier_companies'] ?? [];

            if (!is_array($serviceable)) {
                log_message('error', 'Unexpected serviceability structure: ' . print_r($serviceData, true));
                $serviceable = [];
            }

            // Build lookup maps
            $chargesById = [];
            $chargesByNameLower = [];
            foreach ($serviceable as $svc) {
                $svcId = $svc['courier_company_id'] ?? null; // serviceability uses this key
                $svcName = trim($svc['courier_name'] ?? '');
                $rate = $svc['rate'] ?? null;

                if ($svcId !== null) {
                    $chargesById[(int)$svcId] = $rate;
                }
                if ($svcName !== '') {
                    $chargesByNameLower[strtolower($svcName)] = $rate;
                }
            }

            // Merge enabled couriers with charges (id-first, then name fallback)
            $result = [];
            foreach ($enabledCouriers as $c) {
                // possible key names in enabled list: 'id', 'courier_company_id', etc.
                $id = $c['id'] ?? ($c['courier_company_id'] ?? null);
                // possible name keys
                $name = $c['courier_name'] ?? ($c['name'] ?? ($c['company_name'] ?? 'Unknown Courier'));

                $deliveryCharge = null;
                if ($id !== null && array_key_exists((int)$id, $chargesById)) {
                    $deliveryCharge = $chargesById[(int)$id];
                } else {
                    $lcname = strtolower(trim($name));
                    if ($lcname !== '' && array_key_exists($lcname, $chargesByNameLower)) {
                        $deliveryCharge = $chargesByNameLower[$lcname];
                    }
                }

                $result[] = [
                    'courier_id'      => $id,
                    'courier_name'    => $name,
                    'delivery_charge' => $deliveryCharge // null means not serviceable for this route
                ];
            }

            echo json_encode([
                'status' => 'success',
                'enabled_couriers' => $result
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function track()
    {
        $headers = array_change_key_case($this->input->request_headers(), CASE_LOWER);
        $receivedToken = $headers['x-api-key'] ?? null;
        $expectedToken = $this->config->item('shiprocket_token');

        if ($receivedToken !== $expectedToken) {
            log_message('error', '❌ Invalid webhook token');
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode(['error' => 'Unauthorized']));
        }
        $payload = json_decode($this->input->raw_input_stream, true);
        log_message('info', '📦 Webhook payload received: ' . json_encode($payload));

        if (empty($payload['order_id'])) {
            log_message('error', '❌ order_id missing in webhook');
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['error' => 'Invalid payload']));
        }
        $order_id = preg_replace('/\D/', '', $payload['order_id']);
        $shipmentStatusMap = [
            6  => 'Shipped',
            7  => 'Delivered',
            8  => 'Canceled',
            9  => 'RTO Initiated',
            10 => 'RTO Delivered',
            12 => 'Lost',
            13 => 'Pickup Error',
            14 => 'RTO Acknowledged',
            15 => 'Pickup Rescheduled',
            16 => 'Cancellation Requested',
            17 => 'Out For Delivery',
            18 => 'In Transit',
            19 => 'Out For Pickup',
            20 => 'Pickup Exception',
            21 => 'Undelivered',
            22 => 'Delayed',
            23 => 'Partial Delivered',
            24 => 'Destroyed',
            25 => 'Damaged',
            26 => 'Fulfilled',
            27 => 'Pickup Booked',
            38 => 'Reached Destination Hub',
            39 => 'Misrouted',
            40 => 'RTO Not Delivered',
            41 => 'RTO Out For Delivery',
            42 => 'Picked Up',
            43 => 'Self Fulfilled',
            44 => 'Disposed Off',
            45 => 'Cancelled Before Dispatched',
            46 => 'RTO In Transit',
            47 => 'QC Failed',
            48 => 'Reached Warehouse',
            49 => 'Custom Cleared',
            50 => 'In Flight',
            51 => 'Handover to Courier',
            52 => 'Shipment Booked',
            54 => 'In Transit Overseas',
            55 => 'Connection Aligned',
            56 => 'Reached Overseas Warehouse',
            57 => 'Custom Cleared Overseas',
            59 => 'Box Packing',
            60 => 'FC Allocated',
            61 => 'Picklist Generated',
            62 => 'Ready To Pack',
            63 => 'Packed',
            67 => 'FC Manifest Generated',
            71 => 'Handover Exception',
            72 => 'Packed Exception',
            75 => 'RTO Lock',
            76 => 'Untraceable',
            77 => 'Issue Related To Recipient',
            78 => 'Reached Back At Seller City'
        ];

        $finalStatusMap = [
            // Normal flow
            'PROCESSING'                  => 'Processing',
            'CONFIRMED'                   => 'Confirmed',
            'PICKUP BOOKED'               => 'Pickup Scheduled',
            'OUT FOR PICKUP'              => 'Pickup Scheduled',
            'PICKUP SCHEDULED'            => 'Pickup Scheduled',
            'PICKED UP'                   => 'Shipped',
            'SHIPMENT BOOKED'             => 'Shipped',
            'SHIPPED'                     => 'Shipped',
            'IN TRANSIT'                  => 'In Transit',
            'REACHED DESTINATION HUB'     => 'In Transit',
            'CONNECTION ALIGNED'          => 'In Transit',
            'IN FLIGHT'                   => 'In Transit',
            'IN TRANSIT OVERSEAS'         => 'In Transit',
            'REACHED OVERSEAS WAREHOUSE'  => 'In Transit',
            'CUSTOM CLEARED'              => 'In Transit',
            'CUSTOM CLEARED OVERSEAS'     => 'In Transit',
            'OUT FOR DELIVERY'            => 'Out For Delivery',
            'OUT FOR PICKUP'              => 'Out For Pickup',
            'DELIVERED'                   => 'Delivered',
            'FULFILLED'                   => 'Delivered',
            'UNDELIVERED'                 => 'Delivery Failed',
            'DELAYED'                     => 'Delivery Failed',
            'PICKUP ERROR'                => 'Delivery Failed',
            'PICKUP EXCEPTION'            => 'Delivery Failed',
            'HANDOVER EXCEPTION'          => 'Delivery Failed',
            'PACKED EXCEPTION'            => 'Delivery Failed',
            'UNTRACEABLE'                 => 'Delivery Failed',
            'MISROUTED'                   => 'Delivery Failed',
            'ISSUE RELATED TO RECIPIENT'  => 'Delivery Failed',
            'LOST'                        => 'Delivery Failed',
            'PARTIAL DELIVERED'           => 'Delivery Failed',
            'DESTROYED'                    => 'Delivery Failed',
            'DAMAGED'                      => 'Delivery Failed',
            'DISPOSED OFF'                 => 'Delivery Failed',
            'QC FAILED'                    => 'Delivery Failed',
            'RTO INITIATED'               => 'RTO Initiated',
            'RTO ACKNOWLEDGED'            => 'RTO Initiated',
            'RTO IN TRANSIT'              => 'RTO In Transit',
            'RTO OUT FOR DELIVERY'        => 'RTO Out For Delivery',
            'RTO NOT DELIVERED'           => 'RTO In Transit',
            'RTO DELIVERED'               => 'RTO Delivered',
            'RTO LOCK'                    => 'RTO In Transit',
            'REACHED BACK AT SELLER CITY' => 'RTO In Transit',
            'CANCELED'                    => 'Cancelled',
            'CANCELLATION REQUESTED'      => 'Cancelled',
            'CANCELLED BEFORE DISPATCHED' => 'Cancelled'
        ];

        $updateData = [
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($payload['awb'])) {
            $updateData['awb_code'] = $payload['awb'];
        }

        if (!empty($payload['sr_order_id'])) {
            $updateData['shiprocket_id'] = $payload['sr_order_id'];
        }
        $shipmentStatusId = $payload['shipment_status_id'] ?? null;
        $shipmentStatus   = $payload['shipment_status'] ?? '';
        $currentStatus    = $payload['current_status'] ?? '';

        if ($shipmentStatusId && isset($shipmentStatusMap[$shipmentStatusId])) {
            $rawStatus = $shipmentStatusMap[$shipmentStatusId];
        } elseif (!empty($currentStatus)) {
            $rawStatus = ucfirst(strtolower($currentStatus));
        } else {
            $rawStatus = 'Processing';
        }
        $normalizedKey = strtoupper(trim($rawStatus));
        $finalStatus = $finalStatusMap[$normalizedKey] ?? 'Processing';
        $updateData['status'] = $finalStatus;

        $this->db->where('id', $order_id)->update('orders', $updateData);

        if ($this->db->affected_rows() > 0) {
            log_message(
                'info',
                "✅ Order {$order_id} updated. Shiprocket Payload Status Raw: {$rawStatus}, DB Mapping  Status Final: {$finalStatus}. Shiprocket Payload: " . json_encode($payload)
            );
        } else {
            log_message(
                'error',
                "❌ Failed to update Order {$order_id}. Shiprocket Payload Status Raw: {$rawStatus}, DB Mapping  Status Final: {$finalStatus}. Shiprocket Payload: " . json_encode($payload)
            );
        }
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode(['success' => true]));
    }

    public function get_shipping_info()
    {
        $method = $this->input->method();

        if ($method === 'get' || $method === 'head' || $method === 'options') {
            // Return a minimal, successful response so the platform knows the URL is live.
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(['success' => true, 'message' => 'Endpoint is live. Ready for POST data.']));
        }


        if ($this->input->method() !== 'post') {
            return $this->output
                ->set_status_header(405)
                ->set_output(json_encode(['success' => false, 'message' => 'Method Not Allowed.']));
        }

        // Get the JSON input payload from the external platform
        $input = json_decode($this->input->raw_input_stream, true);

        // --- 2. Validate and Extract Required Data ---
        $address = $input['address'] ?? [];
        $cart_items = $input['cart_items'] ?? [];

        $delivery_postcode = $address['pincode'] ?? null;
        // Check for COD requested (assuming the platform sends a 'payment_method' key if COD is selected)
        $cod_requested = (isset($input['payment_method']) && strtolower($input['payment_method']) === 'cod') ? 1 : 0;

        if (empty($delivery_postcode) || empty($cart_items)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['success' => false, 'message' => 'Missing Pincode or Cart Items.']));
        }

        // --- 3. Calculate Total Dimensions and Weight ---

        $combined_length = 0;
        $combined_breadth = 0;
        $combined_height = 0;
        $combined_weight = 0;

        // Logic: Use max L/B/H found in cart, and sum the total weight.
        foreach ($cart_items as $item) {
            $product = $item['product'] ?? [];
            $quantity = (int)($item['quantity'] ?? 1);
            $item_weight = (float)($product['weight_kg'] ?? 0);

            $combined_weight += $item_weight * $quantity;

            // For L/B/H, take the max of all products for the box size
            $combined_length = max($combined_length, (float)($product['length_cm'] ?? 0));
            $combined_breadth = max($combined_breadth, (float)($product['breadth_cm'] ?? 0));
            $combined_height = max($combined_height, (float)($product['height_cm'] ?? 0));
        }

        // --- 4. Call Shiprocket Function ---

        $available_couriers = $this->get_shiprocket_rates(
            $delivery_postcode,
            intval($combined_length),
            intval($combined_breadth),
            intval($combined_height),
            floatval($combined_weight),
            $cod_requested
        );

        // --- 5. Format Output ---

        $shipping_fee = 0.00;
        $cod_fee = 0.00;
        $serviceable = !empty($available_couriers);
        $cod_serviceable = false;

        if ($serviceable) {
            // Rates are sorted by price (lowest is first element)
            $lowest_rate_courier = $available_couriers[0];
            $shipping_fee = (float)($lowest_rate_courier['rate'] ?? 0.00);

            // Determine COD Serviceability/Fee
            if ($cod_requested === 1) {
                // If a COD rate was successfully returned, the lowest rate courier supports COD.
                $cod_serviceable = true;
                $cod_fee = (float)($lowest_rate_courier['cod_charges'] ?? 0.00);
            } else {
                // For prepaid requests, check if any courier supports COD generally
                foreach ($available_couriers as $courier) {
                    if ($courier['is_cod'] ?? false) {
                        $cod_serviceable = true;
                        break;
                    }
                }
            }
        }

        // Construct the required JSON response structure
        $response_data = [
            'success' => true,
            'serviceable' => $serviceable,
            'cod_serviceable' => $cod_serviceable,
            'shipping_fee' => number_format($shipping_fee, 2, '.', ''),
            'cod_fee' => number_format($cod_fee, 2, '.', ''),
            'message' => $serviceable ? 'Shipping available.' : 'Shipping not available for this Pincode.'
        ];

        // Send the structured JSON response
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response_data));
    }
}
