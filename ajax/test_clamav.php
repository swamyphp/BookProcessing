<?php
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../config/auth.php';
require_role('admin');
header('Content-Type: application/json');
try{
  require_once __DIR__ . '/../classes/Scanner.php';
  $tmpDir = __DIR__ . '/../Temp'; if(!is_dir($tmpDir)) mkdir($tmpDir,0777,true);
  $tmpFile = $tmpDir . '/clamav_test_' . uniqid() . '.txt';
  file_put_contents($tmpFile, 'clamav test ' . date('c'));
  $res = Scanner::scanFile($tmpFile);
  @unlink($tmpFile);
  if($res['ok']){
    echo json_encode(['success'=>true,'message'=>'ClamAV available: '.$res['message'],'raw'=>$res['raw']]);
  } else {
    $msg = $res['infected'] ? 'Infected file detected' : 'Scan error: '.$res['message'];
    echo json_encode(['success'=>false,'message'=>$msg,'raw'=>$res['raw']]);
  }
}catch(Exception $e){ echo json_encode(['success'=>false,'message'=>'Exception: '.$e->getMessage()]); }
