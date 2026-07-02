<?php
// rename, replace, delete, validate
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$id = $_REQUEST['id'] ?? null; if(!$id){ echo json_encode(['error'=>'no id']); exit; }
$base = __DIR__ . '/temp/' . basename($id) . '/extracted';
if(!is_dir($base)){ echo json_encode(['error'=>'missing extracted']); exit; }

function relPath($full){ return str_replace('\\','/', $full); }

if($action=='rename'){
  $path = $_POST['path']; $name = $_POST['name']; $new = $_POST['newname'];
  $full = __DIR__ . $path; $newfull = dirname($full).'/'.basename($new);
  if(!file_exists($full)){ echo json_encode(['error'=>'not found']); exit; }
  rename($full,$newfull);
  echo json_encode(['success'=>true]); exit;
}

if($action=='replace'){
  if(empty($_FILES['file'])){ echo json_encode(['error'=>'no file']); exit; }
  $path = $_POST['path']; $full = __DIR__ . $path;
  if(!file_exists($full)){ echo json_encode(['error'=>'target missing']); exit; }
  move_uploaded_file($_FILES['file']['tmp_name'],$full);
  echo json_encode(['success'=>true]); exit;
}

if($action=='delete'){
  $path = $_POST['path']; $full = __DIR__ . $path;
  if(file_exists($full)) { unlink($full); echo json_encode(['success'=>true]); }
  else echo json_encode(['error'=>'missing']);
  exit;
}

if($action=='validate'){
  // simple required list
  $required = ['Manuscript.docx','Cover.jpg','TOC.docx'];
  $results = [];
  foreach($required as $r){ $found = false; $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base)); foreach($it as $f){ if($f->isFile() && strcasecmp($f->getFilename(), $r)==0) { $found=true; break; } }
    $results[]=['name'=>$r,'ok'=>$found]; }
  $ok = array_reduce($results, function($carry,$i){ return $carry && $i['ok']; }, true);
  echo json_encode(['results'=>$results,'ok'=>$ok]); exit;
}

echo json_encode(['error'=>'unknown action']);
