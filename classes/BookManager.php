<?php
class BookManager{
  protected $booksDir;
  public function __construct($booksDir){ $this->booksDir=$booksDir; }
  public function createFromExtraction($extractedPath, $shortName){
    $dest = $this->booksDir . '/' . $shortName;
    if(is_dir($dest)) throw new Exception('exists');
    mkdir($dest,0777,true);
    $this->rcopy($extractedPath, $dest);
    return $dest;
  }
  protected function rcopy($src,$dst){ $dir=opendir($src); @mkdir($dst); while(false!==($f=readdir($dir))){ if($f=='.'||$f=='..') continue; $s=$src.'/'.$f; $d=$dst.'/'.$f; if(is_dir($s)) $this->rcopy($s,$d); else copy($s,$d); } closedir($dir); }
}
