<?php
class PythonRunner{
  protected $pythonPath = 'python';
  public function run($script, array $args=[]){
    $cmd = escapeshellcmd($this->pythonPath) . ' ' . escapeshellarg($script);
    foreach($args as $a) $cmd .= ' ' . escapeshellarg($a);
    $out=[]; $ret=0; exec($cmd, $out, $ret);
    return ['return'=>$ret,'output'=>implode("\n",$out)];
  }
}
