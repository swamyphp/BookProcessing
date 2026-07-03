<?php
require 'config/db.php'; require 'classes/User.php'; require 'config/auth.php';
$pdo = get_db(); $svc = new User($pdo);
$error=''; $success='';
$token = $_GET['token'] ?? $_POST['token'] ?? null;
if(!$token){ echo 'Invalid token'; exit; }
$stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()'); $stmt->execute([$token]); $row = $stmt->fetch();
if(!$row){ echo 'Invalid or expired token'; exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){
  $pw = $_POST['password'] ?? ''; $pw2 = $_POST['password2'] ?? '';
  if($pw!==$pw2){ $error='Passwords do not match'; }
  else {
    $svc->setPassword($row['user_id'], $pw);
    $stmt = $pdo->prepare('UPDATE password_resets SET used=1 WHERE id=?'); $stmt->execute([$row['id']]);
    record_user_log('password_reset_completed', ['user_id'=>$row['user_id']]);
    $success='Password updated. You may <a href="login.php">login</a> now.';
  }
}
?><!doctype html><html><head><meta charset="utf-8"><title>Reset Password</title></head><body>
<div class="container"><h4>Reset Password</h4><?php if($error) echo '<div style="color:red">'.htmlspecialchars($error).'</div>'; if($success) echo '<div style="color:green">'.$success.'</div>'; ?>
<?php if(!$success):?>
<form method="post"><input type="hidden" name="token" value="<?php echo htmlspecialchars($token);?>">
  <div><input name="password" type="password" placeholder="New password" required></div>
  <div><input name="password2" type="password" placeholder="Confirm password" required></div>
  <button>Set Password</button>
</form>
<?php endif; ?></div></body></html>
