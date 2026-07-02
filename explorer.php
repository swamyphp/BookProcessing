<?php
// explorer with optional lazy-loading of a folder's children
$id = $_GET['id'] ?? null;
if(!$id){ echo json_encode(['error'=>'missing id']); exit; }
$base = __DIR__ . '/Temp/' . basename($id) . '/extracted';
if(!is_dir($base)){ echo json_encode(['error'=>'missing extracted folder']); exit; }

$path = $_GET['path'] ?? '';
$target = $base . ($path?('/' . trim($path, '/')) : '');
if(!is_dir($target) && !is_file($target)){
  echo json_encode(['error'=>'path missing','path'=>$target]); exit;
}

function listChildren($dir, $baseDir, $id){
  $out = [];
  foreach(scandir($dir) as $f){ if($f=='.' || $f=='..') continue; $full = $dir . '/' . $f; if(is_dir($full)) { $out[]=['type'=>'folder','name'=>$f,'path'=>str_replace('\\','/', substr($full, strlen(__DIR__))) ,'extraction_id'=>$id]; } else { $out[]=['type'=>'file','name'=>$f,'path'=>str_replace('\\','/', substr($full, strlen(__DIR__))) ,'extraction_id'=>$id]; } }
  return $out;
}

$children = listChildren($target, $base, $id);
echo json_encode(['children'=>$children]);
