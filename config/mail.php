<?php
// Mail configuration — edit for your SMTP server
function get_mail_config(){
  $cfg = [
    'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.example.com',
    'smtp_port' => getenv('SMTP_PORT') ?: 587,
    'smtp_user' => getenv('SMTP_USER') ?: 'user@example.com',
    'smtp_pass' => getenv('SMTP_PASS') ?: 'secret',
    'smtp_secure' => getenv('SMTP_SECURE') ?: 'tls', // tls or ssl or empty
    'from_email' => getenv('MAIL_FROM') ?: 'no-reply@example.com',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'BookProcessing'
  ];
  // If settings table exists, override from DB values
  try{
    if(function_exists('get_db')){
      $pdo = get_db();
      $keys = ['smtp_host','smtp_port','smtp_user','smtp_pass','smtp_secure','from_email','from_name'];
      $placeholders = implode(',', array_fill(0, count($keys), '?'));
      $stmt = $pdo->prepare('SELECT `key`,`value` FROM settings WHERE `key` IN ('.$placeholders.')');
      $stmt->execute($keys);
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        if(array_key_exists($row['key'], $cfg)) $cfg[$row['key']] = $row['value'];
      }
    }
  }catch(Exception $e){ /* ignore */ }
  return $cfg;
}
