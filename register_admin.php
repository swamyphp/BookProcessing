<?php
require 'config/db.php'; require 'classes/User.php';
$pdo = get_db(); $svc = new User($pdo);
$token = 'INIT_ADMIN_TOKEN'; // change or remove after creating admin
$error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(($_POST['token'] ?? '') !== $token){ $error='Invalid token'; }
  else {
    try{
      $uid = $svc->create($_POST['username'], $_POST['password'], 'admin');
      // assign role in user_roles table if exists
      try{
        $stmt = $pdo->prepare('INSERT IGNORE INTO roles (name) VALUES (?)'); $stmt->execute(['admin']);
        $stmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_name) VALUES (?,?)'); $stmt->execute([$uid,'admin']);
      }catch(Exception $e){ /* ignore */ }
      $success='Admin created';
    } catch(Exception $e){ $error=$e->getMessage(); }
  }
}
?><!doctype html><html><head><meta charset="utf-8"><title>Register Admin</title></head><body>
<div class="container mt-4"><h4>Register Admin</h4><?php if($error) echo '<div style="color:red">'.htmlspecialchars($error).'</div>';?><?php if($success) echo '<div style="color:green">'.htmlspecialchars($success).'</div>';?>
<form method="post">
  <input name="token" placeholder="token"><br>
  <input name="username" placeholder="username"><br>
  <input name="password" placeholder="password"><br>
  <button>Create</button>
</form></div></body></html>
