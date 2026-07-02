<?php
// small upload helper for replacing files
if(empty($_FILES['file'])){ echo json_encode(['success'=>false,'error'=>'no file']); exit; }
$target = $_POST['target'] ?? null; if(!$target){ echo json_encode(['success'=>false,'error'=>'no target']); exit; }
$full = __DIR__ . '/..' . $target;
$dir = dirname($full);
if(!is_dir($dir)) mkdir($dir,0777,true);
if(move_uploaded_file($_FILES['file']['tmp_name'],$full)){
	echo json_encode(['success'=>true]);
} else {
	echo json_encode(['success'=>false,'error'=>'move failed']);
}
