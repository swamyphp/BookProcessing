<?php
require 'config/db.php'; require 'config/auth.php';
require_login();
$pdo = get_db();
$totalBooks = $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$completed = $pdo->query("SELECT COUNT(*) FROM processing_history WHERE status='success'")->fetchColumn();
$errors = $pdo->query('SELECT COUNT(*) FROM error_logs')->fetchColumn();
$todayUploads = $pdo->query("SELECT COUNT(*) FROM upload_history WHERE DATE(uploaded_at)=CURDATE()")->fetchColumn();
$recent = $pdo->query('SELECT id,short_name,title,created_at FROM books ORDER BY created_at DESC LIMIT 5')->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3"><h2>Dashboard</h2><a href="logout.php" class="btn btn-sm btn-outline-secondary">Logout</a></div>
  <div class="row">
    <div class="col-md-3"><div class="card p-3">Total Books<br><h3><?php echo $totalBooks;?></h3></div></div>
    <div class="col-md-3"><div class="card p-3">Completed<br><h3><?php echo $completed;?></h3></div></div>
    <div class="col-md-3"><div class="card p-3">Errors<br><h3><?php echo $errors;?></h3></div></div>
    <div class="col-md-3"><div class="card p-3">Today's Uploads<br><h3><?php echo $todayUploads;?></h3></div></div>
  </div>
  <div class="mt-4">
    <h5>Recent Books</h5>
    <table class="table table-sm"><thead><tr><th>ID</th><th>Short</th><th>Title</th><th>Created</th></tr></thead><tbody>
    <?php foreach($recent as $r):?><tr><td><?php echo $r['id'];?></td><td><?php echo htmlspecialchars($r['short_name']);?></td><td><?php echo htmlspecialchars($r['title']);?></td><td><?php echo $r['created_at'];?></td></tr><?php endforeach; ?>
    </tbody></table>
  </div>
</div>
</body></html>
