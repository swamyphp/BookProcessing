<?php
require 'config/db.php'; require 'config/auth.php';
$id = $_POST['id'] ?? null; $short = $_POST['short'] ?? null;
if(!$id || !$short){ echo json_encode(['success'=>false,'error'=>'missing params']); exit; }
if(!preg_match('/^[A-Za-z0-9_-]+$/',$short)){ echo json_encode(['success'=>false,'error'=>'invalid short name']); exit; }

$src = __DIR__ . '/Temp/' . basename($id) . '/extracted'; if(!is_dir($src)){ echo json_encode(['success'=>false,'error'=>'missing extracted']); exit; }

$booksRoot = __DIR__ . '/books'; if(!is_dir($booksRoot)) mkdir($booksRoot,0777,true);
$dest = $booksRoot . '/' . $short; if(is_dir($dest)){ echo json_encode(['success'=>false,'error'=>'short name exists']); exit; }
mkdir($dest);

// copy recursively
function rcopy($src,$dst){
  $dir = opendir($src); @mkdir($dst);
  while(false!==($file=readdir($dir))){ if($file=='.'||$file=='..') continue; $s=$src.'/'.$file; $d=$dst.'/'.$file; if(is_dir($s)) rcopy($s,$d); else copy($s,$d); }
  closedir($dir);
}
rcopy($src,$dest);

// final validation
$manuscript = null; foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dest)) as $f){ if($f->isFile() && strcasecmp($f->getFilename(),'Manuscript.docx')==0) { $manuscript=$f->getPathname(); break; } }
if(!$manuscript){ echo json_encode(['success'=>false,'error'=>'manuscript missing']); exit; }

// run python processing
// prepare DB and processing history
$pdo = get_db();
$createdBy = current_user_id();
try{
  $pdo->beginTransaction();
  $ph = $pdo->prepare('INSERT INTO processing_history (book_id, status, output) VALUES (NULL, ?, NULL)');
  $ph->execute(['running']);
  $processingId = $pdo->lastInsertId();
  $pdo->commit();
}catch(Exception $e){ /* continue without processing history */ }

$cmd = 'python "' . __DIR__ . '/python/process_book.py" "' . $dest . '" "' . $short . '"';
$out = []; $ret=0; exec($cmd, $out, $ret);
$rawOut = implode("\n", $out);
$json = @json_decode($rawOut, true);

// update processing history
try{
  $upd = $pdo->prepare('UPDATE processing_history SET finished_at=NOW(), status=?, output=? WHERE id=?');
  $status = ($json && ($json['status'] ?? '')=='success') ? 'success' : 'failed';
  $upd->execute([$status, $rawOut, $processingId]);
}catch(Exception $e){ /* ignore */ }

if(!$json || ($json['status'] ?? '')!='success'){ echo json_encode(['success'=>false,'error'=>'python failed','detail'=>$rawOut]); exit; }

// insert book, chapters and files
try{
  $pdo->beginTransaction();
  $s = $pdo->prepare('INSERT INTO books (short_name,title,source_folder,total_chapters,total_files,created_by,status) VALUES (?,?,?,?,?,?,?)');
  $s->execute([$short, $json['title'] ?? $short, $dest, $json['chapters'], 0, $createdBy, 'created']);
  $bookId = $pdo->lastInsertId();

  // insert chapters
  $chapStmt = $pdo->prepare('INSERT INTO chapters (book_id,chapter_number,chapter_title,filename,status) VALUES (?,?,?,?,?)');
  $filesCount = 0;
  foreach($json['chapterList'] ?? [] as $ch){ $chapStmt->execute([$bookId, $ch['chapter'], $ch['title'], '', 'created']); }

  // catalog files
  $bf = $pdo->prepare('INSERT INTO book_files (book_id,filename,relative_path,file_size,file_type) VALUES (?,?,?,?,?)');
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dest));
  foreach($it as $f){ if($f->isFile()){ $rel = substr($f->getPathname(), strlen($dest)+1); $bf->execute([$bookId, $f->getFilename(), $rel, $f->getSize(), mime_content_type($f->getPathname())]); $filesCount++; } }

  // update total_files
  $u = $pdo->prepare('UPDATE books SET total_files=? WHERE id=?'); $u->execute([$filesCount, $bookId]);

  // link processing history to book
  $link = $pdo->prepare('UPDATE processing_history SET book_id=? WHERE id=?'); $link->execute([$bookId, $processingId]);

  $pdo->commit();
  echo json_encode(['success'=>true,'book_id'=>$bookId,'chapters'=>$json['chapters']]);
}catch(Exception $e){ $pdo->rollBack(); echo json_encode(['success'=>false,'error'=>'db error','detail'=>$e->getMessage()]); }
