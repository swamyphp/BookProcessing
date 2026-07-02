<?php
// handle zip upload and extract
require 'config/db.php'; require 'config/config.php'; require 'config/auth.php';
if(empty($_FILES['zipfile'])){ echo json_encode(['success'=>false,'error'=>'No file']); exit; }
$f = $_FILES['zipfile'];
if($f['error']){ echo json_encode(['success'=>false,'error'=>'Upload error']); exit; }
$cfg = require __DIR__ . '/config/config.php';
$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
if(!in_array($ext, ['zip'])){ echo json_encode(['success'=>false,'error'=>'Only zip allowed']); exit; }
if($f['size'] > ($cfg['upload_max_size'] ?? 200*1024*1024)){ echo json_encode(['success'=>false,'error'=>'File too large']); exit; }

$tmpRoot = $cfg['temp_dir'] ?? (__DIR__ . '/temp'); if(!is_dir($tmpRoot)) mkdir($tmpRoot,0777,true);
$id = uniqid('ex_');
$dest = $tmpRoot . '/' . $id;
mkdir($dest,0777,true);
$zipPath = $dest . '/' . basename($f['name']);
move_uploaded_file($f['tmp_name'],$zipPath);
$z = new ZipArchive();
if($z->open($zipPath)===TRUE){ $z->extractTo($dest.'/extracted'); $z->close();
	// record upload history
	try{ $pdo = get_db(); $stmt = $pdo->prepare('INSERT INTO upload_history (user_id, original_filename, storage_path, filesize, extraction_id, status) VALUES (?,?,?,?,?,?)'); $stmt->execute([current_user_id(), $f['name'], $zipPath, $f['size'], $id, 'extracted']); }catch(Exception $e){ }
	echo json_encode(['success'=>true,'extraction_id'=>$id]); }
else { echo json_encode(['success'=>false,'error'=>'Corrupted zip']); }
