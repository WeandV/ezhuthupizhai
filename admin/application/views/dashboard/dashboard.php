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
                        <li class="breadcrumb-item active" aria-current="page">eCommerce</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="card radius-10 bg-custom-gradient">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6 text-white">Total Combined Revenue</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-custom-dark-green">
                                <ion-icon name="wallet-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0 text-white">₹<?= number_format((float)$combined_total_revenue, 2) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 bg-custom-gradient">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6 text-white">Current Month Total Revenue</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-custom-dark-green">
                                <ion-icon name="trending-up-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0 text-white">₹<?= number_format((float)$combined_current_month_revenue, 2) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 bg-custom-gradient">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6 text-white">Current Week Total Revenue</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-custom-dark-green">
                                <ion-icon name="trending-up-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0 text-white">₹<?= number_format((float)$combined_current_week_revenue, 2) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="mt-4">Website Sales</h5>
        <hr>
        <div class="row row-cols-1 row-cols-lg-2 row-cols-xxl-3">
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6">Total Website Revenue</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-gradient-purple">
                                <ion-icon name="wallet-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0">₹<?= number_format((float)$total_website_revenue, 2) ?></h4>
                            </div>
                            <div class="ms-auto">+6.32%</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6">Current Month Website Sales</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-gradient-info">
                                <ion-icon name="trending-up-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0">₹<?= number_format((float)$current_month_website_revenue, 2) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6">Current Week Website Sales</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-gradient-success">
                                <ion-icon name="trending-up-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0">₹<?= number_format((float)$current_week_website_revenue, 2) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="mt-4">In-Store Sales</h5>
        <hr>
        <div class="row row-cols-1 row-cols-lg-2 row-cols-xxl-3">
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6">Total In-Store Revenue</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-gradient-purple">
                                <ion-icon name="wallet-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0">₹<?= number_format((float)$total_instore_revenue, 2) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6">Current Month In-Store Sales</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-gradient-info">
                                <ion-icon name="trending-up-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0">₹<?= number_format((float)$current_month_instore_revenue, 2) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6">Current Week In-Store Sales</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-gradient-success">
                                <ion-icon name="trending-up-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0">₹<?= number_format((float)$current_week_instore_revenue, 2) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="mt-4">Order Status</h5>
        <hr>
        <div class="row row-cols-1 row-cols-lg-2 row-cols-xxl-4">
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6">Total Orders</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-gradient-danger">
                                <ion-icon name="bag-handle-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0"><?= $total_orders ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6">Processing Orders</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-gradient-danger">
                                <ion-icon name="ellipsis-horizontal-circle-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0"><?= $processed_orders ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6">Shipped Orders</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-gradient-warning">
                                <ion-icon name="cube-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0"><?= $shipped_orders ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6">Delivered Orders</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-gradient-success">
                                <ion-icon name="checkmark-circle-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0"><?= $delivered_orders ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6">Cancelled Orders</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-gradient-dark">
                                <ion-icon name="close-circle-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0"><?= $cancelled_orders ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <p class="mb-0 fs-6">On Hold Orders</p>
                            </div>
                            <div class="ms-auto widget-icon-small text-white bg-gradient-secondary">
                                <ion-icon name="pause-circle-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div>
                                <h4 class="mb-0"><?= $on_hold_orders ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card radius-10 w-100">
                    <div class="card-body">
                        <h6 class="mb-0">Daily Revenue</h6>
                        <div id="dailyRevenueChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card radius-10 w-100">
                    <div class="card-body">
                        <h6 class="mb-0">Invoice Status Breakdown</h6>
                        <div id="invoiceStatusChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card radius-10 w-100">
                    <div class="card-body">
                        <h6 class="mb-0">Top 10 Selling Products</h6>
                        <div id="topProductsChart"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-6">
                <div class="card radius-10 w-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <h6 class="mb-0">Daily Sales Data</h6>
                            <div class="fs-5 ms-auto dropdown">
                                <div class="dropdown-toggle dropdown-toggle-nocaret cursor-pointer" data-bs-toggle="dropdown"><i
                                        class="bi bi-three-dots"></i></div>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#">Action</a></li>
                                    <li><a class="dropdown-item" href="#">Another action</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="table-responsive mt-2">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Total Orders</th>
                                        <th>Total Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($daily_sales)): ?>
                                        <?php foreach ($daily_sales as $row): ?>
                                            <tr>
                                                <td><?= date('M d, Y', strtotime($row['sales_date'])); ?></td>
                                                <td><?= $row['daily_orders']; ?></td>
                                                <td>₹<?= number_format((float)$row['daily_revenue'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No sales data available.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card radius-10 w-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <h6 class="mb-0">Daily Sales Data (In-Store & Dealer)</h6>
                            <div class="fs-5 ms-auto dropdown">
                                <div class="dropdown-toggle dropdown-toggle-nocaret cursor-pointer" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></div>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#">Action</a></li>
                                    <li><a class="dropdown-item" href="#">Another action</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="table-responsive mt-2">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Total Invoices</th>
                                        <th>Total Revenue</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($invoices_daily_sales)): ?>
                                        <?php foreach ($invoices_daily_sales as $row): ?>
                                            <tr>
                                                <td><?= date('M d, Y', strtotime($row['sales_date'])); ?></td>
                                                <td><?= $row['daily_invoices']; ?></td>
                                                <td>₹<?= number_format((float)$row['daily_revenue'], 2); ?></td>
                                                <td>
                                                    <?php
                                                    $badge_class = '';
                                                    switch ($row['payment_status']) {
                                                        case 'Paid':
                                                            $badge_class = 'bg-success';
                                                            break;
                                                        case 'Unpaid':
                                                            $badge_class = 'bg-danger';
                                                            break;
                                                        case 'Partially Paid':
                                                            $badge_class = 'bg-warning';
                                                            break;
                                                        default:
                                                            $badge_class = 'bg-secondary';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badge_class; ?>">
                                                        <?= ucwords(str_replace('_', ' ', $row['payment_status'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No sales data available.</td>
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
</div>