<?php $this->load->view('dashboard/sidemenu'); ?>
<div class="page-content-wrapper border shadow bg-white">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Invoice</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0 align-items-center">
                        <li class="breadcrumb-item">
                            <a href="<?= base_url('dashboard/orders'); ?>">
                                <ion-icon name="list-outline"></ion-icon>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Invoice #<?= $order->id; ?></li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto">
                <a href="<?= base_url('shipping-label/' . $order->id); ?>" class="btn btn-primary">Download Shipping Label</a>
            </div>
        </div>

        <div class="container-fluid">
            <div class="card shadow-none">
                <div class="card-body">
                    <div class="invoice-header d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0">Invoice #<?= $order->id; ?></h2>
                        <p class="mb-0"><strong>Order Date:</strong> <?= date('F j, Y', strtotime($order->created_at)); ?></p>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h4 class="mb-3">Customer Details</h4>
                            <p class="mb-1 fs-6"><strong>Name:</strong> <?= $order->first_name . ' ' . $order->last_name; ?></p>
                            <p class="mb-1 fs-6"><strong>Email:</strong> <?= $order->email; ?></p>
                            <p class="mb-1 fs-6"><strong>Phone:</strong> <?= $order->phone; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h4 class="mb-3">Shipping Address</h4>
                            <p class="mb-1 fs-6"><?= $order->address1; ?></p>
                            <p class="mb-1 fs-6"><?= $order->address2; ?></p>
                            <p class="mb-1 fs-6"><?= $order->city . ', ' . $order->state; ?></p>
                            <p class="mb-1 fs-6"><?= $order->zip_code; ?></p>
                            <p class="mb-1 fs-6"><?= $order->country; ?></p>
                        </div>
                    </div>
                    <h4 class="mb-4">Order Items</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order->items as $item) : ?>
                                    <tr>
                                        <td><?= $item->product_name; ?></td>
                                        <td><?= $item->quantity; ?></td>
                                        <td>₹<?= number_format($item->price_at_order, 2); ?></td>
                                        <td>₹<?= number_format($item->total, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end mt-4">
                        <p><strong>Subtotal:</strong> ₹<?= number_format($order->subtotal, 2); ?></p>
                        <p><strong>Delivery Charge:</strong> ₹<?= number_format($order->delivery_charge, 2); ?></p>
                        <h3><strong>Final Total:</strong> ₹<?= number_format($order->final_total, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>