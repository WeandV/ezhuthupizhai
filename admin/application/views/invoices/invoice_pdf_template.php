<!DOCTYPE html>
<html>
<head>
    <title>Invoice</title>
    <style>
        /* Add CSS styling for your invoice here */
        body { font-family: sans-serif; }
        .invoice-header { text-align: center; }
        .invoice-details { float: right; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <div class="invoice-header">
        <h1>Invoice</h1>
        <p>#<?= $invoice->invoice_number; ?></p>
    </div>
    <div class="invoice-details">
        <p>Invoice Date: <?= $invoice->invoice_date; ?></p>
        <p>Status: <?= $invoice->payment_status; ?></p>
        <p>Payment Mode: <?= $invoice->payment_mode; ?></p>
    </div>
    <div style="clear: both;"></div>

    <table>
        <thead>
            <tr>
                <th>ITEM DESCRIPTION</th>
                <th>PRICE</th>
                <th>QUANTITY</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoice_items as $item): ?>
            <tr>
                <td><?= $item->product_name; ?></td>
                <td><?= $item->price; ?></td>
                <td><?= $item->quantity; ?></td>
                <td><?= $item->total; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </body>
</html>