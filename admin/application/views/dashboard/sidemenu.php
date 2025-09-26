<aside class="sidebar-wrapper bg-light" data-simplebar="true">
    <div class="sidebar-header bg-light">
        <!-- <div>
            <img src="<?= base_url(); ?>assets/images/favicon.png" class="logo-icon" alt="logo icon">
        </div> -->
        <div>
            <img src="<?= base_url(); ?>assets/images/logo.png" class="logo-text" alt="logo icon" width="150px">
        </div>
    </div>
    <ul class="metismenu bg-white border shadow" id="menu" style="border-radius: 20px; margin: 80px 10px 10px;">
        <li>
            <a href="<?= base_url(); ?>dashboard">
                <div class="parent-icon">
                    <i class="fa-solid fa-house text-primary fs-6"></i>
                </div>
                <div class="menu-title">Dashboard</div>
            </a>
        </li>
        <li>
            <a href="<?= base_url(); ?>orders">
                <div class="parent-icon">
                    <i class="fa-solid fa-cart-shopping text-primary fs-6"></i>
                </div>
                <div class="menu-title">Sales</div>
            </a>
        </li>
        <li>
            <a href="<?= base_url(); ?>customers">
                <div class="parent-icon">
                    <i class="fa-solid fa-user text-primary fs-6"></i>
                </div>
                <div class="menu-title">Customers</div>
            </a>
        </li>
        <li>
            <a href="<?= base_url(); ?>inventory">
                <div class="parent-icon">
                    <i class="fa-solid fa-list-check text-primary fs-6"></i>
                </div>
                <div class="menu-title">Inventory</div>
            </a>
        </li>
        <li class="menu-label">In Store Sales</li>
        <li>
            <a href="<?= base_url(); ?>direct-sales">
                <div class="parent-icon">
                    <i class="fa-brands fa-salesforce text-primary fs-6"></i>
                </div>
                <div class="menu-title">Direct Sales</div>
            </a>
        </li>
        <li>
            <a href="<?= base_url(); ?>invoice-list">
                <div class="parent-icon">
                    <i class="fa-solid fa-file-invoice text-primary fs-6"></i>
                </div>
                <div class="menu-title">Invoices</div>
            </a>
        </li>
        <li>
            <a href="<?= base_url(); ?>dealer-list">
                <div class="parent-icon">
                    <i class="fa-brands fa-salesforce text-primary fs-6"></i>
                </div>
                <div class="menu-title">Dealer List</div>
            </a>
        </li>
    </ul>
</aside>
<header class="top-header bg-light">
    <nav class="navbar navbar-expand gap-3">
        <div class="toggle-icon">
            <ion-icon name="menu-outline"></ion-icon>
        </div>
        <div class="top-navbar-right ms-auto">
            <ul class="navbar-nav align-items-center">
                <!-- <li class="nav-item">
                    <a class="nav-link dark-mode-icon" href="javascript:;">
                        <div class="mode-icon border p-2 rounded-circle">
                            <ion-icon name="moon-outline"></ion-icon>
                        </div>
                    </a>
                </li> -->
                <li class="nav-item dropdown dropdown-user-setting">
                    <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="javascript:;" data-bs-toggle="dropdown">
                        <div class="user-setting">
                            <img src="<?= base_url();?>assets/images/manobharathi.jpg" class="user-img" alt="">
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="javascript:;">
                                <div class="d-flex flex-row align-items-center gap-2">
                                    <img src="<?= base_url();?>assets/images/manobharathi.jpg" alt="" class="rounded-circle" width="54" height="54">
                                    <div class="">
                                        <h6 class="mb-0 dropdown-user-name">Manobharathi</h6>
                                        <small class="mb-0 dropdown-user-designation text-secondary">CEO and Founder</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="javascript:;">
                                <div class="d-flex align-items-center">
                                    <div class="">
                                        <ion-icon name="person-outline"></ion-icon>
                                    </div>
                                    <div class="ms-3"><span>Profile</span></div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= base_url();?>../" target="_blank">
                                <div class="d-flex align-items-center">
                                    <div class="">
                                        <ion-icon name="globe-outline"></ion-icon>
                                    </div>
                                    <div class="ms-3"><span>Website</span></div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="logout">
                                <div class="d-flex align-items-center">
                                    <div class="">
                                        <ion-icon name="log-out-outline"></ion-icon>
                                    </div>
                                    <div class="ms-3"><span>Logout</span></div>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</header>