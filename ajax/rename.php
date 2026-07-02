<?php
// rename AJAX wrapper
require __DIR__ . '/../config/config.php';
$post = $_POST; $path = $_POST['path'] ?? ''; $new = $_POST['newname'] ?? '';
$full = __DIR__ . '/..' . $path;
if(!file_exists($full)){ echo json_encode(['success'=>false,'error'=>'missing']); exit; }
rename($full, dirname($full).'/'.basename($new));
echo json_encode(['success'=>true]);
