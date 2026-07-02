<?php
// Simple auth bootstrap
if(session_status()===PHP_SESSION_NONE) session_start();
function require_login(){
  if(empty($_SESSION['user_id'])){ header('Location: login.php'); exit; }
}
function current_user_id(){ return $_SESSION['user_id'] ?? null; }
function current_user_role(){ return $_SESSION['role'] ?? null; }
function require_role($role){ if(current_user_role() !== $role){ http_response_code(403); echo 'Forbidden'; exit; } }
