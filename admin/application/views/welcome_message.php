<html lang="en">

<head>
	<title>EP Admin Dashboard</title>
	<link rel="icon" href="<?= base_url(); ?>admin/assets/images/favicon.ico" type="image/png" />
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="<?= base_url(); ?>assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet" />
	<link href="<?= base_url(); ?>assets/plugins/simplebar/css/simplebar.css" rel="stylesheet" />
	<link href="<?= base_url(); ?>assets/plugins/metismenu/css/metisMenu.min.css" rel="stylesheet" />
	<link href="<?= base_url(); ?>assets/plugins/datatable/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
	<link href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.css" rel="stylesheet" />
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
	<link rel="stylesheet"
		href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
	<link href="<?= base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet">
	<link href="<?= base_url(); ?>assets/css/bootstrap-extended.css" rel="stylesheet">
	<link href="<?= base_url(); ?>assets/css/style.css" rel="stylesheet">
	<link href="<?= base_url(); ?>assets/css/custom.css" rel="stylesheet">
	<link href="<?= base_url(); ?>assets/css/icons.css" rel="stylesheet">
	<link href="<?= base_url(); ?>assets/css/dark-theme.css" rel="stylesheet" />
	<link href="<?= base_url(); ?>assets/css/semi-dark.css" rel="stylesheet" />
	<link href="<?= base_url(); ?>assets/css/header-colors.css" rel="stylesheet" />
	<link
		href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap"
		rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
</head>

<body class="bg-light">
	<div class="wrapper">
		<?php $this->load->view($viewpage); ?>
		<div class="overlay nav-toggle-icon"></div>
	</div>

	<script src="<?= base_url(); ?>assets/js/jquery.min.js"></script>
	<script src="<?= base_url(); ?>assets/plugins/simplebar/js/simplebar.min.js"></script>
	<script src="<?= base_url(); ?>assets/plugins/metismenu/js/metisMenu.min.js"></script>
	<script src="<?= base_url(); ?>assets/js/bootstrap.bundle.min.js"></script>
	<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
	<script src="<?= base_url(); ?>assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script src="<?= base_url(); ?>assets/plugins/select2/js/select2-custom.js"></script>
	<script src="<?= base_url(); ?>assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
	<script src="<?= base_url(); ?>assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
	<script src="<?= base_url(); ?>assets/js/table-datatable.js"></script>
	<script src="<?= base_url(); ?>assets/js/index.js"></script>
	<script src="<?= base_url(); ?>assets/js/main.js"></script>

	<script>
		var baseUrl = "<?= base_url(); ?>";
		var appBaseUrl = "<?= base_url(); ?>";
		var addVendorUrl = "<?= site_url("dashboard/add_vendor"); ?>";
		var updateStockUrl = "<?= site_url("dashboard/update_stock_ajax"); ?>";
		var checkVendorExistsUrl = "<?= site_url("dashboard/check_vendor_exists"); ?>";
	</script>
	<script src="<?= base_url('assets/js/vendor-management.js'); ?>" defer></script>
	<script src="<?= base_url('assets/js/invoice-management.js'); ?>"></script>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/js/all.min.js"></script>
</body>

</html>