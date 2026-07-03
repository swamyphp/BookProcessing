<?php
/**
 * Seeder script for initial roles, admin user, and validation rules.
 * Usage (CLI): php seed.php --username=admin --password=Secret123
 */
require __DIR__ . '/config/db.php';

$opts = getopt('', ['username::','password::']);
$username = $opts['username'] ?? 'admin';
$password = $opts['password'] ?? 'ChangeMe123!';

try{
  $pdo = get_db();
  $pdo->beginTransaction();

  // roles
  $roles = [ 'admin'=>'Full administrator', 'editor'=>'Edit books and metadata', 'uploader'=>'Upload packages', 'viewer'=>'Read-only' ];
  $insRole = $pdo->prepare('INSERT INTO roles (name, description) VALUES (?,?) ON DUPLICATE KEY UPDATE name=name');
  foreach($roles as $r=>$d) $insRole->execute([$r,$d]);

  // create user if not exists
  $stmt = $pdo->prepare('SELECT id FROM users WHERE username=?'); $stmt->execute([$username]);
  $user = $stmt->fetch();
  if(!$user){
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO users (username,password_hash,role,email) VALUES (?,?,?,?)');
    $ins->execute([$username, $hash, 'admin', $username.'@example.com']);
    $userId = $pdo->lastInsertId();
    echo "Created user: $username (ID: $userId)\n";
  } else { $userId = $user['id']; echo "User exists: $username (ID: $userId)\n"; }

  // ensure user_roles mapping
  $roleId = $pdo->prepare('SELECT id FROM roles WHERE name=?'); $roleId->execute(['admin']); $rid = $roleId->fetchColumn();
  if($rid){
    $map = $pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?,?)');
    $map->execute([$userId, $rid]);
  }

  // validation rules (only insert if not exists)
  $rules = [ ['Manuscript.docx','Manuscript.docx',1,'file'], ['TOC.docx','TOC.docx',1,'file'], ['Cover.jpg','Cover.jpg',1,'file'], ['Images','Images',1,'folder'] ];
  $check = $pdo->prepare('SELECT COUNT(*) FROM validation_rules WHERE name=?');
  $insR = $pdo->prepare('INSERT INTO validation_rules (name, pattern, required, file_type) VALUES (?,?,?,?)');
  foreach($rules as $r){ $check->execute([$r[0]]); if($check->fetchColumn()==0) $insR->execute([$r[0], $r[1], $r[2], $r[3]]); }

  $pdo->commit();
  echo "Seeding completed.\n";
}catch(Exception $e){
  if(isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  echo "Error: " . $e->getMessage() . "\n";
}
