<?php
class ZipManager{
  protected $baseTemp;
  public function __construct($baseTemp){ $this->baseTemp=$baseTemp; }
  public function extract($uploadedPath){
    $id = uniqid('ex_');
    $dest = $this->baseTemp . '/' . $id;
    mkdir($dest,0777,true);
    $z = new ZipArchive();
    if($z->open($uploadedPath)===TRUE){ $z->extractTo($dest.'/extracted'); $z->close(); return $id; }
    return false;
  }
}
