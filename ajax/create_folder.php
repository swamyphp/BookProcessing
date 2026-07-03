<?php
$id = $_POST['id'] ?? null; $path = $_POST['path'] ?? null;
if(!$id || !$path){ echo json_encode(['success'=>false,'error'=>'missing parameters']); exit; }
if(strpos($path,'..') !== false || strpos($path,'\0') !== false || strpos($path,'/') === 0 || strpos($path,'\\') !== false){
  echo json_encode(['success'=>false,'error'=>'invalid path']); exit;
}
$base = realpath(__DIR__ . '/..' . '/Temp/' . basename($id) . '/extracted');
if(!$base || !is_dir($base)){ echo json_encode(['success'=>false,'error'=>'invalid upload id']); exit; }
$full = $base . '/' . trim($path,'/');
if(!is_dir($full)){
  if(mkdir($full,0777,true)) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'error'=>'cannot create']);
} else echo json_encode(['success'=>false,'error'=>'exists']);
