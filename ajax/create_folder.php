<?php
$id = $_POST['id'] ?? null; $path = $_POST['path'] ?? null; if(!$id || !$path){ echo json_encode(['success'=>false,'error'=>'missing parameters']); exit; }
$base = __DIR__ . '/..' . '/Temp/' . basename($id) . '/extracted';
$target = $base . '/' . trim($path,'/');
if(!is_dir($target)){
  if(mkdir($target,0777,true)) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'error'=>'cannot create']);
} else echo json_encode(['success'=>false,'error'=>'exists']);
