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
                        <li class="breadcrumb-item active" aria-current="page"><?= $title; ?></li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto">
                <a href="http://localhost/ezhuthupizhai/admin/direct-sales" class="btn btn-primary">New Invoice</a>
            </div>
        </div>
        <div class="container-fluid">
            <div class="card shadow-none">
                <div class="card-body">
                    <div class="table-responsive mt-2">
                        <table class="table align-middle mb-0" style="table-layout: auto;">
                            <thead class="table-light">
                                <tr>
                                    <th>id</th>
                                    <th>Name</th>
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Order Status</th>
                                    <th>View Order</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($orders) : ?>
                                    <?php foreach ($orders as $order) : ?>
                                        <tr>
                                            <td><?= $order->id; ?></td>
                                            <td class="text-capitalize"><?= $order->first_name . ' ' . $order->last_name; ?></td>
                                            <td><?= $order->payment_method; ?></td>
                                            <td>&#8377; <?= number_format($order->final_total, 2); ?></td>
                                            <td><?= date('d M Y', strtotime($order->created_at)); ?></td>
                                            <td style="width: 15%;">
                                                <div class="dropdown">
                                                    <?php
                                                    $badge_class = 'bg-light-secondary text-secondary';
                                                    switch ($order->status) {
                                                        case 'Processing':
                                                            $badge_class = 'bg-light-primary text-primary';
                                                            break;
                                                        case 'Shipped':
                                                            $badge_class = 'bg-light-info text-info';
                                                            break;
                                                        case 'Delivered':
                                                            $badge_class = 'bg-light-success text-success';
                                                            break;
                                                        case 'Cancelled':
                                                            $badge_class = 'bg-light-danger text-danger';
                                                            break;
                                                        case 'On Hold':
                                                            $badge_class = 'bg-light-warning text-warning';
                                                            break;
                                                    }
                                                    ?>
                                                    <button class="btn <?= $badge_class; ?> dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <?= $order->status; ?>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateOrderStatus(<?= $order->id; ?>, 'Processing')">Processing</a></li>
                                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateOrderStatus(<?= $order->id; ?>, 'Shipped')">Shipped</a></li>
                                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateOrderStatus(<?= $order->id; ?>, 'Delivered')">Delivered</a></li>
                                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateOrderStatus(<?= $order->id; ?>, 'Cancelled')">Cancelled</a></li>
                                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateOrderStatus(<?= $order->id; ?>, 'On Hold')">On Hold</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= base_url('ecommerce/invoice/' . $order->id); ?>" class=" btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </td>
                                            <td class="">
                                                <div class="text-center d-flex justify-content-center align-items-center gap-2">
                                                    <a href="<?= base_url('view-shipping_label/' . $order->id); ?>" target="_blank">
                                                        <i class="fa-solid fa-receipt text-primary fs-6"></i>
                                                    </a>
                                                    <a href="<?= base_url('shipping-label/' . $order->id); ?>">
                                                        <i class="fa-solid fa-download text-primary fs-6"></i>
                                                    </a>
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
<script>
    function updateOrderStatus(orderId, newStatus) {
        if (!confirm('Are you sure you want to change the order status to ' + newStatus + '?')) {
            return;
        }

        const url = "<?= base_url('dashboard/update_order_status'); ?>";
        fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    order_status: newStatus
                }),
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(error => {
                        throw new Error(error.message || 'Something went wrong.');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                alert('An error occurred during the update: ' + error.message);
            });
    }
</script>