<?php
$require_login_placeholder = true;
require 'config/db.php'; require 'config/auth.php';
require_login();
$id = $_POST['id'] ?? $_GET['id'] ?? null; if(!$id){ echo json_encode(['success'=>false,'error'=>'no id']); exit; }
$base = __DIR__ . '/Temp/' . $id;
$pkg = null; foreach(scandir($base) as $f){ if(preg_match('/^package_/',$f)) { $pkg = $base.'/'.$f; break; } }
if(!$pkg || !file_exists($pkg)){ echo json_encode(['success'=>false,'error'=>'package missing']); exit; }

// extract into quarantine for async scanning
$quarantineBase = __DIR__ . '/Quarantine/' . $id;
$dest = $quarantineBase . '/extracted'; if(!is_dir($dest)) mkdir($dest,0777,true);
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
// enqueue async scan job (Redis)
try{
  $cfg = require __DIR__ . '/config/config.php';
  $redisCfg = $cfg['redis'] ?? null;
  $job = ['extraction_id'=>$id, 'quarantine_path'=>$quarantineBase, 'package'=>$pkg, 'queued_at'=>date('c'), 'user_id'=>current_user_id()];
  $pushed = false;
  // try predis
  if(file_exists(__DIR__ . '/vendor/autoload.php')){
    require __DIR__ . '/vendor/autoload.php';
    if(class_exists('Predis\Client')){
      $client = new Predis\Client(['host'=>$redisCfg['host'] ?? '127.0.0.1','port'=>$redisCfg['port'] ?? 6379]);
        $client->lpush($redisCfg['queue_key'] ?? 'clamav_jobs', json_encode($job));
      $pushed = true;
    }
  }
  // fallback to ext-redis
  if(!$pushed && class_exists('Redis')){
    $r = new Redis(); $r->connect($redisCfg['host'] ?? '127.0.0.1', $redisCfg['port'] ?? 6379);
    $r->lPush($redisCfg['queue_key'] ?? 'clamav_jobs', json_encode($job));
    $pushed = true;
  }
  // update upload_history status to quarantined
  try{ $pdo = get_db(); $stmt = $pdo->prepare('UPDATE upload_history SET status=?, storage_path=? WHERE extraction_id=?'); $stmt->execute(['quarantined', $pkg, $id]); }catch(Exception $e){ }
  if($pushed){ echo json_encode(['success'=>true,'queued'=>true,'extraction_id'=>$id]); }
  else { echo json_encode(['success'=>false,'error'=>'Queue push failed']); }
}catch(Exception $e){ echo json_encode(['success'=>false,'error'=>'Exception: '.$e->getMessage()]); }
