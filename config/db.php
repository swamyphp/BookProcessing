<?php
function get_db(){
  $host = '127.0.0.1'; $db = 'bookprocessing'; $user = 'root'; $pass = '';
  $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
  $opt = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
  return new PDO($dsn,$user,$pass,$opt);
}
