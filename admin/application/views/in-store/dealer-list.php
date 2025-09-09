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
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVendorModal">Add Dealer</button>
                <div class="modal fade" id="addVendorModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add New Dealer</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="addVendorForm" class="row g-3" novalidate>
                                    <div class="col-md-12">
                                        <label class="form-label">Dealer Name</label>
                                        <input type="text" class="form-control" name="name" placeholder="Dealer Name" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Contact Person Name</label>
                                        <input type="text" class="form-control" name="contact_person" placeholder="Contact Person Name" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" name="phone" placeholder="Phone" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Address</label>
                                        <input type="text" class="form-control mb-2" name="address_line1" placeholder="Address Line 1" required>
                                        <div class="invalid-feedback"></div>
                                        <input type="text" class="form-control" name="address_line2" placeholder="Address Line 2">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city" placeholder="City" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">State</label>
                                        <input type="text" class="form-control" name="state" placeholder="State" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Country</label>
                                        <input type="text" class="form-control" name="country" placeholder="Country" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Pincode</label>
                                        <input type="text" class="form-control" name="pincode" placeholder="Pincode" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-md-flex d-grid align-items-center gap-3">
                                            <button type="submit" class="btn btn-primary px-4">Submit</button>
                                            <button type="reset" class="btn btn-light px-4">Reset</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="card shadow-none">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>id</th>
                                    <th>Name</th>
                                    <th>Contact Person</th>
                                    <th>Email</th>
                                    <th>Phone No</th>
                                    <th>Address</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendors as $dealer) : ?>
                                    <?php $this->load->view('in-store/dealer_row_template', ['dealer' => $dealer]); ?>
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


