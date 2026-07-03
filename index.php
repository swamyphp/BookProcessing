<?php
require 'config/db.php'; require 'config/auth.php';
require_login();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Interactive Book Package Processing</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid p-3">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h3>Interactive Book Package Processing</h3>
		<div class="d-flex align-items-center">
			<form id="uploadZipForm" enctype="multipart/form-data" class="me-2">
				<input type="file" name="zipfile" accept=".zip" required>
				<button class="btn btn-primary btn-sm" type="submit">Upload ZIP</button>
			</form>
			<a href="dashboard.php" class="btn btn-outline-secondary btn-sm me-2">Dashboard</a>
			<?php if(current_user_role() === 'admin'): ?>
			  <a href="smtp_settings.php" class="btn btn-outline-secondary btn-sm me-2">SMTP Settings</a>
			<?php endif; ?>
			<a href="forgot_password.php" class="btn btn-outline-warning btn-sm me-2">Forgot Password</a>
			<a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<span>Folder Tree</span>
					<div id="breadcrumb" class="small text-muted"></div>
				</div>
				<div class="card-body" id="treeArea" style="height:600px;overflow:auto;"></div>
			</div>
		</div>
		<div class="col-md-8">
			<div class="card">
				<div class="card-header">File / Folder Details</div>
				<div class="card-body" id="detailArea">
					<p>Select a file from the tree to see actions.</p>
				</div>
			</div>

			<div class="card mt-3">
				<div class="card-header">Validation & Create Book</div>
				<div class="card-body">
					<div id="validationArea"></div>
					<div class="mb-3">
						<label>Book Short Name</label>
						<input id="bookShortName" class="form-control" placeholder="ABC101">
					</div>
					<button id="createBookBtn" class="btn btn-success" disabled>Create Book</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="previewModalLabel">File Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="previewContent" style="min-height:400px;">
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>

