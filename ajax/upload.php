<?php
// small upload helper for replacing files
if(empty($_FILES['file'])){ echo json_encode(['success'=>false,'error'=>'no file']); exit; }
$id = $_POST['id'] ?? null; if(!$id){ echo json_encode(['success'=>false,'error'=>'missing id']); exit; }
$target = $_POST['target'] ?? null; if(!$target){ echo json_encode(['success'=>false,'error'=>'no target']); exit; }
if(strpos($target,'..') !== false || strpos($target,'\0') !== false || strpos($target,'/') === 0 || strpos($target,'\\') !== false){
  echo json_encode(['success'=>false,'error'=>'invalid target']); exit;
}
$base = realpath(__DIR__ . '/..' . '/Temp/' . basename($id) . '/extracted');
if(!$base || !is_dir($base)){ echo json_encode(['success'=>false,'error'=>'invalid upload id']); exit; }
$full = $base . '/' . trim($target,'/');
$dir = dirname($full);
if(realpath($dir) !== false && strpos(realpath($dir), $base) !== 0){ echo json_encode(['success'=>false,'error'=>'invalid target']); exit; }
if(!is_dir($dir)) mkdir($dir,0777,true);
if(move_uploaded_file($_FILES['file']['tmp_name'],$full)){
	echo json_encode(['success'=>true]);
} else {
	echo json_encode(['success'=>false,'error'=>'move failed']);
}
