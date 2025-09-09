<?php $this->load->view('dashboard/sidemenu'); ?>
<div class="page-content-wrapper border shadow bg-white">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Dashboard</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0 align-items-center">
                        <li class="breadcrumb-item"><a href="javascript:;">
                                <ion-icon name="home-outline"></ion-icon>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">User Details</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="container-fluid">
            <div class="card shadow-none">
                <div class="card-header">
                    <h5 class="mb-0">Customer Details: <?= $user->first_name . ' ' . $user->last_name; ?></h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs nav-justified" id="myTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">General</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#history" role="tab" aria-controls="history" aria-selected="false">History</a>
                        </li>
                    </ul>

                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h6 class="mb-3">User Information</h6>
                                    <p><strong>Name:</strong> <?= $user->first_name . ' ' . $user->last_name; ?></p>
                                    <p><strong>Email:</strong> <?= $user->email; ?></p>
                                    <p><strong>Phone:</strong> <?= $user->phone; ?></p>
                                    <p><strong>Status:</strong> <?= ucfirst($user->status); ?></p>
                                    <p><strong>Member Since:</strong> <?= date('F j, Y', strtotime($user->created_at)); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-3">Addresses</h6>
                                    <?php if (!empty($user->addresses)) : ?>
                                        <?php foreach ($user->addresses as $address) : ?>
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?= ucfirst($address->address_type); ?> Address</h6>
                                                    <p class="card-text">
                                                        <?= $address->first_name . ' ' . $address->last_name; ?><br>
                                                        <?= $address->address1; ?><br>
                                                        <?php if (!empty($address->address2)) : ?>
                                                            <?= $address->address2; ?><br>
                                                        <?php endif; ?>
                                                        <?= $address->city . ', ' . $address->state . ' ' . $address->zip_code; ?><br>
                                                        <?= $address->country; ?><br>
                                                        Phone: <?= $address->phone; ?><br>
                                                        Email: <?= $address->email; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <p>No addresses found for this user.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                            <div class="mt-4">
                                <h6 class="mb-3">Order History</h6>
                                <?php if (!empty($orders)) : ?>
                                    <div class="accordion" id="ordersAccordion">
                                        <?php foreach ($orders as $key => $order) : ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="heading<?= $key; ?>">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $key; ?>" aria-expanded="false" aria-controls="collapse<?= $key; ?>">
                                                        Order #<?= $order->id; ?> - <?= date('F j, Y', strtotime($order->created_at)); ?> (Total: <?= $order->final_total; ?>)
                                                    </button>
                                                </h2>
                                                <div id="collapse<?= $key; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $key; ?>" data-bs-parent="#ordersAccordion">
                                                    <div class="accordion-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong>Order ID:</strong> <?= $order->id; ?></p>
                                                                <p><strong>Status:</strong> <?= ucfirst($order->status); ?></p>
                                                                <p><strong>Payment Method:</strong> <?= $order->payment_method; ?></p>
                                                                <p><strong>Subtotal:</strong> <?= $order->subtotal; ?></p>
                                                                <p><strong>Delivery Charge:</strong> <?= $order->delivery_charge; ?></p>
                                                                <p><strong>Total:</strong> <?= $order->final_total; ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Shipping Address</h6>
                                                                <p>
                                                                    <?= $order->first_name . ' ' . $order->last_name; ?><br>
                                                                    <?= $order->address1; ?><br>
                                                                    <?php if (!empty($order->address2)) : ?><?= $order->address2; ?><br><?php endif; ?>
                                                                    <?= $order->city . ', ' . $order->state . ' ' . $order->zip_code; ?><br>
                                                                    <?= $order->country; ?><br>
                                                                    Phone: <?= $order->phone; ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <hr>
                                                        <h6>Order Items</h6>
                                                        <table class="table table-sm">
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
                                                                        <td><?= $item->price_at_order; ?></td>
                                                                        <td><?= $item->total; ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <p>No order history found for this user.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>