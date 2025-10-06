<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Shop_model');
        $this->load->model('Order_model');
        $this->load->model('Gallery_model');

        $this->output->set_content_type('application/json');
        $this->load->helper('url');

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    public function products()
    {
        $products = $this->Shop_model->get_all_products();

        foreach ($products as $product) {
            $thumbnail_images = $this->Shop_model->get_product_images($product->id, true);
            $product->thumbnail_image = !empty($thumbnail_images) ? $thumbnail_images[0]->image_url : 'https://placehold.co/500x500';

            $product->categories = explode(',', $product->categories);
            $product->categories = array_map('trim', $product->categories);

            $product->mrp_price = number_format((float)$product->mrp_price, 2, '.', '');
            $product->special_price = number_format((float)$product->special_price, 2, '.', '');

            $product->videos = $this->Shop_model->get_videos_by_product_id($product->id) ?: [];
        }

        echo json_encode(['status' => 'success', 'data' => $products]);
    }

    public function international_products()
    {
        $products = $this->Shop_model->get_international_products();

        foreach ($products as $product) {
            $thumbnail_images = $this->Shop_model->get_product_images($product->id, true);
            $product->thumbnail_image = !empty($thumbnail_images) ? $thumbnail_images[0]->image_url : 'https://placehold.co/500x500';

            $product->categories = explode(',', $product->categories);
            $product->categories = array_map('trim', $product->categories);

            $product->mrp_price = number_format((float)$product->mrp_price, 2, '.', '');
            $product->special_price = number_format((float)$product->special_price, 2, '.', '');

            $product->videos = $this->Shop_model->get_videos_by_product_id($product->id) ?: [];
        }

        echo json_encode(['status' => 'success', 'data' => $products]);
    }
    public function product_detail($id)
    {
        $product = $this->Shop_model->get_product_by_id($id);

        if ($product) {
            $product->images = $this->Shop_model->get_product_images($id);
            $product->reviews = $this->Shop_model->get_product_reviews($id);

            $product->mrp_price = number_format((float)$product->mrp_price, 2, '.', '');
            $product->special_price = number_format((float)$product->special_price, 2, '.', '');

            $product->videos = $this->Shop_model->get_videos_by_product_id($id) ?: [];

            foreach ($product->images as $image) {
                // $image->image_url = base_url($image->image_url);
            }

            echo json_encode(['status' => 'success', 'data' => $product]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Product not found']);
        }
    }

    public function categories()
    {
        $categories = $this->Shop_model->get_all_categories();
        echo json_encode(['status' => 'success', 'data' => $categories]);
    }

    public function slug($slug)
    {
        $product = $this->Shop_model->getProductBySlug($slug);

        if ($product) {
            echo json_encode($product);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Product not found.']);
        }
    }

    public function get_all_coupons()
    {
        $coupons = $this->Shop_model->get_all_coupons();

        $processed_coupons = array_map(function ($coupon) {
            if (isset($coupon->expiry_date) && !empty($coupon->expiry_date)) {
                $coupon->expiry_date = (new DateTime($coupon->expiry_date))->format(DateTime::ISO8601);
            } else {
                $coupon->expiry_date = null;
            }

            if (isset($coupon->allowed_customer_ids) && !empty($coupon->allowed_customer_ids)) {
                $coupon->allowed_customer_ids = array_map('intval', explode(',', $coupon->allowed_customer_ids));
            } else {
                $coupon->allowed_customer_ids = [];
            }
            return $coupon;
        }, $coupons);

        $this->output->set_output(json_encode($processed_coupons));
    }

    public function get_coupon($coupon_code)
    {
        $coupon = $this->Shop_model->get_coupon_by_code($coupon_code);

        if ($coupon) {
            if (isset($coupon->expiry_date) && !empty($coupon->expiry_date)) {
                $coupon->expiry_date = (new DateTime($coupon->expiry_date))->format(DateTime::ISO8601);
            } else {
                $coupon->expiry_date = null;
            }
            if (isset($coupon->allowed_customer_ids) && !empty($coupon->allowed_customer_ids)) {
                $coupon->allowed_customer_ids = array_map('intval', explode(',', $coupon->allowed_customer_ids));
            } else {
                $coupon->allowed_customer_ids = [];
            }
            $this->output->set_output(json_encode($coupon));
        } else {
            http_response_code(404);
            $this->output->set_output(json_encode(['message' => 'Coupon not found.']));
        }
    }

    public function videos()
    {
        $videos = $this->Shop_model->get_all_videos();
        echo json_encode(['status' => 'success', 'data' => $videos]);
    }

    public function product_videos($product_id)
    {
        $videos = $this->Shop_model->get_videos_by_product_id($product_id);

        if ($videos) {
            echo json_encode(['status' => 'success', 'data' => $videos]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No videos found for this product.']);
        }
    }

    public function vendor()
    {
        $videos = $this->Shop_model->get_all_vendor();
        echo json_encode(['status' => 'success', 'data' => $videos]);
    }
    public function get_all_images()
    {
        $videos = $this->Shop_model->get_all_images();
        echo json_encode(['status' => 'success', 'data' => $videos]);
    }

    public function get_filters()
    {
        $products = $this->Gallery_model->get_all_products();
        $filters = array_map(function ($product) {
            return $product->product;
        }, $products);
        echo json_encode(['status' => 'success', 'data' => $filters]);
    }

    public function get_images()
    {
        $product = $this->input->get('product'); // Get the product name from the query string
        $images = $this->Gallery_model->get_images($product);
        echo json_encode(['status' => 'success', 'data' => $images]);
    }

        public function get_order_summary()
    {
        // Read the raw POST data
        $postData = json_decode(file_get_contents('php://input'), true);
        $userId = $postData['userId'] ?? null;

        if (empty($userId)) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['success' => false, 'message' => 'User ID is required.']));
            return;
        }

        $totalOrders = $this->Order_model->get_total_orders_by_user($userId);
        $pendingOrders = $this->Order_model->get_pending_orders_by_user($userId);

        $response = [
            'success' => true,
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders
        ];

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function get_user_orders()
    {
        // Read the raw POST data
        $postData = json_decode(file_get_contents('php://input'), true);
        $userId = $postData['userId'] ?? null;

        if (empty($userId)) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['success' => false, 'message' => 'User ID is required.']));
            return;
        }

        $orders = $this->Order_model->get_orders_by_user_with_items($userId);
        $response = [
            'success' => true,
            'orders' => $orders
        ];

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

}
