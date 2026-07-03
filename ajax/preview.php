<?php
$id = $_GET['id'] ?? null;
$path = $_GET['path'] ?? null;
if(!$id || !$path){ http_response_code(400); echo 'Missing id or path'; exit; }
$base = __DIR__ . '/..' . '/Temp/' . basename($id) . '/extracted/';
$full = realpath($base . ltrim($path,'/'));
if(!$full || strpos($full, realpath($base)) !== 0 || !is_file($full)){
  http_response_code(404); echo 'File not found'; exit;
}
$ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
$previewTypes = ['jpg','jpeg','png','pdf','xml','css','txt'];
if(!in_array($ext, $previewTypes, true)){
  http_response_code(415); echo 'Preview not supported for this file type.'; exit;
}
$mime = mime_content_type($full) ?: 'application/octet-stream';
header('Content-Type: '.$mime);
if(in_array($ext, ['xml','css','txt'], true)){
  header('Content-Disposition: inline; filename="'.basename($full).'"');
  echo file_get_contents($full);
  exit;
}
if($ext === 'pdf'){
  header('Content-Disposition: inline; filename="'.basename($full).'"');
  readfile($full);
  exit;
}
// Images
if(in_array($ext, ['jpg','jpeg','png'], true)){
  header('Content-Disposition: inline; filename="'.basename($full).'"');
  readfile($full);
  exit;
}
http_response_code(415);
echo 'Preview not supported.';
