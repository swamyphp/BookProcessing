<?php
require 'config/db.php'; require 'classes/User.php'; require 'config/auth.php'; require 'config/mail.php';
$pdo = get_db(); $svc = new User($pdo);
$error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email = $_POST['email'] ?? '';
  $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?'); $stmt->execute([$email]); $user = $stmt->fetch();
  if(!$user){ $error='If the email exists, a reset link will be sent.'; }
  else {
      $token = bin2hex(random_bytes(24));
      // check settings table for expiry minutes
      $minutes = 60;
      try{
        $s = $pdo->prepare('SELECT `value` FROM settings WHERE `key` = ? LIMIT 1'); $s->execute(['password_reset_expires_minutes']); $val = $s->fetchColumn();
        if($val) $minutes = (int)$val;
      }catch(Exception $e){ }
      $expires = date('Y-m-d H:i:s', time()+($minutes*60));
    $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?,?)');
    $stmt->execute([$user['id'],$token,$expires]);
    // Build reset link
    $link = (isset($_SERVER['HTTPS'])? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\') . "/reset_password.php?token=$token";
    // Attempt to send via PHPMailer if installed
    $sent = false; $mailError = null;
    if(file_exists(__DIR__ . '/vendor/autoload.php')){
      require __DIR__ . '/vendor/autoload.php';
      try{
        $cfg = get_mail_config();
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $cfg['smtp_host'];
        $mail->SMTPAuth = !empty($cfg['smtp_user']);
        if(!empty($cfg['smtp_user'])){ $mail->Username = $cfg['smtp_user']; $mail->Password = $cfg['smtp_pass']; }
        if(!empty($cfg['smtp_secure'])){ $mail->SMTPSecure = $cfg['smtp_secure']; }
        $mail->Port = $cfg['smtp_port'];
        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($user['email']);
        $mail->isHTML(true);
        $mail->Subject = 'Password reset request';
        $mail->Body = '<p>A password reset was requested for your account. Click the link below to reset your password:</p>';
        $mail->Body .= '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>';
        $mail->AltBody = "Reset your password: $link";
        $mail->send();
        $sent = true;
      }catch(Exception $e){ $mailError = $e->getMessage(); }
    }

    if($sent){
      $success = 'Password reset link sent to ' . htmlspecialchars($user['email']) . '.';
      record_user_log('password_reset_sent', ['user_id'=>$user['id']]);
    } else {
      // fallback: show link (for dev) and surface mail error if any
      $success = 'Password reset link: ' . $link;
      if($mailError) $success .= ' (mail error: '.htmlspecialchars($mailError).')';
      record_user_log('password_reset_requested', ['email'=>$email,'mail_error'=>$mailError]);
    }
  }
}
?><!doctype html><html><head><meta charset="utf-8"><title>Forgot Password</title></head><body>
<div class="container"><h4>Forgot Password</h4><?php if($error) echo '<div style="color:red">'.htmlspecialchars($error).'</div>'; if($success) echo '<div style="color:green">'.htmlspecialchars($success).'</div>'; ?>
<form method="post"><input name="email" placeholder="Email" required><button>Request Reset</button></form></div></body></html>
