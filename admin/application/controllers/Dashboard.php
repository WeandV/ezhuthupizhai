<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Dompdf\Dompdf;
use Dompdf\Options;

class Dashboard extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');

        if (!$this->session->userdata('logged_in')) {
            redirect('login');
        }

        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('admin/Order_model');
        $this->load->model('User_model');
        $this->load->model('admin/Inventory_model');
        $this->load->model('admin/Invoice_model');
        $this->load->library('form_validation');

        $this->config->load('shiprocket', TRUE);
        if (!$this->session->userdata('shiprocket_token') && ENVIRONMENT !== 'testing') {
            $this->_authenticate_shiprocket();
        }
    }

    public function index()
    {
        $this->load->database();
        $total_website_revenue_query = $this->db->select_sum('final_total')->get('orders');
        $total_website_revenue = $total_website_revenue_query->row()->final_total ?? 0;

        $current_month_website_revenue_query = $this->db->select_sum('final_total')
            ->where('MONTH(created_at)', date('m'))
            ->where('YEAR(created_at)', date('Y'))
            ->get('orders');
        $current_month_website_revenue = $current_month_website_revenue_query->row()->final_total ?? 0;

        $start_of_week = date('Y-m-d', strtotime('monday this week'));
        $end_of_week = date('Y-m-d', strtotime('sunday this week'));

        $current_week_website_revenue_query = $this->db->select_sum('final_total')
            ->where('DATE(created_at) >=', $start_of_week)
            ->where('DATE(created_at) <=', $end_of_week)
            ->get('orders');
        $current_week_website_revenue = $current_week_website_revenue_query->row()->final_total ?? 0;

        // --- In-Store (Invoice) Sales Metrics (from 'invoices' table) ---

        // UPDATED: Queries now sum 'sub_total' and ignore 'recipient_type'.
        $total_instore_revenue_query = $this->db->select_sum('sub_total')
            ->where('payment_status', 'Paid') // Only count paid invoices
            ->get('invoices');
        $total_instore_revenue = $total_instore_revenue_query->row()->sub_total ?? 0;

        $current_month_instore_revenue_query = $this->db->select_sum('sub_total')
            ->where('MONTH(invoice_date)', date('m'))
            ->where('YEAR(invoice_date)', date('Y'))
            ->where('payment_status', 'Paid')
            ->get('invoices');
        $current_month_instore_revenue = $current_month_instore_revenue_query->row()->sub_total ?? 0;

        $current_week_instore_revenue_query = $this->db->select_sum('sub_total')
            ->where('DATE(invoice_date) >=', $start_of_week)
            ->where('DATE(invoice_date) <=', $end_of_week)
            ->where('payment_status', 'Paid')
            ->get('invoices');

        $current_week_instore_revenue = $current_week_instore_revenue_query->row()->sub_total ?? 0;

        // --- Combined (Website + In-Store) Metrics ---

        $combined_total_revenue = $total_website_revenue + $total_instore_revenue;
        $combined_current_month_revenue = $current_month_website_revenue + $current_month_instore_revenue;
        $combined_current_week_revenue = $current_week_website_revenue + $current_week_instore_revenue;

        // --- Order Status Metrics (from 'orders' table) ---

        $total_orders = $this->db->count_all('orders');
        $processed_orders = $this->db->where('status', 'Processing')->count_all_results('orders');
        $shipped_orders = $this->db->where('status', 'Shipped')->count_all_results('orders');
        $delivered_orders = $this->db->where('status', 'Delivered')->count_all_results('orders');
        $cancelled_orders = $this->db->where('status', 'Cancelled')->count_all_results('orders');
        $on_hold_orders = $this->db->where('status', 'On Hold')->count_all_results('orders');

        // --- Other Metrics & Charts (existing logic) ---

        $daily_sales_query = $this->db->select('DATE(created_at) AS sales_date, SUM(final_total) AS daily_revenue, COUNT(id) AS daily_orders')
            ->group_by('sales_date')
            ->order_by('sales_date', 'DESC')
            ->get('orders');
        $daily_sales = $daily_sales_query->result_array();

        $today_invoices_query = $this->db->select('SUM(total_amount) AS today_revenue, COUNT(id) AS today_count')
            ->where('DATE(created_at)', date('Y-m-d'))
            ->get('invoices');

        $today_invoices_data = $today_invoices_query->row();
        $today_invoices_revenue = $today_invoices_data->today_revenue ?? 0;
        $today_invoices_count = $today_invoices_data->today_count ?? 0;

        $invoices_daily_sales_query = $this->db->select('DATE(invoice_date) AS sales_date, payment_status, SUM(total_amount) AS daily_revenue, COUNT(id) AS daily_invoices')
            ->group_by('sales_date, payment_status')
            ->order_by('sales_date', 'DESC')
            ->order_by('payment_status', 'ASC')
            ->get('invoices');
        $invoices_daily_sales = $invoices_daily_sales_query->result_array();

        // Chart 1: Daily Revenue Data (Last 30 days)
        $daily_orders_query = $this->db->select('DATE(created_at) as sales_date, SUM(final_total) as daily_revenue')
            ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
            ->group_by('sales_date')
            ->order_by('sales_date', 'ASC')
            ->get('orders');
        $daily_orders_sales = $daily_orders_query->result_array();

        $daily_invoices_query = $this->db->select('DATE(invoice_date) as sales_date, SUM(sub_total) as daily_revenue')
            ->where('invoice_date >=', date('Y-m-d', strtotime('-30 days')))
            ->where('payment_status', 'paid')
            ->group_by('sales_date')
            ->order_by('sales_date', 'ASC')
            ->get('invoices');
        $daily_invoices_sales = $daily_invoices_query->result_array();

        $combined_sales = [];
        foreach ($daily_orders_sales as $row) {
            $combined_sales[$row['sales_date']] = ($combined_sales[$row['sales_date']] ?? 0) + $row['daily_revenue'];
        }
        foreach ($daily_invoices_sales as $row) {
            $combined_sales[$row['sales_date']] = ($combined_sales[$row['sales_date']] ?? 0) + $row['daily_revenue'];
        }

        $this->db->select('product_id, quantity');
        $order_items = $this->db->get_compiled_select('order_items');

        $this->db->select('product_id, quantity');
        $invoice_items = $this->db->get_compiled_select('invoice_items');

        $combined_items_query = "($order_items) UNION ALL ($invoice_items)";

        $this->db->select('t1.product_id, SUM(t1.quantity) as total_quantity_sold, t2.name as product_name')
            ->from("($combined_items_query) as t1", FALSE)
            ->join('products as t2', 't1.product_id = t2.id')
            ->group_by('t1.product_id')
            ->order_by('total_quantity_sold', 'DESC')
            ->limit(10);

        $top_products_query = $this->db->get();
        $top_products = $top_products_query->result_array();

        // Chart 3: Invoice Status Breakdown
        $this->db->select('payment_status, COUNT(id) as status_count');
        $this->db->group_by('payment_status');
        $status_breakdown = $this->db->get('invoices')->result_array();

        // Final data array
        $data = [
            // Website Metrics
            'total_website_revenue' => $total_website_revenue,
            'current_month_website_revenue' => $current_month_website_revenue,
            'current_week_website_revenue' => $current_week_website_revenue,

            // In-Store Metrics
            'total_instore_revenue' => $total_instore_revenue,
            'current_month_instore_revenue' => $current_month_instore_revenue,
            'current_week_instore_revenue' => $current_week_instore_revenue,

            // Combined Metrics
            'combined_total_revenue' => $combined_total_revenue,
            'combined_current_month_revenue' => $combined_current_month_revenue,
            'combined_current_week_revenue' => $combined_current_week_revenue,

            // Order Status
            'total_orders' => $total_orders,
            'processed_orders' => $processed_orders,
            'shipped_orders' => $shipped_orders,
            'delivered_orders' => $delivered_orders,
            'cancelled_orders' => $cancelled_orders,
            'on_hold_orders' => $on_hold_orders,

            // Other existing data
            'daily_sales' => $daily_sales,
            'today_invoices_revenue' => $today_invoices_revenue,
            'today_invoices_count' => $today_invoices_count,
            'invoices_daily_sales' => $invoices_daily_sales,
            'combined_sales' => $combined_sales,
            'top_products' => $top_products,
            'status_breakdown' => $status_breakdown,
        ];

        $data['viewpage'] = 'dashboard/dashboard';
        $this->load->view('welcome_message', $data);
    }

    public function orders()
    {
        $data['title'] = 'Orders List';
        $data['orders'] = $this->Order_model->get_all_orders();

        $order_id = $this->uri->segment(3);
        $data['selected_order'] = null;

        if ($order_id) {
            $data['selected_order'] = $this->Order_model->get_order_with_items($order_id);
        }

        $data['viewpage'] = 'dashboard/orders';
        $this->load->view('welcome_message', $data);
    }

    public function update_order_status()
    {
        if (!$this->input->is_ajax_request() || !$this->session->userdata('logged_in')) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
            return;
        }

        $input_data = json_decode($this->input->raw_input_stream, true);

        $order_id = $input_data['order_id'] ?? null;
        $new_status = $input_data['order_status'] ?? null;

        if (empty($order_id) || empty($new_status)) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['success' => false, 'message' => 'Missing order ID or status.']));
            return;
        }

        $allowed_statuses = [
            'Processing',
            'Confirmed',
            'Pickup Scheduled',
            'Shipped',
            'In Transit',
            'Out For Delivery',
            'Out For Pickup',
            'Delivered',
            'RTO Initiated',
            'RTO In Transit',
            'RTO Out For Delivery',
            'RTO Delivered',
            'Cancelled',
            'On Hold',
            'Delivery Failed'
        ];

        if (!in_array($new_status, $allowed_statuses)) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['success' => false, 'message' => 'Invalid status provided.']));
            return;
        }

        $result = $this->Order_model->update_order_status($order_id, $new_status);

        if ($result || $this->db->affected_rows() === 0) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(['success' => true, 'message' => 'Order status updated successfully.']));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['success' => false, 'message' => 'Failed to update database.']));
        }
    }


    public function view_invoice($order_id)
    {
        if (!$order_id || !is_numeric($order_id)) {
            show_404();
        }
        $data['order'] = $this->Order_model->get_order_with_items($order_id);
        if (empty($data['order'])) {
            show_404();
        }
        $data['title'] = 'Invoice for Order #' . $order_id;
        $data['viewpage'] = 'dashboard/view-invoice';
        $this->load->view('welcome_message', $data);
    }

    public function download_shipping_label($order_id)
    {
        ob_start();

        if (!$order_id || !is_numeric($order_id)) {
            ob_end_clean();
            show_error('Invalid order ID.', 404);
            return;
        }

        try {
            require_once APPPATH . 'third_party/dompdf/autoload.inc.php';
            $order_data = $this->Order_model->get_order_with_items($order_id);

            if (empty($order_data)) {
                ob_end_clean();
                show_error('Order not found.', 404);
                return;
            }

            $data['invoice'] = (object) [
                'invoice_number' => $order_data->id ?? 'N/A',
                'recipient_name' => ($order_data->first_name ?? '') . ' ' . ($order_data->last_name ?? ''),
                'address_line1' => $order_data->address1 ?? 'N/A',
                'address_line2' => $order_data->address2 ?? '',
                'city' => $order_data->city ?? 'N/A',
                'state' => $order_data->state ?? 'N/A',
                'pincode' => $order_data->zip_code ?? 'N/A',
                'phone' => $order_data->phone ?? 'N/A',
                'email' => $order_data->email ?? 'N/A',
                'invoice_date' => $order_data->created_at ?? date('Y-m-d'),
                'payment_method' => $order_data->payment_method,
                'coupon_discount' => $order_data->coupon_discount ?? 0,
                'delivery_charge' => $order_data->delivery_charge ?? 0,
                'total_amount' => $order_data->final_total ?? 0,
            ];

            $data['items'] = $order_data->items ?? [];

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $html = $this->load->view('invoices/shipping_label_pdf', $data, true);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            ob_end_clean();

            $filename = 'shipping_label_' . $order_id;
            // This is the line that keeps the download functionality:
            $dompdf->stream($filename . '.pdf', array("Attachment" => true));
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: text/plain');
            echo "An error occurred during PDF generation:\n\n";
            echo "Message: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . "\n";
            echo "Line: " . $e->getLine() . "\n";
            echo "Trace: \n" . $e->getTraceAsString();
            exit;
        }
    }

    public function view_shipping_label($order_id)
    {
        ob_start();

        if (!$order_id || !is_numeric($order_id)) {
            ob_end_clean();
            show_error('Invalid order ID.', 404);
            return;
        }

        try {
            require_once APPPATH . 'third_party/dompdf/autoload.inc.php';
            $order_data = $this->Order_model->get_order_with_items($order_id);

            if (empty($order_data)) {
                ob_end_clean();
                show_error('Order not found.', 404);
                return;
            }

            $data['invoice'] = (object) [
                'invoice_number' => $order_data->id ?? 'N/A',
                'recipient_name' => ($order_data->first_name ?? '') . ' ' . ($order_data->last_name ?? ''),
                'address_line1' => $order_data->address1 ?? 'N/A',
                'address_line2' => $order_data->address2 ?? '',
                'city' => $order_data->city ?? 'N/A',
                'state' => $order_data->state ?? 'N/A',
                'pincode' => $order_data->zip_code ?? 'N/A',
                'phone' => $order_data->phone ?? 'N/A',
                'email' => $order_data->email ?? 'N/A',
                'invoice_date' => $order_data->created_at ?? date('Y-m-d'),
                'payment_method' => $order_data->payment_method,
                'coupon_discount' => $order_data->coupon_discount ?? 0,
                'delivery_charge' => $order_data->delivery_charge ?? 0,
                'total_amount' => $order_data->final_total ?? 0,
            ];

            $data['items'] = $order_data->items ?? [];

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $html = $this->load->view('invoices/shipping_label_pdf', $data, true);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            ob_end_clean();

            $filename = 'shipping_label_' . $order_id;
            // This is the line you need to change for viewing in a new tab:
            $dompdf->stream($filename . '.pdf', array("Attachment" => false));
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: text/plain');
            echo "An error occurred during PDF generation:\n\n";
            echo "Message: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . "\n";
            echo "Line: " . $e->getLine() . "\n";
            echo "Trace: \n" . $e->getTraceAsString();
            exit;
        }
    }

    public function customers()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }

        $data['title'] = 'Users';
        $data['users'] = $this->User_model->get_all_users();
        $data['viewpage'] = 'dashboard/customers';
        $this->load->view('welcome_message', $data);
    }

    public function user_details($user_id)
    {
        $data['title'] = 'User Details';
        $data['user'] = $this->User_model->get_user_details($user_id);

        if (empty($data['user'])) {
            show_404();
        }

        $data['orders'] = $this->User_model->get_user_orders($user_id);

        $data['viewpage'] = 'dashboard/customer_details';
        $this->load->view('welcome_message', $data);
    }

    public function inventory()
    {
        $data['title'] = 'inventory';
        $data['inventory'] = $this->Inventory_model->get_inventory();
        $data['products'] = $this->Inventory_model->get_all_products_with_stock();
        $data['viewpage'] = 'dashboard/inventory';
        $this->load->view('welcome_message', $data);
    }

    public function update_stock_ajax()
    {
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }

        $product_id = $this->input->post('product_id');
        $new_stock = $this->input->post('new_stock');
        if (empty($product_id) || !is_numeric($new_stock)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
            return;
        }
        $update_result = $this->Inventory_model->update_stock($product_id, $new_stock);
        if ($update_result > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Inventory updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update inventory or no changes were made.']);
        }
    }

    //in store sales
    public function direct_sales()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }

        $data['title'] = 'Invoice Generator';
        $data['next_invoice_number'] = $this->get_next_invoice_number();
        $data['viewpage'] = 'in-store/direct-sales';
        $this->load->view('welcome_message', $data);
    }

    public function get_next_invoice_number()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }

        $this->db->select_max('invoice_number', 'max_invoice_number');
        $query = $this->db->get('invoices');
        $row = $query->row();

        if ($row && $row->max_invoice_number) {
            $lastNumber = intval(substr($row->max_invoice_number, 5));
            $newNumber = $lastNumber + 1;
            $nextInvoiceNumber = 'INVEP' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        } else {
            $nextInvoiceNumber = 'INVEP001';
        }
        return $nextInvoiceNumber;
    }

    public function get_products()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }
        header('Content-Type: application/json');
        $products = $this->Invoice_model->get_all_products();
        echo json_encode($products);
    }

    public function get_recipients()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }

        $type = $this->input->get('type');
        $query = $this->input->get('query');
        $recipients = [];

        if ($type === 'customer') {
            $this->db->like('first_name', $query);
            $this->db->or_like('phone', $query);
            $recipients = $this->db->select('id, first_name as name, phone, email')->get('users')->result_array();
        } elseif ($type === 'vendor') {
            $this->db->like('name', $query);
            $this->db->or_like('phone', $query);
            $recipients = $this->db->select('id, name, phone, email')->get('vendors')->result_array();
        }

        echo json_encode($recipients);
    }

    public function get_recipient_details()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }

        $type = $this->input->get('type');
        $id = $this->input->get('id');
        $details = null;

        if ($type === 'customer') {
            $this->db->where('user_id', $id);
            $this->db->join('users', 'users.id = user_addresses.user_id');
            $details = $this->db->get('user_addresses')->row_array();
        } elseif ($type === 'vendor') {
            $this->db->where('id', $id);
            $details = $this->db->get('vendors')->row_array();
        }

        echo json_encode($details);
    }

    public function save_invoice()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }
        header('Content-Type: application/json');

        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        if (empty($data['invoice_date']) || empty($data['customer_name']) || empty($data['items'])) {
            echo json_encode(['success' => false, 'message' => 'Mandatory fields are missing.']);
            return;
        }

        $result = $this->Invoice_model->save_full_invoice($data);

        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Invoice saved successfully.']);
        } else {
            log_message('error', 'Failed to save invoice: ' . $result['message']);
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
    }

    public function invoice_list()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }
        $data['invoices'] = $this->Invoice_model->get_invoices();
        $data['title'] = 'Invoices';
        $data['viewpage'] = 'in-store/invoice-list';
        $this->load->view('welcome_message', $data);
    }

    public function update_invoice_status()
    {
        if (!$this->input->is_ajax_request() || !$this->session->userdata('logged_in')) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(403) // Forbidden
                ->set_output(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
            return;
        }

        $input_data = json_decode($this->input->raw_input_stream, true);

        $invoice_id = $input_data['invoice_id'] ?? null;
        $new_status = $input_data['payment_status'] ?? null;

        if (empty($invoice_id) || empty($new_status)) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['success' => false, 'message' => 'Missing invoice ID or status.']));
            return;
        }

        $allowed_statuses = ['Unpaid', 'Partially Paid', 'Paid'];
        if (!in_array($new_status, $allowed_statuses)) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['success' => false, 'message' => 'Invalid status provided.']));
            return;
        }

        $this->load->database();

        $data = [
            'payment_status' => $new_status,
        ];

        $this->db->where('id', $invoice_id);
        $this->db->update('invoices', $data);

        if ($this->db->affected_rows() >= 0) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(['success' => true, 'message' => 'Invoice status updated successfully.']));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['success' => false, 'message' => 'Failed to update database.']));
        }
    }

    public function download_dealer_invoice($invoice_id)
    {
        ob_start();

        if (empty($invoice_id) || !is_numeric($invoice_id)) {
            ob_end_clean();
            show_error('Invalid invoice ID.', 404);
            return;
        }

        try {
            require_once APPPATH . 'third_party/dompdf/autoload.inc.php';

            $this->load->database();

            // Fetch invoice details from the 'invoices' table
            $invoice = $this->db->get_where('invoices', ['id' => $invoice_id])->row();

            // Fetch invoice items and join with 'products' to get book titles
            $this->db->select('ii.*, p.name AS book_title, ii.price AS unit_price');
            $this->db->from('invoice_items ii');
            $this->db->join('products p', 'p.id = ii.product_id', 'inner');
            $this->db->where('ii.invoice_id', $invoice_id);
            $items = $this->db->get()->result();

            if (empty($invoice)) {
                ob_end_clean();
                show_error('Invoice not found.', 404);
                return;
            }

            // Map database columns to view variables
            $data['invoice'] = (object)[
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date,
                'customer_name' => ($invoice->customer_name ?? ''),
                'address_line1' => $invoice->billing_address1 ?? '',
                'address_line2' => $invoice->billing_address2 ?? '',
                'city' => $invoice->city ?? '',
                'state' => $invoice->state ?? '',
                'pincode' => $invoice->pincode ?? '',
                'phone' => $invoice->phone_number ?? '',
                'email' => $invoice->customer_email ?? '',
                'payment_status' => $invoice->payment_status,
                'sub_total' => $invoice->sub_total,
                'discount_percentage' => $invoice->discount_percentage,
                'discount_amount' => $invoice->discount_amount,
                'flat_discount' => $invoice->flat_discount,
                'delivery_charge' => $invoice->delivery_charge,
                'total_amount' => $invoice->total_amount,
            ];
            $data['items'] = $items;

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            $html = $this->load->view('invoices/dealer_invoice', $data, true);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            ob_end_clean();

            $action = $this->input->get('action');
            $filename = 'Invoice-' . $invoice->invoice_number;

            if ($action == 'download') {
                $dompdf->stream($filename . '.pdf', ['Attachment' => 1]);
            } else {
                $dompdf->stream($filename . '.pdf', ['Attachment' => 0]);
            }
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: text/plain');
            echo "An error occurred during PDF generation:\n\n";
            echo "Message: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . "\n";
            echo "Line: " . $e->getLine() . "\n";
            echo "Trace: \n" . $e->getTraceAsString();
            exit;
        }
    }

    public function dealer_list()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }

        $data['title'] = 'Dealer List';
        $data['vendors'] = $this->Invoice_model->get_dealer();
        $data['viewpage'] = 'in-store/dealer-list';
        $this->load->view('welcome_message', $data);
    }

    public function add_vendor()
    {
        $this->form_validation->set_rules('name', 'Vendor Name', 'required');
        $this->form_validation->set_rules('contact_person', 'Contact Person Name', 'required');
        $this->form_validation->set_rules('phone', 'Phone Number', 'required|numeric|exact_length[10]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('address_line1', 'Address Line 1', 'required');
        $this->form_validation->set_rules('city', 'City', 'required');
        $this->form_validation->set_rules('state', 'State', 'required');
        $this->form_validation->set_rules('country', 'Country', 'required');
        $this->form_validation->set_rules('pincode', 'Pincode', 'required|numeric|exact_length[6]');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode(['status' => 'error', 'errors' => validation_errors()]);
        } else {
            $data = [
                'name'           => $this->input->post('name'),
                'contact_person' => $this->input->post('contact_person'),
                'phone'          => $this->input->post('phone'),
                'email'          => $this->input->post('email'),
                'address_line1'  => $this->input->post('address_line1'),
                'address_line2'  => $this->input->post('address_line2'),
                'city'           => $this->input->post('city'),
                'state'          => $this->input->post('state'),
                'country'        => $this->input->post('country'),
                'pincode'        => $this->input->post('pincode'),
                'created_at'     => date('Y-m-d H:i:s')
            ];

            $id = $this->Invoice_model->insert_vendor($data);
            if ($id) {
                $data['id'] = $id;
                $new_row_html = $this->load->view('in-store/dealer_row_template', ['dealer' => (object)$data], TRUE);
                echo json_encode(['status' => 'success', 'message' => 'Vendor added successfully!', 'new_row' => $new_row_html]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
            }
        }
    }
    public function check_vendor_exists()
    {
        $field = $this->input->post('field');
        $value = $this->input->post('value');

        if (empty($field) || empty($value)) {
            echo json_encode(['exists' => false]);
            return;
        }

        $exists = $this->Invoice_model->vendor_exists($field, $value);

        echo json_encode(['exists' => $exists]);
    }

    public function delete_dealer($vendor_id = null)
    {
        if (empty($vendor_id)) {
            $this->session->set_flashdata('error', 'Dealer ID not provided.');
            redirect('dashboard/vendors');
            return;
        }

        $result = $this->Invoice_model->delete_dealer($vendor_id);

        if ($result) {
            $this->session->set_flashdata('success', 'Dealer deleted successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to delete vendor.');
        }

        redirect('dealer-list');
    }

    public function update_dealer_modal()
    {
        $vendor_data = $this->input->post();
        $vendor_id = $vendor_data['id'];
        unset($vendor_data['id']);

        $result = $this->Invoice_model->update_dealer($vendor_data, $vendor_id);

        if ($result) {
            $this->session->set_flashdata('success', 'Vendor updated successfully.');
        } else {
            $this->session->set_flashdata('error', 'No changes were made or the update failed.');
        }

        redirect('dealer-list');
    }

    private function _authenticate_shiprocket()
    {
        $email = $this->config->item('shiprocket_email', 'shiprocket');
        $password = $this->config->item('shiprocket_password', 'shiprocket');
        $api_url = $this->config->item('shiprocket_api_url', 'shiprocket');
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $api_url . "auth/login",
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
            log_message('error', 'Shiprocket Authentication cURL Error: ' . $err);
            return false;
        }

        $result = json_decode($response, true);

        if (isset($result['token'])) {
            $this->session->set_userdata('shiprocket_token', $result['token']);
            return true;
        } else {
            log_message('error', 'Shiprocket Authentication Failed: ' . print_r($result, true));
            return false;
        }
    }


    private function _send_to_shiprocket($invoice_data)
    {
        $token = $this->session->userdata('shiprocket_token');
        $api_url = $this->config->item('shiprocket_api_url', 'shiprocket');
        $pickup_location_name = $this->config->item('shiprocket_pickup_name', 'shiprocket') ?? 'work';

        if (!$token) {
            if (!$this->_authenticate_shiprocket()) {
                return ['status' => 'error', 'message' => 'Shiprocket token not available after re-auth.'];
            }
            $token = $this->session->userdata('shiprocket_token');
        }

        $shiprocket_items = [];
        $total_weight_kg = 0;
        $max_dim = ['length' => 0, 'breadth' => 0, 'height' => 0];

        foreach ($invoice_data['items'] as $item) {
            $selling_price = (float)($item['price_at_order'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 1);
            $weight_kg = (float)($item['weight_kg'] ?? 0);
            $length_cm = (float)($item['length_cm'] ?? 0);
            $breadth_cm = (float)($item['breadth_cm'] ?? 0);
            $height_cm = (float)($item['height_cm'] ?? 0);

            $shiprocket_items[] = [
                'name'      => trim($item['product_name'] ?? 'Product'),
                'sku'       => trim($item['sku'] ?? 'SKU-NA'),
                'units'     => $quantity,
                'selling_price' => $selling_price,
                'hsn'       => trim($item['hsn'] ?? '')
            ];

            $total_weight_kg += ($weight_kg * $quantity);
            $max_dim['length'] = max($max_dim['length'], $length_cm);
            $max_dim['breadth'] = max($max_dim['breadth'], $breadth_cm);
            $max_dim['height'] = max($max_dim['height'], $height_cm);
        }

        $total_weight_kg = max(0.5, round($total_weight_kg, 2));
        $package_length = max(1.0, round($max_dim['length'], 1));
        $package_breadth = max(1.0, round($max_dim['breadth'], 1));
        $package_height = max(1.0, round($max_dim['height'], 1));

        $clean_pincode = preg_replace("/[^0-9]/", "", trim($invoice_data['pincode'] ?? '000000'));
        $clean_phone = preg_replace("/[^0-9]/", "", trim($invoice_data['phone_number'] ?? '9999999999'));

        $shiprocket_payload = [
            'order_id'              => trim($invoice_data['invoice_number']),
            'order_date'            => date('Y-m-d H:i:s', strtotime($invoice_data['created_at'])),
            'pickup_location'       => $pickup_location_name,
            'channel_id'            => '8291407',
            'billing_customer_name' => trim($invoice_data['customer_name'] ?? 'Guest'),
            'billing_last_name'     => trim($invoice_data['customer_last_name'] ?? '.'),
            'billing_address'       => trim($invoice_data['billing_address1'] ?? 'NA'),
            'billing_address_2'     => trim($invoice_data['billing_address2'] ?? ''),
            'billing_city'          => trim($invoice_data['city'] ?? 'NA'),
            'billing_pincode'       => $clean_pincode,
            'billing_state'         => trim($invoice_data['state'] ?? 'NA'),
            'billing_country'       => 'India',
            'billing_email'         => trim($invoice_data['customer_email'] ?? 'test@example.com'),
            'billing_phone'         => $clean_phone,
            'shipping_customer_name' => trim($invoice_data['customer_name'] ?? 'Guest'),
            'shipping_last_name'     => trim($invoice_data['customer_last_name'] ?? '.'),
            'shipping_address'       => trim($invoice_data['billing_address1'] ?? 'NA'),
            'shipping_address_2'     => trim($invoice_data['billing_address2'] ?? ''),
            'shipping_city'          => trim($invoice_data['city'] ?? 'NA'),
            'shipping_pincode'       => $clean_pincode,
            'shipping_state'         => trim($invoice_data['state'] ?? 'NA'),
            'shipping_country'       => 'India',
            'shipping_email'         => trim($invoice_data['customer_email'] ?? 'test@example.com'),
            'shipping_phone'         => $clean_phone,
            'shipping_is_billing'   => true,
            'order_items'           => $shiprocket_items,
            'payment_method'        => (strtolower($invoice_data['payment_status']) === 'paid' ? 'Prepaid' : 'COD'),
            'shipping_charges'      => (float)($invoice_data['delivery_charge'] ?? 0.0),
            'gift_wrap_charges'     => 0,
            'transaction_charges'   => 0,
            'total_discount'        => (float)($invoice_data['discount_amount'] ?? 0.0),
            'sub_total'             => (float)($invoice_data['sub_total'] ?? 0.0),
            'length'                => $package_length,
            'breadth'               => $package_breadth,
            'height'                => $package_height,
            'weight'                => $total_weight_kg
        ];

        log_message('error', 'Shiprocket FINAL PAYLOAD for Invoice ' . $invoice_data['id'] . ': ' . json_encode($shiprocket_payload, JSON_PRETTY_PRINT));
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $api_url . "orders/create/adhoc",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($shiprocket_payload),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $token
            ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        log_message('info', 'Shiprocket API Response for Order ' . $invoice_data['invoice_number'] . ': ' . $response);


        if ($err) {
            log_message('error', 'Shiprocket Order Creation cURL Error for Invoice ' . $invoice_data['id'] . ': ' . $err);
            return ['status' => 'error', 'message' => 'API connection failed.'];
        }

        $result = json_decode($response, true);
        if (isset($result['order_id']) && ($result['status_code'] == 200 || $result['status_code'] == 1)) {
            $shipment_id = $result['shipment_id'] ?? null;
            $awb_code = $result['awb_code'] ?? null;

            $this->Invoice_model->update_shiprocket_ids($invoice_data['id'], $shipment_id, $awb_code);

            return [
                'status' => 'success',
                'shiprocket_id' => $shipment_id,
                'awb_code' => $awb_code
            ];
        } else {
            $message = $result['message'] ?? ($result['errors'][0]['message'] ?? 'Unknown Shiprocket API Error.');
            if (isset($result['errors'])) {
                $message .= ' | Details: ' . json_encode($result['errors']);
            }
            log_message('error', 'Shiprocket API Error for Invoice ' . $invoice_data['id'] . ': ' . $message);
            return ['status' => 'error', 'message' => $message];
        }
    }

    public function send_to_shiprocket()
    {
        $this->output->set_content_type('application/json');

        if (!$this->session->userdata('logged_in')) {
            $this->output->set_status_header(401)->set_output(json_encode(['success' => false, 'message' => 'Unauthorized.']));
            return;
        }

        $json_data = file_get_contents('php://input');
        if (empty($json_data)) {
            $this->output->set_status_header(400)->set_output(json_encode(['success' => false, 'message' => 'No request body received.']));
            return;
        }

        $data = json_decode($json_data, true);
        $invoice_id = $data['invoice_id'] ?? null;

        if (empty($invoice_id)) {
            log_message('error', 'Missing Invoice ID. Raw payload: ' . $json_data);
            $this->output->set_status_header(400)->set_output(json_encode(['success' => false, 'message' => 'Missing Invoice ID.']));
            return;
        }

        $invoice_data = $this->Invoice_model->get_full_invoice_details($invoice_id);

        if (!$invoice_data || empty($invoice_data['items'])) {
            $this->output->set_status_header(404)->set_output(json_encode(['success' => false, 'message' => 'Invoice data not found or no items present.']));
            return;
        }
        if (!empty($invoice_data['shiprocket_id'])) {
            $this->output->set_output(json_encode([
                'success' => true,
                'message' => "This invoice has already been sent to Shiprocket. Shipment ID: " . $invoice_data['shiprocket_id']
            ]));
            return;
        }

        $shiprocket_result = $this->_send_to_shiprocket($invoice_data);
        if ($shiprocket_result['status'] === 'success') {
            $this->output->set_output(json_encode([
                'success' => true,
                'message' => "Invoice successfully sent to Shiprocket. Shipment ID: " . $shiprocket_result['shiprocket_id']
            ]));
        } else {
            $this->output->set_status_header(500)->set_output(json_encode([
                'success' => false,
                'message' => "Failed to send to Shiprocket: " . $shiprocket_result['message']
            ]));
        }
    }

    private function _safe_float_from_string($value, $default = 0.0)
    {
        if (empty($value)) return $default;
        // Strip anything that isn't a digit, dot, or minus sign
        $clean_value = preg_replace('/[^0-9.]/', '', $value);
        return is_numeric($clean_value) ? (float)$clean_value : $default;
    }
}
