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
                                    <!-- <th>Status</th> -->
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Order Status</th>
                                    <th>Order Details</th>
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
                                                    $badge_class = 'bg-light-secondary text-secondary'; // Default badge
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
                                            <td class="text-center text-primary">
                                                <i class="fa-solid fa-eye fs-5"></i>
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
    /**
     * Sends an AJAX request to update the order status.
     * @param {number} orderId The ID of the order to update.
     * @param {string} newStatus The new status to set for the order.
     */
    function updateOrderStatus(orderId, newStatus) {
        if (!confirm('Are you sure you want to change the order status to ' + newStatus + '?')) {
            return; // Exit if the user cancels
        }

        const url = "<?= base_url('dashboard/update_order_status'); ?>";

        // Use the Fetch API to send the request
        fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    // Optional: Add a custom header to identify the AJAX request
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    order_status: newStatus
                }),
            })
            .then(response => {
                // Check if the response is OK (status code 200-299)
                if (!response.ok) {
                    // Throw an error to be caught by the .catch() block
                    return response.json().then(error => {
                        throw new Error(error.message || 'Something went wrong.');
                    });
                }
                return response.json();
            })
            .then(data => {
                // The request was successful
                if (data.success) {
                    // You can display a success message
                    console.log(data.message);
                    // Reload the page to show the updated status
                    window.location.reload();
                } else {
                    // This block handles a successful request with a failed operation
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                // This block handles network errors or errors thrown in the .then() block
                console.error('AJAX Error:', error);
                alert('An error occurred during the update: ' + error.message);
            });
    }
</script>
