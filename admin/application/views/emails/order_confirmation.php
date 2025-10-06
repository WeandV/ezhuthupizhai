<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order Confirmation - Ezhuthupizhai</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Georgia', serif; color: #333;">
    <div style="max-width: 650px; margin: 30px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
        <div style="background-color: #f8f8f8; padding: 30px; text-align: center; color: #fff;">
            <img src="https://www.ezhuthupizhai.com/assets/images/logo.png" alt="Ezhuthupizhai Logo" style="max-width: 200px; height: auto;">
        </div>

        <div style="padding: 40px; line-height: 1.8; background-color: #e6f1f5">
            <h2 style="font-size: 22px; margin-bottom: 15px; color: #2c3e50;">Your Order is Confirmed!</h2>
            <p>Dear <strong style="color: #2c3e50;"><?= html_escape($order_details['first_name']) ?></strong>,</p>
            <p>We are delighted to inform you that your order has been successfully placed. Below are the details of your purchase:</p>

            <div style="background-color: #fafafa; border: 1px solid #eaeaea; border-radius: 8px; padding: 20px; margin-top: 20px;">
                <p style="margin: 6px 0;"><strong>Order ID:</strong> #<?= html_escape($order_id) ?></p>
                <p style="margin: 6px 0;"><strong>Order Date:</strong> <?= date('F j, Y, g:i a', strtotime($order_details['created_at'])) ?></p>
                <p style="margin: 6px 0;"><strong>Payment Method:</strong> <?= html_escape($order_details['payment_method']) ?></p>
            </div>

            <div style="margin-top: 20px;">
                <h3 style="font-size: 18px; margin-bottom: 10px; color: #2c3e50;">Order Items</h3>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;">
                    <thead>
                        <tr style="background-color: #f2f2f2;">
                            <th style="padding: 12px 8px; border: 1px solid #ddd; text-align: left;">Item</th>
                            <th style="padding: 12px 8px; border: 1px solid #ddd; text-align: center;">Qty</th>
                            <th style="padding: 12px 8px; border: 1px solid #ddd; text-align: right;">Price</th>
                            <th style="padding: 12px 8px; border: 1px solid #ddd; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr style="border-bottom: 1px solid #f2f2f2;">
                                <td style="padding: 12px 8px; border: 1px solid #ddd; text-align: left;"><?= html_escape($item['product_name']) ?></td>
                                <td style="padding: 12px 8px; border: 1px solid #ddd; text-align: center;"><?= html_escape($item['quantity']) ?></td>
                                <td style="padding: 12px 8px; border: 1px solid #ddd; text-align: right;">₹<?= number_format(floatval($item['price_at_order']), 2) ?></td>
                                <td style="padding: 12px 8px; border: 1px solid #ddd; text-align: right;">₹<?= number_format(floatval($item['total']), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px;">
                <table style="width: 100%; font-size: 14px;">
                    <tr>
                        <td style="text-align: right; padding-top: 5px;">Subtotal:</td>
                        <td style="text-align: right; padding-top: 5px;">₹<?= number_format(floatval($order_details['subtotal']), 2) ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: right; padding-top: 5px;">Discount:</td>
                        <td style="text-align: right; padding-top: 5px;">- ₹<?= number_format(floatval($order_details['coupon_discount']), 2) ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: right; padding-top: 5px;">Shipping:</td>
                        <td style="text-align: right; padding-top: 5px;">₹<?= number_format(floatval($order_details['delivery_charge']), 2) ?></td>
                    </tr>
                    <tr style="font-weight: bold; font-size: 16px; border-top: 2px solid #ddd;">
                        <td style="text-align: right; padding-top: 10px;">Grand Total:</td>
                        <td style="text-align: right; padding-top: 10px;">₹<?= number_format(floatval($order_details['final_total']), 2) ?></td>
                    </tr>
                </table>
            </div>

            <a href="https://www.ezhuthupizhai.com/login" style="display: inline-block; margin-top: 25px; padding: 12px 25px; background-color: #2c3e50; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background 0.3s;">Track Your Order</a>

            <p style="margin-top:30px;">If you have any questions, feel free to <a href="mailto:<?= html_escape($from_email) ?>" style="color: #2c3e50; font-weight: bold;">contact our support team</a>. We’re always happy to help.</p>
            <p>Thank you,<br><em style="color: #2c3e50;">The Ezhuthupizhai Team</em></p>
        </div>

        <div style="background-color: #f8f8f8; text-align: center; padding: 20px; font-size: 13px; color: #777;">
            <p style="margin: 0;">©️ <?= date("Y") ?> Ezhuthupizhai. All rights reserved.</p>
            <p style="margin: 5px 0 0;"><a href="https://www.ezhuthupizhai.com" style="color: #2c3e50; text-decoration: none; font-weight: bold;">Visit our website</a></p>
        </div>
    </div>
</body>

</html>