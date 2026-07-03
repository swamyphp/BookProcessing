<?php
class User{
  protected $pdo;
  public function __construct(PDO $pdo){ $this->pdo=$pdo; }
  public function findByUsername($u){ $s=$this->pdo->prepare('SELECT * FROM users WHERE username=?'); $s->execute([$u]); return $s->fetch(); }
  public function verifyPassword($userRow, $password){ return password_verify($password, $userRow['password_hash']); }
  public function create($username,$password,$role='uploader'){
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $s=$this->pdo->prepare('INSERT INTO users (username,password_hash,role,created_at) VALUES (?,?,?,NOW())'); $s->execute([$username,$hash,$role]); return $this->pdo->lastInsertId(); }
  public function findById($id){ $s=$this->pdo->prepare('SELECT * FROM users WHERE id=?'); $s->execute([$id]); return $s->fetch(); }
  public function setPassword($userId, $newPassword){ $hash = password_hash($newPassword, PASSWORD_DEFAULT); $s=$this->pdo->prepare('UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?'); return $s->execute([$hash,$userId]); }
}
