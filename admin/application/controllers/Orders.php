<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Orders extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        // Load the Order model
        $this->load->model('order_model');

        // Set CORS headers to allow requests from your Angular app
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

        // Handle pre-flight OPTIONS request for CORS
        if ($this->input->method() === 'options') {
            $this->output->set_status_header(200);
            exit(0);
        }
    }

    /**
     * API endpoint to get the total number of orders for a user.
     * Corresponds to Angular's getTotalOrders()
     * URL: GET /orders/total/{user_id}
     */
    public function total($user_id)
    {
        if (empty($user_id) || !is_numeric($user_id)) {
            return $this->output->set_status_header(400)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Invalid User ID.']));
        }

        // THIS IS THE CRUCIAL LINE THAT WAS LIKELY REMOVED OR HAS A TYPO
        $total_count = $this->order_model->get_total_orders_by_user($user_id);

        $response = ['success' => true, 'totalOrders' => (int)$total_count];
        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }
    public function pending($user_id)
    {
        if (empty($user_id) || !is_numeric($user_id)) {
            return $this->output->set_status_header(400)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Invalid User ID.']));
        }

        // This is the line that was likely missing or had a typo
        $pending_count = $this->order_model->get_pending_orders_by_user($user_id);

        $response = ['success' => true, 'pendingOrders' => (int)$pending_count];
        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }

    public function get_user_orders()
    {
        // Your service sends a POST request with a JSON body
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = isset($input['userId']) ? $input['userId'] : null;

        if (empty($user_id) || !is_numeric($user_id)) {
            return $this->output->set_status_header(400)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'User ID is required.']));
        }

        // Call the model to fetch the orders
        // Using the function from Order_model is a good practice.
        $orders = $this->order_model->get_orders_by_user_with_items($user_id);

        // Create the JSON response
        $response = [
            'success' => true,
            'orders' => $orders,
            'message' => 'Orders fetched successfully.'
        ];

        // Output the JSON response
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
}
