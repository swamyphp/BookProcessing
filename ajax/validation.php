<?php
require __DIR__ . '/../classes/Validator.php'; require __DIR__ . '/../config/db.php';
$id = $_GET['id'] ?? null; if(!$id) { echo json_encode(['error'=>'no id']); exit; }
$base = __DIR__ . '/..' . '/Temp/' . $id . '/extracted';
$rules = [];
// try to load rules from DB
try{ $pdo = get_db(); $stmt = $pdo->query('SELECT name,file_type,required FROM validation_rules'); foreach($stmt->fetchAll() as $r){ $rules[]=['name'=>$r['name'],'type'=>($r['file_type']=='folder'?'folder':'file'),'required'=>$r['required']]; } }catch(Exception $e){ /* fallback */ }
if(empty($rules)){
	$rules = [ ['name'=>'Manuscript.docx','type'=>'file','required'=>1], ['name'=>'Cover.jpg','type'=>'file','required'=>1], ['name'=>'TOC.docx','type'=>'file','required'=>1], ['name'=>'Images','type'=>'folder','required'=>1] ];
}
$v = new Validator($base); $res = $v->validateRules($rules); echo json_encode($res);
