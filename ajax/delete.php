<?php
$id = $_POST['id'] ?? null; $path = $_POST['path'] ?? null;
if(!$id || !$path){ echo json_encode(['success'=>false,'error'=>'missing parameters']); exit; }
$full = __DIR__ . '/..' . '/Temp/' . basename($id) . '/extracted/' . ltrim($path,'/');
if(file_exists($full)){
  unlink($full);
  echo json_encode(['success'=>true]);
} else {
  echo json_encode(['success'=>false,'error'=>'missing']);
}
