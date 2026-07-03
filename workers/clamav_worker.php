<?php
// CLI worker: php workers/clamav_worker.php
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../classes/Scanner.php';
$cfg = require __DIR__ . '/../config/config.php';
$redisCfg = $cfg['redis'] ?? ['host'=>'127.0.0.1','port'=>6379,'queue_key'=>'clamav_jobs'];

echo "ClamAV worker starting...\n";

// connect to Redis (predis or ext-redis)
$client = null;
if(file_exists(__DIR__ . '/../vendor/autoload.php')){
  require __DIR__ . '/../vendor/autoload.php';
  if(class_exists('Predis\\Client')){ $client = new Predis\\Client(['host'=>$redisCfg['host'],'port'=>$redisCfg['port']]); }
}
if(!$client && class_exists('Redis')){ $r = new Redis(); $r->connect($redisCfg['host'],$redisCfg['port']); $client = $r; }
if(!$client){ echo "No Redis client available. Install predis/predis or ext-redis.\n"; exit(1); }

$queueKey = $redisCfg['queue_key'] ?? 'clamav_jobs';
while(true){
  try{
    // BRPOP style: block
    if($client instanceof Predis\\Client){ $res = $client->brpop([$queueKey], 5); if(!$res) { continue; } $payload = $res[1] ?? null; }
    else { $res = $client->brPop([$queueKey], 5); if(!$res) { continue; } $payload = $res[1] ?? null; }
    if(!$payload) continue;
    $job = json_decode($payload, true);
    if(!$job) continue;
    $id = $job['extraction_id'] ?? null; $qPath = $job['quarantine_path'] ?? null; $pkg = $job['package'] ?? null;
    echo "Processing job: $id\n";
    $scan = Scanner::scanDir($qPath . '/extracted');
    $pdo = get_db();
    if(!$scan['ok'] && $scan['infected']){
      // mark failed
      $stmt = $pdo->prepare('UPDATE upload_history SET status=? WHERE extraction_id=?'); $stmt->execute(['failed',$id]);
      // log processing_logs
      $stmt = $pdo->prepare('INSERT INTO processing_logs (processing_history_id, message, level) VALUES (NULL,?,?)'); $stmt->execute(["Virus detected in quarantine: " . $scan['message'],'error']);
      echo "Job $id infected: " . $scan['message'] . "\n";
      continue;
    }
    // clean: move from Quarantine/<id>/extracted to Temp/<id>/extracted
    $tempDest = __DIR__ . '/../Temp/' . $id . '/extracted';
    if(!is_dir(dirname($tempDest))) mkdir(dirname($tempDest),0777,true);
    $src = $qPath . '/extracted';
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach($it as $item){
      $destPath = $tempDest . substr($item->getPathname(), strlen($src));
      if($item->isDir()){
        if(!is_dir($destPath)) mkdir($destPath,0777,true);
      } else {
        if(!is_dir(dirname($destPath))) mkdir(dirname($destPath),0777,true);
        rename($item->getPathname(), $destPath);
      }
    }
    $it2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it2 as $item){ if($item->isFile()) @unlink($item->getPathname()); else @rmdir($item->getPathname()); }
    @rmdir($src);

    // update upload_history to extracted
    $stmt = $pdo->prepare('UPDATE upload_history SET status=?, storage_path=? WHERE extraction_id=?'); $stmt->execute(['extracted', $pkg, $id]);
    echo "Job $id cleaned and moved to Temp.\n";
  }catch(Exception $e){ echo "Worker exception: " . $e->getMessage() . "\n"; sleep(1); }
}
