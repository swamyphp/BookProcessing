<?php
$path = $_POST['path'] ?? null; if(!$path){ echo json_encode(['success'=>false,'error'=>'no path']); exit; }
$full = __DIR__ . '/..' . $path; if(!is_dir($full)){
  if(mkdir($full,0777,true)) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'error'=>'cannot create']);
} else echo json_encode(['success'=>false,'error'=>'exists']);
