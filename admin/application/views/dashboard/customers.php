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
        </div>
        <div class="container-fluid">
            <div class="card shadow-none">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example2" class="table table-hover w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>id#</th>
                                    <th>Customer Name</th>
                                    <th>Email ID</th>
                                    <th>Phone Number</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)) : ?>
                                    <?php foreach ($users as $user) : ?>
                                        <tr>
                                            <td><?= $user->id; ?></td>
                                            <td><?= $user->first_name . ' ' . $user->last_name; ?></td>
                                            <td><?= $user->email; ?></td>
                                            <td><?= $user->phone; ?></td>
                                            <td>
                                                <a href="<?= base_url('user-details/' . $user->id); ?>" class="btn btn-primary btn-sm">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No customers found.</td>
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