<?php
// return tree JSON for extracted package
$id = $_GET['id'] ?? null;
if(!$id){ echo json_encode(['error'=>'missing id']); exit; }
$base = __DIR__ . '/temp/' . basename($id) . '/extracted';
if(!is_dir($base)){ echo json_encode(['error'=>'missing extracted folder']); exit; }

function scan($path, $rel=''){
  $name = basename($path);
  if(is_dir($path)){
    $res = ['type'=>'folder','name'=>$name,'children'=>[]];
    foreach(scandir($path) as $f){ if($f=='.' || $f=='..') continue; $res['children'][] = scan($path.'/'.$f, $rel.'/'.$name); }
    return $res;
  } else {
    return ['type'=>'file','name'=>$name,'path'=>str_replace('\\','/', substr($path, strlen(__DIR__))), 'extraction_id'=>$id];
  }
}

$tree = scan($base,'');
echo json_encode(['tree'=>$tree]);
