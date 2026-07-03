<?php
// handle zip upload and extract
require 'config/db.php'; require 'config/config.php'; require 'config/auth.php';
require 'classes/Scanner.php';
if(empty($_FILES['zipfile'])){ echo json_encode(['success'=>false,'error'=>'No file']); exit; }
$f = $_FILES['zipfile'];
if($f['error']){ echo json_encode(['success'=>false,'error'=>'Upload error']); exit; }
$cfg = require __DIR__ . '/config/config.php';
$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
if(!in_array($ext, ['zip'])){ echo json_encode(['success'=>false,'error'=>'Only zip allowed']); exit; }
if($f['size'] > ($cfg['upload_max_size'] ?? 200*1024*1024)){ echo json_encode(['success'=>false,'error'=>'File too large']); exit; }

$tmpRoot = $cfg['temp_dir'] ?? (__DIR__ . '/Temp'); if(!is_dir($tmpRoot)) mkdir($tmpRoot,0777,true);
$id = uniqid('ex_');
$dest = $tmpRoot . '/' . $id;
mkdir($dest,0777,true);
$zipPath = $dest . '/package_' . basename($f['name']);
// compute checksum for duplicate detection
$checksum = hash_file('sha256', $f['tmp_name']);
// check duplicates
try{
	$pdo = get_db();
	$stmt = $pdo->prepare('SELECT extraction_id, storage_path FROM upload_history WHERE checksum = ? AND filesize = ? LIMIT 1');
	$stmt->execute([$checksum, $f['size']]);
	$dup = $stmt->fetch();
	if($dup){ echo json_encode(['success'=>false,'duplicate'=>true,'message'=>'Duplicate upload','extraction_id'=>$dup['extraction_id']]); exit; }
}catch(Exception $e){ /* ignore DB errors */ }

if(!move_uploaded_file($f['tmp_name'],$zipPath)){ echo json_encode(['success'=>false,'error'=>'move failed']); exit; }

// Quick zip sanity check (can we open it?)
$z = new ZipArchive();
if($z->open($zipPath) !== TRUE){ echo json_encode(['success'=>false,'error'=>'Corrupted zip']); unlink($zipPath); exit; }
$z->close();

// optional ClamAV scan
try{
	$cfg = require __DIR__ . '/config/config.php';
	if(!empty($cfg['clamav_scan'])){
		$scan = Scanner::scanFile($zipPath);
		if(!$scan['ok'] && $scan['infected']){
			// record failed upload
			try{ $pdo = get_db(); $stmt = $pdo->prepare('INSERT INTO upload_history (user_id, original_filename, storage_path, checksum, filesize, extraction_id, status) VALUES (?,?,?,?,?,?,?)'); $stmt->execute([current_user_id(), $f['name'], $zipPath, $checksum, $f['size'], $id, 'failed']); }catch(Exception $e){ }
			unlink($zipPath);
			echo json_encode(['success'=>false,'error'=>'Virus detected in archive','details'=>$scan['message']]); exit;
		}
	}
}catch(Exception $e){ /* ignore scanner errors */ }

// record upload history (status=uploaded). Extraction will be performed by extract.php
try{ $pdo = get_db(); $stmt = $pdo->prepare('INSERT INTO upload_history (user_id, original_filename, storage_path, checksum, filesize, extraction_id, status) VALUES (?,?,?,?,?,?,?)'); $stmt->execute([current_user_id(), $f['name'], $zipPath, $checksum, $f['size'], $id, 'uploaded']); }catch(Exception $e){ }

echo json_encode(['success'=>true,'extraction_id'=>$id]);
