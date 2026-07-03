<?php
class Scanner {
  // Returns array: ['ok'=>bool,'infected'=>bool,'message'=>string,'raw'=>string]
  public static function scanFile($path){
    $cfg = [];
    try{ $cfg = require __DIR__ . '/../config/config.php'; }catch(Exception $e){ }
    if(empty($cfg['clamav_scan'])) return ['ok'=>true,'infected'=>false,'message'=>'Scanning disabled','raw'=>''];
    $bin = $cfg['clamav_bin'] ?? null;
    // auto-detect clamscan or clamdscan
    if(!$bin){
      $which = function($cmd){
        $out = null; $rc = null; @exec("where $cmd 2>&1", $out, $rc); if($rc === 0 && !empty($out)) return $out[0]; return null;
      };
      $bin = $which('clamscan') ?: $which('clamdscan');
    }
    if(!$bin) return ['ok'=>true,'infected'=>false,'message'=>'ClamAV not found','raw'=>''];
    $cmd = escapeshellcmd($bin) . ' --no-summary --infected ' . escapeshellarg($path) . ' 2>&1';
    $output = [];$rc = null; @exec($cmd, $output, $rc);
    $raw = implode("\n", $output);
    if($rc === 0) return ['ok'=>true,'infected'=>false,'message'=>'Clean','raw'=>$raw];
    if($rc === 1) return ['ok'=>false,'infected'=>true,'message'=>'Infected','raw'=>$raw];
    return ['ok'=>false,'infected'=>false,'message'=>'Scan error (rc='.$rc.')','raw'=>$raw];
  }

  public static function scanDir($dir){
    $cfg = [];
    try{ $cfg = require __DIR__ . '/../config/config.php'; }catch(Exception $e){ }
    if(empty($cfg['clamav_scan'])) return ['ok'=>true,'infected'=>false,'message'=>'Scanning disabled','raw'=>''];
    $bin = $cfg['clamav_bin'] ?? null;
    if(!$bin){
      $which = function($cmd){ $out=null;$rc=null; @exec("where $cmd 2>&1", $out, $rc); if($rc===0 && !empty($out)) return $out[0]; return null; };
      $bin = $which('clamscan') ?: $which('clamdscan');
    }
    if(!$bin) return ['ok'=>true,'infected'=>false,'message'=>'ClamAV not found','raw'=>''];
    $cmd = escapeshellcmd($bin) . ' -r --no-summary --infected ' . escapeshellarg($dir) . ' 2>&1';
    $output = [];$rc = null; @exec($cmd, $output, $rc);
    $raw = implode("\n", $output);
    if($rc === 0) return ['ok'=>true,'infected'=>false,'message'=>'Clean','raw'=>$raw];
    if($rc === 1) return ['ok'=>false,'infected'=>true,'message'=>'Infected','raw'=>$raw];
    return ['ok'=>false,'infected'=>false,'message'=>'Scan error (rc='.$rc.')','raw'=>$raw];
  }
}
