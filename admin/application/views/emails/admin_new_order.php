<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>New Order Received - Ezhuthupizhai</title>
</head>

<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:'Georgia',serif;color:#333;">
    <div style="max-width:650px;margin:30px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
        <div style="background-color:#2c3e50;padding:20px;text-align:center;color:#fff;">
            <h2 style="margin:0;">📦 New Order Received</h2>
        </div>

        <div style="padding:30px;line-height:1.8;background-color:#f9fafb;">
            <p>Hello <strong>Admin</strong>,</p>
            <p>A new order has been placed on <strong>Ezhuthupizhai</strong>. Here are the details:</p>

            <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:15px;margin:15px 0;">
                <p style="margin:6px 0;"><strong>Order ID:</strong> #<?= html_escape($order_id) ?></p>
                <p style="margin:6px 0;"><strong>Order Date:</strong> <?= date('F j, Y, g:i a', strtotime($order_details['created_at'])) ?></p>
                <p style="margin:6px 0;"><strong>Payment Method:</strong> <?= html_escape($order_details['payment_method']) ?></p>
                <p style="margin:6px 0;"><strong>Total:</strong> ₹<?= number_format(floatval($order_details['final_total']), 2) ?></p>
            </div>

            <h3 style="font-size:18px;margin-top:20px;color:#2c3e50;">Customer Details</h3>
            <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:15px;margin:15px 0;">
                <p style="margin:6px 0;"><strong>Name:</strong> <?= html_escape($order_details['first_name'] . ' ' . $order_details['last_name']) ?></p>
                <p style="margin:6px 0;"><strong>Email:</strong> <?= html_escape($order_details['email']) ?></p>
                <p style="margin:6px 0;"><strong>Phone:</strong> <?= html_escape($order_details['phone']) ?></p>
            </div>

            <h3 style="font-size:18px;margin-top:20px;color:#2c3e50;">Order Items</h3>
            <table style="width:100%;border-collapse:collapse;margin:15px 0;font-size:14px;">
                <thead>
                    <tr style="background-color:#f2f2f2;">
                        <th style="padding:10px;border:1px solid #ddd;text-align:left;">Item</th>
                        <th style="padding:10px;border:1px solid #ddd;text-align:center;">Qty</th>
                        <th style="padding:10px;border:1px solid #ddd;text-align:right;">Price</th>
                        <th style="padding:10px;border:1px solid #ddd;text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td style="padding:10px;border:1px solid #ddd;"><?= html_escape($item['product_name']) ?></td>
                            <td style="padding:10px;border:1px solid #ddd;text-align:center;"><?= $item['quantity'] ?></td>
                            <td style="padding:10px;border:1px solid #ddd;text-align:right;">₹<?= number_format(floatval($item['price_at_order']), 2) ?></td>
                            <td style="padding:10px;border:1px solid #ddd;text-align:right;">₹<?= number_format(floatval($item['total']), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top:20px;">
                <table style="width:100%;font-size:14px;">
                    <tr>
                        <td style="text-align:right;padding:5px 0;">Subtotal:</td>
                        <td style="text-align:right;padding:5px 0;">₹<?= number_format(floatval($order_details['subtotal']), 2) ?></td>
                    </tr>
                    <tr>
                        <td style="text-align:right;padding:5px 0;">Discount:</td>
                        <td style="text-align:right;padding:5px 0;">- ₹<?= number_format(floatval($order_details['coupon_discount']), 2) ?></td>
                    </tr>
                    <tr>
                        <td style="text-align:right;padding:5px 0;">Shipping:</td>
                        <td style="text-align:right;padding:5px 0;">₹<?= number_format(floatval($order_details['delivery_charge']), 2) ?></td>
                    </tr>
                    <tr style="font-weight:bold;font-size:16px;border-top:2px solid #ddd;">
                        <td style="text-align:right;padding-top:10px;">Grand Total:</td>
                        <td style="text-align:right;padding-top:10px;">₹<?= number_format(floatval($order_details['final_total']), 2) ?></td>
                    </tr>
                </table>
            </div>

            <a href="https://www.ezhuthupizhai.com/admin/ecommerce/invoice/<?= html_escape($order_id) ?>" style="display:inline-block;margin-top:25px;padding:12px 25px;background:#dd4814;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;">View in Admin Panel</a>
        </div>

        <div style="background:#2c3e50;text-align:center;padding:15px;font-size:12px;color:#fff;">
            <p style="margin:0;">© <?= date("Y") ?> Ezhuthupizhai. All rights reserved.</p>
        </div>
    </div>
</body>

</html>