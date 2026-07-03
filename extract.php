<?php
$require_login_placeholder = true;
require 'config/db.php'; require 'config/auth.php';
require_login();
$id = $_POST['id'] ?? $_GET['id'] ?? null; if(!$id){ echo json_encode(['success'=>false,'error'=>'no id']); exit; }
$base = __DIR__ . '/Temp/' . $id;
$pkg = null; foreach(scandir($base) as $f){ if(preg_match('/^package_/',$f)) { $pkg = $base.'/'.$f; break; } }
if(!$pkg || !file_exists($pkg)){ echo json_encode(['success'=>false,'error'=>'package missing']); exit; }

$dest = $base . '/extracted'; if(!is_dir($dest)) mkdir($dest,0777,true);
$z = new ZipArchive();
if($z->open($pkg) !== TRUE){ echo json_encode(['success'=>false,'error'=>'corrupt']); exit; }

// safe extraction: prevent traversal
for($i=0;$i<$z->numFiles;$i++){
  $name = $z->getNameIndex($i);
  $name = str_replace('\\','/',$name);
  if(preg_match('#(^|/)\.\.(/|$)#',$name)) continue; // skip unsafe
  $target = $dest . '/' . ltrim($name,'/');
  if(substr($target, -1) === '/' ) { if(!is_dir($target)) mkdir($target,0777,true); continue; }
  $dir = dirname($target); if(!is_dir($dir)) mkdir($dir,0777,true);
  copy('zip://' . $pkg . '#' . $name, $target);
}
$z->close();

// update upload_history status
// optional scan after extraction
try{
  $cfg = require __DIR__ . '/config/config.php';
  if(!empty($cfg['clamav_scan_after_extract'])){
    require_once __DIR__ . '/classes/Scanner.php';
    $scan = Scanner::scanDir($dest);
    if(!$scan['ok'] && $scan['infected']){
      try{ $pdo = get_db(); $stmt = $pdo->prepare('UPDATE upload_history SET status=?, storage_path=? WHERE extraction_id=?'); $stmt->execute(['failed', $pkg, $id]); }catch(Exception $e){ }
      echo json_encode(['success'=>false,'error'=>'Virus detected after extraction','details'=>$scan['message'],'raw'=>$scan['raw']]); exit;
    }
  }
}catch(Exception $e){ /* ignore */ }

try{ $pdo = get_db(); $stmt = $pdo->prepare('UPDATE upload_history SET status=?, storage_path=? WHERE extraction_id=?'); $stmt->execute(['extracted', $pkg, $id]); }catch(Exception $e){ }

echo json_encode(['success'=>true,'extraction_id'=>$id]);
