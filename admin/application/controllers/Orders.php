<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Orders extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Order_model');
        $this->load->helper('url');
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    public function total($user_id) {
        if (!is_numeric($user_id) || $user_id <= 0) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Invalid user ID.']));
            return;
        }
        $total_orders = $this->Order_model->get_total_orders_by_user($user_id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'totalOrders' => $total_orders]));
    }

    public function pending($user_id) {
        if (!is_numeric($user_id) || $user_id <= 0) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Invalid user ID.']));
            return;
        }

        $pending_orders = $this->Order_model->get_pending_orders_by_user($user_id);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'pendingOrders' => $pending_orders]));
    }
}