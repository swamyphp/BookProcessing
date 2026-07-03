<?php
require 'config/db.php'; require 'config/auth.php';
require_login(); require_role('admin');
$pdo = get_db(); $message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $fields = ['smtp_host','smtp_port','smtp_user','smtp_pass','smtp_secure','from_email','from_name','clamav_scan','clamav_scan_after_extract','clamav_bin'];
  foreach($fields as $k){
    $v = $_POST[$k] ?? '';
    // normalize checkboxes
    if(in_array($k,['clamav_scan','clamav_scan_after_extract'])){ $v = empty($v) ? '0' : '1'; }
    $stmt = $pdo->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
    $stmt->execute([$k,$v]);
  }
  $message = 'Settings saved.';
  record_activity('smtp_settings_updated');
}
$settings = [];
$stmt = $pdo->prepare('SELECT `key`,`value` FROM settings WHERE `key` IN (?,?,?,?,?,?,?,?,?)');
$stmt->execute(['smtp_host','smtp_port','smtp_user','smtp_pass','smtp_secure','from_email','from_name','clamav_scan','clamav_scan_after_extract']);
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) $settings[$r['key']] = $r['value'];
?><!doctype html>
<html><head><meta charset="utf-8"><title>SMTP Settings</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="p-4">
<div class="container"><h4>SMTP Settings</h4>
<?php if($message):?><div class="alert alert-success"><?php echo htmlspecialchars($message);?></div><?php endif;?>
<form method="post">
  <div class="mb-2"><label>SMTP Host</label><input name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? '');?>"></div>
  <div class="mb-2"><label>SMTP Port</label><input name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587');?>"></div>
  <div class="mb-2"><label>SMTP User</label><input name="smtp_user" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? '');?>"></div>
  <div class="mb-2"><label>SMTP Pass</label><input name="smtp_pass" type="password" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? '');?>"></div>
  <div class="mb-2"><label>SMTP Secure (tls/ssl)</label><input name="smtp_secure" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_secure'] ?? 'tls');?>"></div>
  <div class="mb-2"><label>From Email</label><input name="from_email" class="form-control" value="<?php echo htmlspecialchars($settings['from_email'] ?? 'no-reply@example.com');?>"></div>
  <div class="mb-2"><label>From Name</label><input name="from_name" class="form-control" value="<?php echo htmlspecialchars($settings['from_name'] ?? 'BookProcessing');?>"></div>
  <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" value="1" id="clamav_scan" name="clamav_scan" <?php echo (!empty($settings['clamav_scan']) && $settings['clamav_scan']=='1') ? 'checked':''; ?> />
    <label class="form-check-label" for="clamav_scan">Enable ClamAV scanning on upload</label>
  </div>
  <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" value="1" id="clamav_scan_after_extract" name="clamav_scan_after_extract" <?php echo (!empty($settings['clamav_scan_after_extract']) && $settings['clamav_scan_after_extract']=='1') ? 'checked':''; ?> />
    <label class="form-check-label" for="clamav_scan_after_extract">Scan extracted files after extraction</label>
  </div>
  <div class="mb-2"><label>ClamAV Binary (optional)</label><input name="clamav_bin" class="form-control" value="<?php echo htmlspecialchars($settings['clamav_bin'] ?? '');?>" placeholder="/usr/bin/clamscan or clamdscan"></div>
  <div class="d-flex gap-2"><button class="btn btn-primary">Save</button><a class="btn btn-secondary" href="index.php">Back</a></div>
</form>
<hr>
<h5>Send Test Email</h5>
<div class="mb-2"><label>Send test email to</label><input id="test_email" class="form-control" placeholder="you@example.com"></div>
<div id="testResult" style="margin-bottom:12px"></div>
<button id="sendTestBtn" class="btn btn-outline-success">Send Test Email</button>

<script>
function runClamTest(){
  const out = document.getElementById('clamTestResult'); out.innerHTML='Running ClamAV test...';
  fetch('ajax/test_clamav.php', {method:'POST'}).then(r=>r.json()).then(j=>{ if(j.success){ out.innerHTML = '<div class="alert alert-success">'+j.message+'</div>';} else { out.innerHTML = '<div class="alert alert-danger">'+j.message+'</div>'; } }).catch(e=>{ out.innerHTML = '<div class="alert alert-danger">Request failed</div>'; });
}
</script>
<div class="mt-3">
  <button class="btn btn-secondary" onclick="runClamTest()">Run ClamAV Test</button>
  <div id="clamTestResult" style="margin-top:8px"></div>
</div>
document.getElementById('sendTestBtn').addEventListener('click', function(e){
  e.preventDefault();
  var email = document.getElementById('test_email').value;
  var result = document.getElementById('testResult'); result.innerHTML='Sending...';
  fetch('ajax/send_test_email.php', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({email: email})
  }).then(r=>r.json()).then(j=>{
    if(j.success){ result.innerHTML = '<div class="alert alert-success">'+j.message+'</div>'; }
    else { result.innerHTML = '<div class="alert alert-danger">'+j.message+'</div>'; }
  }).catch(err=>{ result.innerHTML = '<div class="alert alert-danger">Error sending request</div>'; });
});
</script>
</div>
</body></html>
