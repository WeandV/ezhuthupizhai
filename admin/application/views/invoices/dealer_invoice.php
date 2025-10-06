<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light dark" />
    <meta name="supported-color-schemes" content="light dark" />
    <title>Invoice - <?= $invoice->invoice_number ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <style type="text/css">
        @page {
            margin: 30px;
            margin-bottom: 60px;
            border: 1px solid #eeee;
            font-family: "Montserrat", sans-serif;
        }

        .dompdf-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            text-align: center;
        }

        .header-border {
            width: 80%;
            height: 2px;
            background-color: #d0e9f1;
            margin: 10px auto;
        }

        .dompdf-header img {
            max-width: 200px;
            height: auto;
            margin-bottom: 10px;
        }

        .dompdf-header p {
            margin: 0;
            font-size: 15px;
            font-weight: 500;
            color: #555;
            font-family: "Montserrat", sans-serif;
        }

        .dompdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 70px;
            text-align: center;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: "Montserrat", sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td,
        th {
            padding: 0;
        }

        .invoice-title {
            text-align: center;
            padding: 20px 0;

        }

        .invoice-title h1 {
            font-size: 28px;
            color: teal;
            margin: 0;
            font-family: "Montserrat", sans-serif;
        }

        .invoice-title p {
            margin: 0px;
            font-size: 15px;
            color: #000;
            font-family: "Montserrat", sans-serif;
        }

        .details-section {
            padding: 20px 0;
            border-bottom: 1px solid #ddd;
            display: flow-root;
        }

        .details-section table {
            border: none;
        }

        .details-section td {
            font-size: 14px;
            color: #000;
            vertical-align: top;
            line-height: 18px;
            font-weight: 500;
            font-family: "Montserrat", sans-serif;
        }

        .details-section td.right {
            text-align: right;
        }

        .details-section strong {
            font-weight: bold;
            color: #333;
        }

        .items-table {
            padding: 10px 0;
        }

        .items-table table {
            border-bottom: 1px solid #ddd;
            table-layout: auto;
        }

        .items-table th {
            background-color: #d0e9f1;
            color: #000;
            text-align: left;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #97ddf5ff;
            font-family: "Montserrat", sans-serif;
        }

        .items-table td {
            padding: 10px;
            font-size: 14px;
            font-weight: 500;
            color: #000;
        }

        .totals-section {
            padding-top: 10px;
            display: flow-root;
        }

        .totals-section table {
            max-width: 280px;
            float: right;
            border: none;
        }

        .totals-section td {
            padding: 5px;
            font-size: 15px;
            font-weight: 500;
            color: #000;
            text-align: right;
        }

        .totals-section td.label {
            font-weight: bold;
            color: #444;
        }

        .totals-section .grand-total td {
            font-size: 18px;
            font-weight: bold;
            border-top: 1px solid #d0e9f1;
            border-bottom: none;
        }

        .notes-section {
            padding-top: 10px;
        }

        .notes-section h2 {
            font-size: 18px;
            color: teal;
            margin-top: 0;
            font-family: "Montserrat", sans-serif;
            text-decoration: underline;
        }

        .notes-section p,
        .notes-section ul {
            color: #000;
            font-family: "Montserrat", sans-serif;
        }

        .notes-section ul {
            margin: 0 0 0 20px;
            padding: 0;
        }
    </style>
</head>

<body>
    <div class="dompdf-header">
        <img src="<?= base_url(); ?>assets/images/logo.png" alt="Ezhuthupizhai Publications Logo">
        <p>#78/42,2nd Floor, Saradha Apartments, 3rd Main Rd,</p>
        <p>Gandhi Nagar, Adyar, Chennai 600020</p>
        <div class="header-border"></div>
    </div>

    <div class="dompdf-footer">
        <h2 style="font-size: 15px; color: teal; margin-top: 0;">Thank You for Your Business!</h2>
        <p style="margin-bottom: 0px;">
            &copy; <?= date('Y') ?> Ezhuthupizhai Publications. All rights reserved.
            <br>
            This is a computer-generated document and does not require a signature.
        </p>
        <p style="font-size: 12px; color: #777; margin-bottom: 0px;">
            Phone: +91 99627 00810 | Mail: ezhuthupizhaiofficial@gmail.com | Website: www.ezhuthupizhai.in
        </p>
    </div>

    <table cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 120px;">
        <tr>
            <td class="invoice-title">
                <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="width: 50%; vertical-align: top;">
                            <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                                <h1>#<?= $invoice->invoice_number ?></h1>
                            </table>
                        </td>
                        <td style="width: 50%; vertical-align: top;">
                            <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%" style="text-align: right;">
                                <tr>
                                    <td><strong>Invoice Date:</strong> <?= date('F j, Y', strtotime($invoice->invoice_date)) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong>
                                        <?php
                                        $status = $invoice->payment_status;
                                        $color = '';
                                        switch ($status) {
                                            case 'Paid':
                                                $color = 'background-color: #d4edda; color: #155724;';
                                                break;
                                            case 'Not Paid':
                                            default:
                                                $color = 'background-color: #e2e3e5; color: #495057;';
                                                break;
                                        }
                                        ?>
                                        <span style="padding: 4px 8px; border-radius: 5px; font-weight: bold; <?= $color; ?>">
                                            <?= ucwords($status); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="details-section">
                <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="width: 50%; vertical-align: top;">
                            <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                                <tr>
                                    <td><strong style="text-decoration:underline; font-weight: 700">Invoice From:</strong></td>
                                </tr>
                                <tr>
                                    <td>Ezhuthupizhai Publications</td>
                                </tr>
                                <tr>
                                    <td>78/42, 3rd Main Rd, Gandhi Nagar, Adyar, Chennai, Tamil Nadu 600020</td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong> +91 99627 00810</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong> mano@ezhuthupizhai.in</td>
                                </tr>
                            </table>
                        </td>

                        <td style="width: 50%; vertical-align: top;">
                            <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%" style="text-align: right;">
                                <tr>
                                    <td><strong style="text-decoration:underline; font-weight: 700">Billing To:</strong></td>
                                </tr>
                                <tr>
                                    <td><?= ucwords($invoice->customer_name ?? '') ?></td>
                                </tr>
                                <tr>
                                    <td><?= ucwords($invoice->address_line1 ?? '') ?></td>
                                </tr>
                                <?php if (!empty($invoice->address_line2)): ?>
                                    <tr>
                                        <td><?= ucwords($invoice->address_line2 ?? '') ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td>
                                        <?= ucwords($invoice->city ?? '') ?>, <?= ucwords($invoice->state ?? '') ?> - <?= $invoice->pincode ?? 'N/A' ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong> <?= $invoice->phone ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong> <?= $invoice->email ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="items-table">
                <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Item Description</th>
                            <th style="width: 20%;">Price</th>
                            <th style="width: 15%;">Quantity</th>
                            <th style="width: 15%; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= $item->book_title ?></td>
                                <td>₹ <?= number_format((float)$item->unit_price, 2) ?></td>
                                <td><?= $item->quantity ?></td>
                                <td style="text-align: right;">₹ <?= number_format((float)$item->total, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </td>
        </tr>
        <td class="totals-section">
            <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td class="label">Sub Total:</td>
                    <td>₹ <?= number_format((float)$invoice->total_amount, 2) ?></td>
                </tr>

                <?php if ((float)$invoice->discount_percentage > 0): ?>
                    <tr>
                        <td class="label">Discount (<?= number_format((float)$invoice->discount_percentage, 2) ?>%):</td>
                        <td>- ₹ <?= number_format((float)$invoice->discount_amount, 2) ?></td>
                    </tr>
                <?php endif; ?>

                <?php if ((float)$invoice->flat_discount > 0): ?>
                    <tr>
                        <td class="label">Flat Discount:</td>
                        <td>- ₹ <?= number_format((float)$invoice->flat_discount, 2) ?></td>
                    </tr>
                <?php endif; ?>

                <?php if ((float)$invoice->delivery_charge > 0): ?>
                    <tr>
                        <td class="label">Delivery Charge:</td>
                        <td>+ ₹ <?= number_format((float)$invoice->delivery_charge, 2) ?></td>
                    </tr>
                <?php endif; ?>

                <tr class="grand-total">
                    <td class="label">Grand Total:</td>
                    <td>₹ <?= number_format((float)$invoice->sub_total, 2) ?></td>
                </tr>
            </table>
        </td>
        <tr>
            <td class="notes-section">
                <h2>Notes</h2>
                <ul style="font-size: 13px; line-height: 20px; margin-bottom: 0px;">
                    <li>Books once sold will not be exchanged or refunded.</li>
                    <li>For any inquiries regarding this invoice, please contact us at ezhuthupizhaiofficial@gmail.com.</li>
                    <li>Thank you for your prompt payment!</li>
                </ul>
            </td>
        </tr>
    </table>
</body>

</html>