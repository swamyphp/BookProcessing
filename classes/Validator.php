<?php
class Validator{
  protected $base;
  public function __construct($base){ $this->base=$base; }

  // rules: array of ['name'=>filenameOrFolder,'type'=>'file'|'folder','required'=>true]
  public function validateRules(array $rules){
    $results = [];
    foreach($rules as $r){
      $name = $r['name']; $type = $r['type'] ?? 'file';
      if($type=='file'){
        $matches = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->base));
        foreach($it as $f){ if($f->isFile() && strcasecmp($f->getFilename(), $name)==0) $matches[] = $f->getPathname(); }
        if(count($matches)===0) $status='missing';
        elseif(count($matches)>1) $status='duplicate';
        else $status='found';
        // check extension
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if($status=='found' && $ext){ $foundExt = pathinfo($matches[0], PATHINFO_EXTENSION); if(strcasecmp($foundExt,$ext)!==0) $status='wrong_extension'; }
        $results[]=['name'=>$name,'type'=>'file','status'=>$status,'matches'=>$matches];
      } else {
        // folder
        $found=false; $empty=false; $matchPath=null;
        foreach(new RecursiveDirectoryIterator($this->base) as $d){ if($d->isDir() && strcasecmp($d->getFilename(), $name)==0){ $found=true; $matchPath=$d->getPathname(); break; } }
        if(!$found) $status='missing'; else {
          // check empty
          $it = new FilesystemIterator($matchPath);
          $empty = !$it->valid();
          $status = $empty ? 'empty' : 'found';
        }
        $results[]=['name'=>$name,'type'=>'folder','status'=>$status,'path'=>$matchPath];
      }
    }
    // overall ok if no missing, duplicate, wrong_extension, empty
    $ok = true; foreach($results as $r){ if(in_array($r['status'], ['missing','duplicate','wrong_extension','empty'])){ $ok=false; break; } }
    return ['results'=>$results,'ok'=>$ok];
  }
}

