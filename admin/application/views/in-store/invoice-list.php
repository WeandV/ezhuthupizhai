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
                <a href="<?= base_url(); ?>direct-sales" class="btn btn-primary">New Invoice</a>
            </div>
        </div>

        <div class="container-fluid">
            <div class="card shadow-none">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example2" class="table table-hover w-100" style="table-layout: auto;">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Invoice Date</th>
                                    <th>Recipient Type</th>
                                    <th>Name</th>
                                    <th>Invoice Value</th>
                                    <th style="width:170px">Status</th>
                                    <th>Action</th>
                                    <th>Send Invoice</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $item) : ?>
                                    <tr>
                                        <td><?= $item->invoice_number; ?></td>
                                        <td><?= $item->invoice_date; ?></td>
                                        <td><?= $item->recipient_type; ?></td>
                                        <td><?= $item->customer_name; ?></td>
                                        <td>&#8377; <?= $item->sub_total; ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($item->payment_status) {
                                                case 'paid':
                                                    $status_class = 'bg-green-100 text-green-800';
                                                    break;
                                                case 'unpaid':
                                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'overdue':
                                                    $status_class = 'bg-red-100 text-red-800';
                                                    break;
                                                case 'partially_paid':
                                                default:
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                                    break;
                                            }
                                            ?>
                                            <select class="form-select">
                                                <option value="unpaid" <?= $item->payment_status == 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                                <option value="partially_paid" <?= $item->payment_status == 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
                                                <option value="paid" <?= $item->payment_status == 'paid' ? 'selected' : '' ?>>Paid</option>
                                            </select>
                                        </td>
                                        <td class="">
                                            <div class="btn-group d-inline-flex">
                                                <a href="<?= base_url('dashboard/download_invoice/' . $item->id); ?>" class="btn p-0 me-2" target="_blank">
                                                    <i class="fa-solid fa-download"></i>
                                                </a>
                                                <button type="button" class="btn p-0">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary">
                                                Send
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="snackbox"></div>