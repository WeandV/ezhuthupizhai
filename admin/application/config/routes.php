<?php
defined('BASEPATH') or exit('No direct script access allowed');

$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

$route['login'] = 'welcome/index';
$route['logout'] = 'welcome/logout';
$route['orders'] = 'dashboard/orders';
$route['customers'] = 'dashboard/customers';
$route['user-details/(:any)'] = 'dashboard/user_details/$1';

$route['direct-sales'] = 'dashboard/direct_sales';
$route['invoice-list'] = 'dashboard/invoice_list';
$route['dealer-list'] = 'dashboard/dealer_list';
$route['ecommerce/invoice/(:any)'] = 'dashboard/view_invoice/$1';
$route['ecommerce/invoice/view/(:num)'] = 'dashboard/view_order_invoice/$1';
$route['shipping-label/(:num)'] = 'dashboard/download_shipping_label/$1';
$route['view-shipping_label/(:num)'] = 'dashboard/view_shipping_label/$1';


$route['inventory'] = 'dashboard/inventory';

$route['products/slug/(:any)'] = 'products/slug/$1';

$route['api/products'] = 'api/products';
$route['api/categories'] = 'api/categories';
$route['api/product_detail/(:num)'] = 'api/product_detail/$1';
$route['api/products/(:num)/videos'] = 'api/product_videos/$1';

$route['api/coupons'] = 'api_controller/get_all_coupons';
$route['api/coupons/(:any)'] = 'api_controller/get_coupon/$1';
$route['checkout'] = 'checkout';
$route['checkout/place_order'] = 'checkout/place_order';

$route['checkout/get_order_details/(:num)'] = 'checkout/get_order_details/$1';

$route['customer/details/(:num)'] = 'customer/details/$1';
$route['customer/addresses/(:num)'] = 'customer/addresses/$1';

$route['byob/items'] = 'byob/items';
$route['byob/create'] = 'byob/create';
$route['byob/box/(:num)'] = 'byob/box/$1';
$route['byob/add_item'] = 'byob/add_item';
$route['byob/update_item_quantity'] = 'byob/update_item_quantity';
$route['byob/remove_item'] = 'byob/remove_item';
$route['byob/add_to_cart'] = 'byob/add_to_cart';

$route['orders/total/(:num)'] = 'orders/total/$1';
$route['orders/pending/(:num)'] = 'orders/pending/$1';

$route['checkout/create-order'] = 'checkout/create_order';
$route['checkout/verify-payment'] = 'checkout/verify_payment';

// for shiprocket integration
$route['checkout/get_delivery_charge']['post'] = 'checkout/get_delivery_charge';
$route["checkout/getCourier"]['get'] = 'checkout/get_enabled_couriers';
$route["checkout/get_enabled_couriers"]['post'] = 'checkout/get_enabled_courier_charge';
$route['checkout/get_tracking_by_shipment_id/(:num)'] = 'checkout/get_tracking_by_shipment_id/$1';
$route['orders/ready_to_ship/(:num)'] = 'checkout/ready_to_ship/$1';

$route['checkout/order-track'] = 'checkout/track';
