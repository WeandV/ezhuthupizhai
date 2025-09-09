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
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateInventoryModal">Update Inventory</button>
                <div class="modal fade" id="updateInventoryModal" tabindex="-1" aria-labelledby="updateInventoryModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="updateInventoryModalLabel">Update Inventory</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="update-inventory-form">
                                    <div class="mb-4">
                                        <select class="form-select" id="product-select" data-placeholder="Choose a product" name="product_id">
                                            <option selected disabled>Select the Product</option>
                                            <?php foreach ($products as $product) : ?>
                                                <option value="<?= $product->id; ?>" data-stock="<?= $product->stock_quantity; ?>">
                                                    <?= $product->name; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1">#</span>
                                        <input type="number" class="form-control" placeholder="count" aria-label="count" aria-describedby="basic-addon1" name="stock" id="stock-count">
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" id="save-changes-btn">Save changes</button>
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
                        <table id="example2" class="table table-hover w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>Image</th>
                                    <th style="width: 400px">Product Name</th>
                                    <th>SKU</th>
                                    <th>Mrp Price</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory as $item) : ?>
                                    <tr>
                                        <td>
                                            <?php if ($item->product_main_image) : ?>
                                                <img src="<?= $item->product_main_image; ?>" alt="<?= $item->product_name; ?>" style="max-width: 100px;">
                                            <?php else : ?>
                                                No Image
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $item->product_name; ?></td>
                                        <td><?= $item->product_sku; ?></td>
                                        <td><?= $item->product_mrp_price; ?></td>
                                        <td style="<?= ($item->stock_quantity < 40) ? 'color: red; font-weight:900' : ''; ?>">
                                            <?= $item->stock_quantity; ?>
                                            <?php if ($item->stock_quantity < 20) : ?>
                                                <span class="badge bg-danger">low stock</span>
                                            <?php endif; ?>
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