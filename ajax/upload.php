<?php
// small upload helper for replacing files
if(empty($_FILES['file'])){ echo json_encode(['success'=>false,'error'=>'no file']); exit; }
$target = $_POST['target'] ?? null; if(!$target){ echo json_encode(['success'=>false,'error'=>'no target']); exit; }
$full = __DIR__ . '/..' . $target; move_uploaded_file($_FILES['file']['tmp_name'],$full);
echo json_encode(['success'=>true]);
