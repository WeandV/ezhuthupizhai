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
            <div class="row mb-3 justify-content-end">
                <div class="col-md-3">
                    <select id="orderStatusFilter" class="form-select">
                        <option value="">-- Filter by Status --</option>
                        <?php
                        $statuses = [
                            'Processing',
                            'Confirmed',
                            'Pickup Scheduled',
                            'Shipped',
                            'In Transit',
                            'Out For Delivery',
                            'Out For Pickup',
                            'Delivered',
                            'RTO Initiated',
                            'RTO In Transit',
                            'RTO Out For Delivery',
                            'RTO Delivered',
                            'Cancelled',
                            'On Hold',
                            'Delivery Failed'
                        ];
                        foreach ($statuses as $status) {
                            echo "<option value=\"$status\">$status</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="card shadow-none">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="table-layout: fixed;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 15%;">Order ID</th>
                                    <th style="width: 20%;">Name</th>
                                    <!-- <th style="width: 10%;">Payment Method</th> -->
                                    <th style="width: 10%;">Amount</th>
                                    <th style="width: 10%;">Date</th>
                                    <th style="width: 10%;">Time</th>
                                    <th style="width: 14%;">Order Status</th>
                                    <th style="width: 10%;" class="text-end">View Order</th>
                                    <th style="width: 10%;" class="text-end">View</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($orders) : ?>
                                    <?php foreach ($orders as $order) : ?>
                                        <tr>
                                            <td>#<?= $order->invoice_id; ?></td>
                                            <td class="text-capitalize text-truncate" style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?= $order->first_name . ' ' . $order->last_name; ?>
                                            </td>
                                            <!-- <td><?= $order->payment_method; ?></td> -->
                                            <td>&#8377; <?= number_format($order->final_total, 2); ?></td>
                                            <td><?= date('d M Y', strtotime($order->created_at)); ?></td>
                                            <td><?= date('h:i A', strtotime($order->created_at)); ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <?php
                                                    $badge_class = 'bg-light-secondary text-secondary';
                                                    switch ($order->status) {
                                                        case 'Processing':
                                                            $badge_class = 'bg-light-primary text-primary';
                                                            break;
                                                        case 'Confirmed':
                                                            $badge_class = 'bg-light-info text-info';
                                                            break;
                                                        case 'Pickup Scheduled':
                                                            $badge_class = 'bg-light-warning text-warning';
                                                            break;
                                                        case 'Shipped':
                                                            $badge_class = 'bg-light-secondary text-secondary';
                                                            break;
                                                        case 'In Transit':
                                                            $badge_class = 'bg-light-info text-info';
                                                            break;
                                                        case 'Out For Delivery':
                                                            $badge_class = 'bg-light-pink text-pink';
                                                            break;
                                                        case 'Out For Pickup':
                                                            $badge_class = 'bg-light-teal text-teal';
                                                            break;
                                                        case 'Delivered':
                                                            $badge_class = 'bg-light-success text-success';
                                                            break;
                                                        case 'RTO Initiated':
                                                            $badge_class = 'bg-light-warning text-warning';
                                                            break;
                                                        case 'RTO In Transit':
                                                            $badge_class = 'bg-light-info text-info';
                                                            break;
                                                        case 'RTO Out For Delivery':
                                                            $badge_class = 'bg-light-pink text-pink';
                                                            break;
                                                        case 'RTO Delivered':
                                                            $badge_class = 'bg-light-success text-success';
                                                            break;
                                                        case 'Cancelled':
                                                            $badge_class = 'bg-light-danger text-danger';
                                                            break;
                                                        case 'On Hold':
                                                            $badge_class = 'bg-light-secondary text-secondary';
                                                            break;
                                                        case 'Delivery Failed':
                                                            $badge_class = 'bg-light-danger text-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <button class="btn <?= $badge_class; ?> dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <?= $order->status; ?>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php
                                                        $statuses = [
                                                            'Processing',
                                                            'Confirmed',
                                                            'Pickup Scheduled',
                                                            'Shipped',
                                                            'In Transit',
                                                            'Out For Delivery',
                                                            'Out For Pickup',
                                                            'Delivered',
                                                            'RTO Initiated',
                                                            'RTO In Transit',
                                                            'RTO Out For Delivery',
                                                            'RTO Delivered',
                                                            'Cancelled',
                                                            'On Hold',
                                                            'Delivery Failed'
                                                        ];
                                                        foreach ($statuses as $status) {
                                                            echo '<li><a class="dropdown-item" href="javascript:void(0)" onclick="updateOrderStatus(' . $order->id . ', \'' . $status . '\')">' . $status . '</a></li>';
                                                        }
                                                        ?>
                                                    </ul>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= base_url('ecommerce/invoice/' . $order->id); ?>" class=" btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </td>
                                            <td>
                                                <div class="text-center d-flex justify-content-end align-items-center gap-2">
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
    const filterSelect = document.getElementById('orderStatusFilter');

    function filterOrders(selectedStatus) {
        selectedStatus = selectedStatus.toLowerCase().trim();
        const rows = document.querySelectorAll('table tbody tr');

        rows.forEach(row => {
            const statusCell = row.querySelector('td:nth-child(6) .btn').innerText.toLowerCase().trim();
            row.style.display = (selectedStatus === "" || statusCell === selectedStatus) ? '' : 'none';
        });
    }
    filterSelect.addEventListener('change', function() {
        filterOrders(this.value);
    });

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