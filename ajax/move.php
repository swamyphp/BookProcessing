<?php
$src = $_POST['src'] ?? null; $dst = $_POST['dst'] ?? null;
if(!$src || !$dst){ echo json_encode(['success'=>false,'error'=>'missing']); exit; }
$fullSrc = __DIR__ . '/..' . $src; $fullDst = __DIR__ . '/..' . $dst;
if(!file_exists($fullSrc)){ echo json_encode(['success'=>false,'error'=>'src missing']); exit; }
if(is_dir(dirname($fullDst)) || mkdir(dirname($fullDst),0777,true)){
  if(rename($fullSrc, $fullDst)) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'error'=>'move failed']);
} else echo json_encode(['success'=>false,'error'=>'dest missing']);
