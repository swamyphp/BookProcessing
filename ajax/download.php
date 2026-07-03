<?php
$id = $_GET['id'] ?? null;
$path = $_GET['path'] ?? null;
if(!$id || !$path){ http_response_code(400); echo 'Missing id or path'; exit; }
$base = __DIR__ . '/..' . '/Temp/' . basename($id) . '/extracted/';
$full = realpath($base . ltrim($path,'/'));
if(!$full || strpos($full, realpath($base)) !== 0 || !is_file($full)){
  http_response_code(404); echo 'File not found'; exit;
}
$mime = mime_content_type($full) ?: 'application/octet-stream';
header('Content-Type: '.$mime);
header('Content-Disposition: attachment; filename="'.basename($full).'"');
header('Content-Length: '.filesize($full));
readfile($full);
