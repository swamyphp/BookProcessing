$(function(){
  let extractionId = null;
  // dark mode
  if(localStorage.getItem('bp_dark')==='1') $('body').addClass('dark');
  const dmToggle = $('<button class="btn btn-sm btn-outline-secondary ms-2">Toggle Dark</button>');
  dmToggle.on('click', function(){ $('body').toggleClass('dark'); localStorage.setItem('bp_dark', $('body').hasClass('dark') ? '1':'0'); });
  $('.d-flex.align-items-center').first().append(dmToggle);

  // enhanced upload with progress
  $('#uploadZipForm').on('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    const xhr = new XMLHttpRequest();
    const progress = $('<div class="progress mt-2"><div class="progress-bar" role="progressbar" style="width:0%">0%</div></div>');
    $('#uploadZipForm').after(progress);
    xhr.upload.addEventListener('progress', function(ev){ if(ev.lengthComputable){ const pct = Math.round(ev.loaded/ev.total*100); progress.find('.progress-bar').css('width',pct+'%').text(pct+'%'); } });
    xhr.addEventListener('load', function(){ const resp = JSON.parse(xhr.responseText || '{}'); if(resp.success){ extractionId = resp.extraction_id; loadTree(); $('#validationArea').html('Package extracted.'); } else alert(resp.error||'Upload failed'); progress.remove(); });
    xhr.open('POST','upload.php'); xhr.send(fd);
  });

  function loadTree(){
    if(!extractionId) return;
    $.get('explorer.php',{id:extractionId}, function(resp){
      const j = typeof resp === 'string' ? JSON.parse(resp) : resp;
      $('#treeArea').empty();
      buildTree($('#treeArea'), j.tree, '');
      validatePackage();
    });
  }

  function buildTree(container, node, path){
    if(node.type==='folder'){
      const div = $('<div>').addClass('folder');
      const hdr = $('<div>').addClass('fw-bold d-flex align-items-center').html('<span class="me-2">📁</span>'+node.name);
      hdr.on('click', function(){ $(this).next().toggle(); });
      div.append(hdr);
      const inner = $('<div>').css({'padding-left':'12px'}).toggle();
      node.children.forEach(function(ch){ buildTree(inner, ch, path + '/' + node.name); });
      div.append(inner);
      container.append(div);
    } else {
      const file = $('<div>').addClass('file d-flex align-items-center').css('cursor','pointer');
      file.html('<span class="me-2">📄</span>'+node.name);
      file.on('click', function(){ showDetails(node); });
      // right-click menu
      file.on('contextmenu', function(e){ e.preventDefault(); showContextMenu(e.pageX,e.pageY,node); });
      container.append(file);
    }
  }

  function showContextMenu(x,y,node){
    $('.app-context-menu').remove();
    const menu = $(`<div class="app-context-menu card" style="position:absolute;left:${x}px;top:${y}px;z-index:9999;padding:6px;"></div>`);
    const rename = $('<div class="p-1">Rename</div>').on('click', function(){ $('.app-context-menu').remove(); showDetails(node); $('#renameBtn').click(); });
    const replace = $('<div class="p-1">Replace</div>').on('click', function(){ $('.app-context-menu').remove(); showDetails(node); $('#replaceBtn').click(); });
    const del = $('<div class="p-1 text-danger">Delete</div>').on('click', function(){ $('.app-context-menu').remove(); if(confirm('Delete file?')) $.post('ajax/delete.php',{path:node.path}, function(r){ const j=JSON.parse(r); if(j.success) loadTree(); else alert(j.error); }); });
    menu.append(rename,replace,del);
    $('body').append(menu);
    $(document).one('click', function(){ menu.remove(); });
  }

  function showDetails(node){
    const html = `<p><strong>File:</strong> ${node.name}</p>
      <p><strong>Path:</strong> ${node.path}</p>
      <div class="btn-group">
        <button id="renameBtn" class="btn btn-sm btn-outline-secondary">Rename</button>
        <button id="replaceBtn" class="btn btn-sm btn-outline-primary">Replace</button>
        <button id="deleteBtn" class="btn btn-sm btn-outline-danger">Delete</button>
      </div>
      <div id="fileActionArea" class="mt-2"></div>`;
    $('#detailArea').html(html);

    $('#renameBtn').on('click', function(){
      const frm = `<div class="input-group mt-2"><input id="newName" class="form-control" value="${node.name}"><button id="doRename" class="btn btn-primary">Rename</button></div>`;
      $('#fileActionArea').html(frm);
      $('#doRename').on('click', function(){
        $.post('file_actions.php',{action:'rename', id:node.extraction_id, path:node.path, name:node.name, newname:$('#newName').val()}, function(r){ const j=JSON.parse(r); if(j.success){ loadTree(); $('#fileActionArea').html('Renamed'); } else alert(j.error); });
      });
    });

    $('#replaceBtn').on('click', function(){
      const frm = `<form id="replaceForm" enctype="multipart/form-data" class="mt-2"><input type="file" name="file" required><button class="btn btn-primary btn-sm">Upload</button></form>`;
      $('#fileActionArea').html(frm);
      $('#replaceForm').on('submit', function(e){ e.preventDefault(); const fd=new FormData(this); fd.append('action','replace'); fd.append('id', node.extraction_id); fd.append('path', node.path); fd.append('name', node.name);
        const xhr = new XMLHttpRequest(); xhr.open('POST','file_actions.php'); xhr.onload = function(){ const j=JSON.parse(xhr.responseText||'{}'); if(j.success){ loadTree(); $('#fileActionArea').html('Replaced'); } else alert(j.error); }; xhr.send(fd);
      });
    });

    $('#deleteBtn').on('click', function(){ if(!confirm('Delete file?')) return; $.post('file_actions.php',{action:'delete', id:node.extraction_id, path:node.path, name:node.name}, function(r){ const j=JSON.parse(r); if(j.success){ loadTree(); $('#fileActionArea').html('Deleted'); } else alert(j.error); }); });
  }

  function validatePackage(){
    $.get('file_actions.php',{action:'validate', id:extractionId}, function(r){ const j=JSON.parse(r); let html=''; j.results.forEach(function(it){ html += `<div>${it.ok?'<span class="text-success">✔</span>':'<span class="text-danger">❌</span>'} ${it.name}</div>`; });
      $('#validationArea').html(html);
      $('#createBookBtn').prop('disabled', !j.ok);
  });
  }

  $('#createBookBtn').on('click', function(){
    const shortName = $('#bookShortName').val().trim();
    if(!/^[A-Za-z0-9_-]+$/.test(shortName)){ alert('Invalid short name'); return; }
    $.post('create_book.php',{id:extractionId, short:shortName}, function(r){ const j=JSON.parse(r); if(j.success){ alert('Book created: '+j.book_id+' Chapters:'+j.chapters); } else alert(j.error); });
  });
});
