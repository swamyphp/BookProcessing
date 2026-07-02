<?php
class FolderTree{
  public static function build($base){
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
    $root = ['type'=>'folder','name'=>basename($base),'children'=>[]];
    foreach(scandir($base) as $f){ if($f=='.'||$f=='..') continue; $root['children'][] = self::scan($base.'/'.$f); }
    return $root;
  }
  protected static function scan($path){
    if(is_dir($path)){
      $node=['type'=>'folder','name'=>basename($path),'children'=>[]];
      foreach(scandir($path) as $f){ if($f=='.'||$f=='..') continue; $node['children'][] = self::scan($path.'/'.$f); }
      return $node;
    }
    return ['type'=>'file','name'=>basename($path),'path'=>$path];
  }
}
