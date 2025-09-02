<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');

        if (!$this->session->userdata('logged_in')) {
            redirect('auth');
        }

        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('order_model');
        $this->load->model('User_model');
        $this->load->model('admin/Inventory_model');
        $this->load->model('admin/Invoice_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }

        redirect('orders');
    }

    public function orders()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }

        $data['title'] = 'Orders List';
        // CORRECTED LINE: Call the method that includes the 'order_status' column
        $data['orders'] = $this->order_model->get_all_orders();

        $order_id = $this->uri->segment(3);
        $data['selected_order'] = null;

        if ($order_id) {
            $data['selected_order'] = $this->order_model->get_order_with_items($order_id);
        }

        $data['viewpage'] = 'dashboard/orders';
        $this->load->view('welcome_message', $data);
    }
    public function update_order_status()
    {
        // Check if the request is an AJAX call and the user is logged in
        if (!$this->input->is_ajax_request() || !$this->session->userdata('logged_in')) {
            // Return an error if the request is invalid or unauthorized
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(403) // Forbidden
                ->set_output(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
            return;
        }

        // Get the JSON data from the request body
        $input_data = json_decode($this->input->raw_input_stream, true);

        $order_id = $input_data['order_id'] ?? null;
        $new_status = $input_data['order_status'] ?? null;

        // Validate the received data
        if (empty($order_id) || empty($new_status)) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400) // Bad Request
                ->set_output(json_encode(['success' => false, 'message' => 'Missing order ID or status.']));
            return;
        }

        // Validate the new status against the allowed ENUM values
        $allowed_statuses = ['Processing', 'Shipped', 'Delivered', 'Cancelled', 'On Hold'];
        if (!in_array($new_status, $allowed_statuses)) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400) // Bad Request
                ->set_output(json_encode(['success' => false, 'message' => 'Invalid status provided.']));
            return;
        }

        // Load the model and perform the update
        $this->load->model('order_model');
        $result = $this->order_model->update_order_status($order_id, $new_status);

        if ($result) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(200) // OK
                ->set_output(json_encode(['success' => true, 'message' => 'Order status updated successfully.']));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(500) // Internal Server Error
                ->set_output(json_encode(['success' => false, 'message' => 'Failed to update database.']));
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
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }

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
        if (!$this->session->userdata('logged_in')) {
            redirect('admin');
        }

        $data['title'] = 'inventory';
        $data['inventory'] = $this->Inventory_model->get_inventory();
        // Get all products and their current stock for the modal dropdown
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
}
