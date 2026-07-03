<?php
// Simple auth bootstrap
if(session_status()===PHP_SESSION_NONE) session_start();

// session timeout in seconds (30 minutes default)
if(!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 30*60);

function require_login(){
  if(empty($_SESSION['user_id'])){ header('Location: login.php'); exit; }
  // session timeout check
  if(!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT){
    // timeout
    $uid = $_SESSION['user_id'];
    session_unset(); session_destroy();
    header('Location: login.php?expired=1'); exit;
  }
  $_SESSION['last_activity'] = time();
}
function current_user_id(){ return $_SESSION['user_id'] ?? null; }
function current_user_role(){ return $_SESSION['role'] ?? null; }
function require_role($role){ if(current_user_role() !== $role){ http_response_code(403); echo 'Forbidden'; exit; } }

function record_activity($action, $context=null){
  try{
    if(!function_exists('get_db')) return;
    $pdo = get_db();
    $stmt = $pdo->prepare('INSERT INTO activity_logs (user_id, action, context) VALUES (?,?,?)');
    $ctx = $context ? json_encode($context) : null;
    $stmt->execute([current_user_id(), $action, $ctx]);
  }catch(Exception $e){ /* ignore logging errors */ }
}

function record_user_log($event, $meta=null){
  try{
    if(!function_exists('get_db')) return;
    $pdo = get_db();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare('INSERT INTO user_logs (user_id, event, ip_address, user_agent) VALUES (?,?,?,?)');
    $stmt->execute([current_user_id(), $event, $ip, $ua]);
  }catch(Exception $e){ /* ignore */ }
}

