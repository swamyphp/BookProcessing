<?php
// simple validation summary page
require 'config/db.php';
$id = $_GET['id'] ?? null;
?><!doctype html>
<html><head><meta charset="utf-8"><title>Validation</title></head><body>
<div class="container">
  <h3>Validation</h3>
  <pre id="out"></pre>
  <script>fetch('file_actions.php?action=validate&id='+encodeURIComponent('<?php echo $id;?>')).then(r=>r.json()).then(j=>document.getElementById('out').innerText=JSON.stringify(j,null,2));</script>
</div>
</body></html>
