<?php
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../config/auth.php';
require_role('admin');
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim($data['email'] ?? '');
if(!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)){
  echo json_encode(['success'=>false,'message'=>'Invalid email address']); exit;
}
try{
  require __DIR__ . '/../config/mail.php';
  $cfg = get_mail_config();
  $sent = false; $err = null;
  if(file_exists(__DIR__ . '/../vendor/autoload.php')){
    require __DIR__ . '/../vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try{
      $mail->isSMTP();
      $mail->Host = $cfg['smtp_host'];
      $mail->SMTPAuth = !empty($cfg['smtp_user']);
      if(!empty($cfg['smtp_user'])){ $mail->Username = $cfg['smtp_user']; $mail->Password = $cfg['smtp_pass']; }
      if(!empty($cfg['smtp_secure'])) $mail->SMTPSecure = $cfg['smtp_secure'];
      $mail->Port = $cfg['smtp_port'];
      $mail->setFrom($cfg['from_email'], $cfg['from_name']);
      $mail->addAddress($email);
      $mail->isHTML(true);
      $mail->Subject = 'Test email from BookProcessing';
      $mail->Body = '<p>This is a test email sent from BookProcessing at '.date('Y-m-d H:i:s').'</p>';
      $mail->AltBody = 'This is a test email';
      $mail->send(); $sent = true;
    }catch(Exception $e){ $err = $e->getMessage(); }
  } else {
    // fallback to PHP mail()
    $sub = 'Test email from BookProcessing';
    $body = "This is a test email sent from BookProcessing at " . date('Y-m-d H:i:s');
    $headers = 'From: ' . ($cfg['from_email'] ?? 'no-reply@example.com') . "\r\n";
    if(mail($email, $sub, $body, $headers)) $sent = true; else $err = 'mail() failed';
  }
  if($sent){
    record_activity('smtp_test_sent', ['to'=>$email]);
    echo json_encode(['success'=>true,'message'=>'Test email sent to ' . $email]);
  } else {
    record_activity('smtp_test_failed', ['to'=>$email,'error'=>$err]);
    echo json_encode(['success'=>false,'message'=>'Failed to send test email: '.($err?:'unknown')]);
  }
}catch(Exception $e){
  echo json_encode(['success'=>false,'message'=>'Exception: ' . $e->getMessage()]);
}
