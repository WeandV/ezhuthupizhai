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
                                        <td style="width:15%;" data-invoice-id="<?= $item->id; ?>">
                                            <div class="dropdown">
                                                <?php
                                                $badge_class = 'bg-light-secondary text-secondary';
                                                switch ($item->payment_status) {
                                                    case 'Paid':
                                                        $badge_class = 'bg-light-success text-success';
                                                        break;
                                                    case 'Unpaid':
                                                        $badge_class = 'bg-light-danger text-danger';
                                                        break;
                                                    case 'Partially Paid':
                                                        $badge_class = 'bg-light-warning text-warning';
                                                        break;
                                                    default:
                                                        $badge_class = 'bg-light-secondary text-secondary';
                                                        break;
                                                }
                                                ?>
                                                <button class="btn <?= $badge_class; ?> dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <?= ucwords(str_replace('_', ' ', $item->payment_status)); ?>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateInvoiceStatus(<?= $item->id; ?>, 'Unpaid')">Unpaid</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateInvoiceStatus(<?= $item->id; ?>, 'Partially Paid')">Partially Paid</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateInvoiceStatus(<?= $item->id; ?>, 'Paid')">Paid</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td class="">
                                            <div class="btn-group d-inline-flex">
                                                <a href="<?= base_url('dashboard/download_dealer_invoice/' . $item->id . '?action=download'); ?>" class="btn p-0 me-2">
                                                    <i class="fa-solid fa-download"></i>
                                                </a>

                                                <a href="<?= base_url('dashboard/download_dealer_invoice/' . $item->id); ?>" class="btn p-0" target="_blank">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $is_sent = !empty($item->shiprocket_id);
                                            $btn_class = $is_sent ? 'btn-success' : 'btn-primary';
                                            $btn_text = $is_sent ? 'Sent' : 'Send to SR';
                                            $btn_disabled = $is_sent ? 'disabled' : '';
                                            ?>
                                            <button
                                                class="btn <?= $btn_class; ?> send-to-shiprocket"
                                                data-invoice-id="<?= $item->id ?>"
                                                <?= $btn_disabled; ?>>
                                                <?= $btn_text; ?>
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

<script>
    function showSnackbox(message, type = 'success') {
        const snackbox = document.getElementById('snackbox');
        if (snackbox) {
            snackbox.textContent = message;
            snackbox.className = type + ' show';

            setTimeout(() => {
                snackbox.className = snackbox.className.replace(' show', '');
            }, 3000);
        }
    }

    function updateBadgeClass(button, newStatus) {
        let newClass = '';
        switch (newStatus) {
            case 'Paid':
                newClass = 'bg-light-success text-success';
                break;
            case 'Unpaid':
                newClass = 'bg-light-danger text-danger';
                break;
            case 'Partially Paid':
                newClass = 'bg-light-warning text-warning';
                break;
            default:
                newClass = 'bg-light-secondary text-secondary';
                break;
        }

        button.className = `btn ${newClass} dropdown-toggle`;
    }

    function updateInvoiceStatus(invoiceId, newStatus) {
        if (!confirm('Are you sure you want to change the invoice status to ' + newStatus + '?')) {
            return;
        }

        const url = "<?= base_url('dashboard/update_invoice_status'); ?>";
        fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    invoice_id: invoiceId,
                    payment_status: newStatus
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
                    const row = document.querySelector(`td[data-invoice-id="${invoiceId}"]`);
                    const button = row.querySelector('button');
                    button.textContent = newStatus.replace('_', ' ');
                    updateBadgeClass(button, newStatus);

                    showSnackbox(data.message, 'success');
                } else {
                    showSnackbox('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                showSnackbox('An error occurred during the update: ' + error.message, 'error');
            });
    }

    document.querySelectorAll('.send-to-shiprocket').forEach(button => {
        button.addEventListener('click', function() {
            const invoiceId = this.dataset.invoiceId;
            fetch('<?= base_url("Dashboard/send_to_shiprocket") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        invoice_id: invoiceId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.textContent = "Sent";
                        this.disabled = true;
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-success');
                        showSnackbox(data.message, 'success');
                    } else {
                        showSnackbox('Error: ' + data.message, 'error');
                    }
                })
                .catch(err => {
                    console.error('Fetch Error:', err);
                    showSnackbox('Error: ' + err.message, 'error');
                });
        });
    });
</script>