<?php
// simple process trigger page
if($_SERVER['REQUEST_METHOD']==='POST'){
  $short = $_POST['short'] ?? '';
  $id = $_POST['id'] ?? '';
  header('Location: create_book.php'); exit;
}
header('Location: index.php');
