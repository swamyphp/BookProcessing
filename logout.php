<?php
require 'config/auth.php';
// record logout
if(!empty($_SESSION['user_id'])){
	record_activity('logout', ['ip'=>$_SERVER['REMOTE_ADDR'] ?? null]);
}
session_unset(); session_destroy(); header('Location: login.php'); exit;
