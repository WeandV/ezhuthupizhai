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
                    <form id="invoiceForm">
                        <div class="row">
                            <div class="col-12 border-bottom mb-4">
                                <h5 class="mb-3">Invoice Details</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="invoiceDate" class="form-label">Invoice Date</label>
                                        <input type="date" class="form-control" id="invoiceDate" value="<?= date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="invoiceNumber" class="form-label">Invoice Number</label>
                                        <input type="text" class="form-control" id="invoiceNumber" value="<?= $next_invoice_number; ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 border-bottom mb-4">
                                <h5 class="mb-3">Recipient Details</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="invoiceFor" class="form-label">Invoice For</label>
                                        <select class="form-select" id="invoiceFor">
                                            <option value="customer" selected>Customer</option>
                                            <option value="vendor">Vendor</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phoneNumber" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phoneNumber" placeholder="Enter phone number" list="recipient-suggestions">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="customerName" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="customerName" placeholder="Name" list="recipient-suggestions">
                                        <datalist id="recipient-suggestions"></datalist>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="customerEmail" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="customerEmail">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="billingAddress1" class="form-label">Billing Address</label>
                                        <input type="text" class="form-control" id="billingAddress1" placeholder="Address Line 1">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="billingAddress2" class="form-label invisible">Address 2</label>
                                        <input type="text" class="form-control" id="billingAddress2" placeholder="Address Line 2">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="state" class="form-label">State</label>
                                        <input type="text" class="form-control" id="state">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="pincode" class="form-label">Pincode</label>
                                        <input type="text" class="form-control" id="pincode">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 border-bottom mb-4">
                                <h5 class="mb-3">Payment Details</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="paymentStatus" class="form-label">Payment Status</label>
                                        <select class="form-select" id="paymentStatus">
                                            <option selected disabled>Select</option>
                                            <option>Paid</option>
                                            <option>Unpaid</option>
                                            <option>Partially Paid</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="paymentMode" class="form-label">Payment Mode</label>
                                        <select class="form-select" id="paymentMode">
                                            <option selected disabled>Select</option>
                                            <option>UPI</option>
                                            <option>Cash</option>
                                            <option>Account Transfered</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 border-bottom mb-4 pb-4">
                                <h5 class="mb-3">Invoice Items</h5>
                                <div id="invoice-items-container">
                                    <div class="row g-3 mb-3 invoice-item-row">
                                        <div class="col-md-3">
                                            <label for="item-0" class="form-label">Book</label>
                                            <select class="form-select item-select" id="item-0" data-index="0">
                                                <option selected disabled>Select a book</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="price-0" class="form-label">Price</label>
                                            <input type="number" step="0.01" class="form-control item-price" id="price-0" value="0.00" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="quantity-0" class="form-label">Quantity</label>
                                            <input type="number" class="form-control item-quantity" id="quantity-0" value="1" min="1">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="total-0" class="form-label">Total</label>
                                            <input type="text" class="form-control item-total" id="total-0" value="0.00" readonly>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary float-end" id="addItemBtn">Add Item</button>
                            </div>
                            <div class="col-12">
                                <div class="row g-3 justify-content-end">
                                    <div class="col-md-4 text-end">
                                        <label for="deliveryCharge" class="form-label">Delivery Charge</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" step="0.01" class="form-control" id="deliveryCharge" value="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <label for="flatDiscount" class="form-label">Flat Discount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" step="0.01" class="form-control" id="flatDiscount" value="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <label for="discount" class="form-label">Discount Percentage</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" id="discount" value="0.00">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <h5 class="mb-2">Total Amount: <span id="totalAmount">₹ 0.00</span></h5>
                                        <div class="d-flex justify-content-end align-items-center">
                                            <span class="text-secondary me-2">Discount:</span>
                                            <span id="discountAmount" class="text-danger">₹ 0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-end align-items-center mt-2">
                                            <span class="me-2">Delivery Charge:</span>
                                            <span id="deliveryChargeAmount" class="text-primary">₹ 0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-end align-items-center mt-2">
                                            <h5 class="me-2">Sub Total:</h5>
                                            <h5 id="subTotal" class="text-success">₹ 0.00</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mt-4 text-end">
                                <button type="submit" class="btn btn-success">Generate Invoice</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="snackbox"></div>