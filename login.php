<?php
require 'config/db.php'; require 'classes/User.php'; require 'config/auth.php';
$pdo = get_db(); $userSvc = new User($pdo);
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $u = $_POST['username'] ?? ''; $p = $_POST['password'] ?? '';
  $row = $userSvc->findByUsername($u);
  if($row && $userSvc->verifyPassword($row,$p)){
    $_SESSION['user_id']=$row['id']; $_SESSION['username']=$row['username']; $_SESSION['role']=$row['role']; header('Location: index.php'); exit;
  } else { $error='Invalid credentials'; }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light">
<div class="container py-5"><div class="col-md-4 mx-auto">
  <div class="card p-3">
    <h5 class="card-title">Login</h5>
    <?php if($error):?><div class="alert alert-danger"><?php echo htmlspecialchars($error);?></div><?php endif;?>
    <form method="post">
      <div class="mb-2"><input name="username" class="form-control" placeholder="Username" required></div>
      <div class="mb-2"><input name="password" type="password" class="form-control" placeholder="Password" required></div>
      <div class="d-flex justify-content-between"><button class="btn btn-primary">Login</button><a href="register_admin.php" class="btn btn-link">Register</a></div>
    </form>
  </div>
</div></div></body></html>
