<?php $this->load->view('dashboard/sidemenu'); ?>
<div class="page-content-wrapper">
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
                        <li class="breadcrumb-item active" aria-current="page"><?= $title; ?></li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto">
                <div class="btn-group">
                    <a href="javascript:void()" class="btn btn-outline-primary">Add Order</a>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="card shadow-none">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0 w-100" style="table-layout: auto;">
                            <thead class="table-light">
                                <tr>
                                    <th>id</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($orders) : ?>
                                    <?php foreach ($orders as $order) : ?>
                                        <tr>
                                            <td><?= $order->id; ?></td>
                                            <td class="text-capitalize"><?= $order->first_name . ' ' . $order->last_name; ?></td>
                                            <td>
                                                <?php
                                                $badge_class = '';
                                                switch ($order->status) {
                                                    case 'paid':
                                                    case 'Paid':
                                                    case 'completed':
                                                        $badge_class = 'bg-light-success text-success';
                                                        break;
                                                    case 'delivered':
                                                    case 'Delivered':
                                                        $badge_class = 'bg-light-info text-info';
                                                        break;
                                                    case 'pending':
                                                    case 'pending_payment':
                                                        $badge_class = 'bg-light-warning text-warning';
                                                        break;
                                                    default:
                                                        $badge_class = 'bg-light-secondary text-secondary';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?= $badge_class; ?>"><?= $order->status; ?></span>
                                            </td>
                                            <td><?= $order->payment_method; ?></td>
                                            <td>&#8377; <?= number_format($order->final_total, 2); ?></td>
                                            <td><?= date('d M Y', strtotime($order->created_at)); ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="<?= site_url('orders/details/' . $order->id); ?>">View Details</a></li>
                                                        <li><a class="dropdown-item" href="#">Update Status</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No orders found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>