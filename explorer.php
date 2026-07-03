<?php
// explorer with optional lazy-loading of a folder's children
$id = $_GET['id'] ?? null;
if(!$id){ echo json_encode(['error'=>'missing id']); exit; }
$base = __DIR__ . '/Temp/' . basename($id) . '/extracted';
if(!is_dir($base)){ echo json_encode(['error'=>'missing extracted folder']); exit; }

$path = trim($_GET['path'] ?? '', '/');
$target = $base . ($path?('/' . $path) : '');
if(!is_dir($target) && !is_file($target)){
  echo json_encode(['error'=>'path missing','path'=>$target]); exit;
}

function listChildren($dir, $baseDir, $id, $relPrefix=''){
  $out = [];
  foreach(scandir($dir) as $f){
    if($f=='.' || $f=='..') continue;
    $full = $dir . '/' . $f;
    $rel = ($relPrefix? $relPrefix . '/' : '') . $f;
    if(is_dir($full)){
      $out[]=['type'=>'folder','name'=>$f,'path'=>$rel,'extraction_id'=>$id];
    } else {
      $out[]=['type'=>'file','name'=>$f,'path'=>$rel,'extraction_id'=>$id];
    }
  }
  return $out;
}

$children = listChildren($target, $base, $id, $path);

// build breadcrumb array from path
$breadcrumb = [];
$acc = '';
if($path===''){
  $breadcrumb[]=['name'=>'Package Root','path'=>''];
} else {
  $breadcrumb[]=['name'=>'Package Root','path'=>''];
  $parts = explode('/', $path);
  foreach($parts as $p){ $acc = $acc === '' ? $p : ($acc . '/' . $p); $breadcrumb[]=['name'=>$p,'path'=>$acc]; }
}

echo json_encode(['children'=>$children,'path'=>$path,'breadcrumb'=>$breadcrumb]);
