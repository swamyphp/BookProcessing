<?php
$path = $_POST['path'] ?? null; if(!$path){ echo json_encode(['success'=>false,'error'=>'no path']); exit; }
$full = __DIR__ . '/..' . $path; if(file_exists($full)){ unlink($full); echo json_encode(['success'=>true]); } else echo json_encode(['success'=>false,'error'=>'missing']);
