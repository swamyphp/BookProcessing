<?php
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../config/auth.php';
$id = $_GET['id'] ?? null; if(!$id){ echo json_encode(['error'=>'no id']); exit; }
$base = __DIR__ . '/..' . '/Temp/' . $id;
$zipPath = $base . '/package_' ;
// find package file in folder
$pkg = null; foreach(scandir($base) as $f){ if(preg_match('/^package_/',$f)) { $pkg = $base.'/'.$f; break; } }
if(!$pkg || !file_exists($pkg)){ echo json_encode(['error'=>'package missing']); exit; }

$rules = [];
try{ $pdo = get_db(); $stmt = $pdo->query('SELECT name,file_type,required FROM validation_rules'); foreach($stmt->fetchAll() as $r){ $rules[]=['name'=>$r['name'],'type'=>($r['file_type']=='folder'?'folder':'file'),'required'=>$r['required']]; } }catch(Exception $e){ }
if(empty($rules)){
  $rules = [ ['name'=>'Manuscript.docx','type'=>'file','required'=>1], ['name'=>'Cover.jpg','type'=>'file','required'=>1], ['name'=>'TOC.docx','type'=>'file','required'=>1], ['name'=>'Images','type'=>'folder','required'=>1] ];
}

$z = new ZipArchive();
if($z->open($pkg) !== TRUE){ echo json_encode(['error'=>'corrupt']); exit; }
$entries = [];
for($i=0;$i<$z->numFiles;$i++){ $name = $z->getNameIndex($i); $entries[] = $name; }
$z->close();

$results = [];
$ok = true;
foreach($rules as $r){ $found=false; foreach($entries as $e){ $bn = ltrim($e, '/'); if($r['type']==='folder'){ if(strpos($bn, rtrim($r['name'],'/').'/')===0 || preg_match('#(^|/)' . preg_quote($r['name']) . '/#i',$bn)) { $found=true; break; } } else { if(strcasecmp(basename($bn), $r['name'])===0){ $found=true; break; } } }
  $status = $found ? 'found' : ($r['required'] ? 'missing' : 'optional'); if($status !== 'found') $ok = false;
  $results[] = ['name'=>$r['name'],'status'=>$status];
}

echo json_encode(['results'=>$results,'ok'=>$ok,'entriesCount'=>count($entries)]);
