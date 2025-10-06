<div class="wrapper">
    <div class="">
        <div class="row g-0 m-0">
            <div class="col-xl-6 col-lg-12">
                <div class="login-cover-wrapper">
                    <div class="card shadow-none">
                        <div class="card-body">
                            <div class="text-center">
                                <h4>Sign In</h4>
                                <p>Sign In to your account</p>
                            </div>
                            <?php if ($this->session->flashdata('error')): ?>
                                <div class="alert alert-dismissible fade show py-2 bg-danger">
                                    <div class="d-flex align-items-center">
                                        <div class="fs-3 text-white"><ion-icon name="close-circle-sharp"></ion-icon>
                                        </div>
                                        <div class="ms-3">
                                            <div class="text-white"><?= $this->session->flashdata('error') ?></div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <?= form_open('welcome/login', ['class' => 'form-body row g-3']) ?>
                            <div class="col-12">
                                <label for="username" class="form-label">User name</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-12">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <div class="col-12 col-lg-12">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Sign In</button>
                                </div>
                            </div>
                            <?= form_close() ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-12">
                <div class="position-fixed top-0 h-100 d-xl-block d-none login-cover-img">
                </div>
            </div>
        </div>
    </div>
</div>