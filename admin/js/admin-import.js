/**
 * admin-import.js — استيراد أعضاء من Excel
 *
 * المسار: قراءة Excel → معالجة → تخزين في pendingImport[] → عرض جدول مراجعة → حفظ عبر API
 */

// المصفوفة المؤقتة للسجلات المعالجة
var pendingImport = [];

function openImportExcel(){
  pendingImport = [];
  document.getElementById('import-result').style.display='none';
  var saveBtn = document.getElementById('btn-import-save');
  if(saveBtn) saveBtn.style.display='none';
  openModal('modal-import-excel');
}

function isValidName(n){
  if(!n||n.length<3) return false;
  var bad=['المجموع','الإجمالي','Total','Sum','إجمالي','مجموع','العدد','المبلغ','الرصيد','ريال'];
  for(var i=0;i<bad.length;i++){ if(n.indexOf(bad[i])!==-1) return false; }
  if(/^\d+$/.test(n)) return false;
  if(/^05\d{8}$/.test(n)) return false;
  return true;
}

function detectPayment(r){
  var a=parseFloat(r['المبلغ']||r['Amount']||r['المدفوع']||0);
  var st=r['الحالة']||r['Status']||'';
  if(a>0) return {paid:true, amount:a};
  if(st){
    var s=st.toLowerCase();
    if(s.indexOf('مدفوع')!==-1||s.indexOf('paid')!==-1) return {paid:true, amount:a||500};
    if(s.indexOf('متأخر')!==-1||s.indexOf('لم يدفع')!==-1) return {paid:false, amount:0};
  }
  return {paid:false, amount:0};
}

function findMember(n){
  for(var i=0;i<State.getMembers().length;i++){
    var m=State.getMembers()[i];
    if(m.name.toLowerCase()===n.toLowerCase()) return m;
    var n1=n.toLowerCase().replace(/^(أبو|أبي|أم|ابن|بن)\s+/g,'');
    var n2=m.name.toLowerCase().replace(/^(أبو|أبي|أم|ابن|بن)\s+/g,'');
    if(n1===n2) return m;
  }
  return null;
}

// ── معالجة ملف Excel وتخزين النتائج في pendingImport[] ──
document.getElementById('excel-import-input').onchange=function(){
  var file=this.files[0];
  if(!file) return;
  var reader=new FileReader();
  reader.onload=function(e){
    try{
      var data=new Uint8Array(e.target.result);
      var wb=XLSX.read(data,{type:'array'});
      var ws=wb.Sheets[wb.SheetNames[0]];
      var json=XLSX.utils.sheet_to_json(ws);
      if(!json.length){ toast('الملف فارغ','error'); return; }

      pendingImport = [];

      json.forEach(function(row){
        var name=(row['الاسم']||row['Name']||row['name']||'').trim();
        var phone=(row['الجوال']||row['Phone']||row['phone']||'').toString().trim();

        if(!isValidName(name)){
          pendingImport.push({
            name: name||'(فارغ)',
            phone: phone,
            status: 'مشترك',
            type: 'skip',
            existingId: null,
            skipReason: !name ? 'اسم فارغ' : 'اسم غير صالح'
          });
          return;
        }

        var member=findMember(name);

        if(member){
          pendingImport.push({
            name: name,
            phone: phone || member.phone || '',
            status: member.status || 'مشترك',
            type: 'update',
            existingId: member.id,
            skipReason: null
          });
        } else {
          pendingImport.push({
            name: name,
            phone: phone,
            status: 'مشترك',
            type: 'new',
            existingId: null,
            skipReason: null
          });
        }
      });

      renderImportReview();
      toast('تم معالجة '+json.length+' سجل — راجع قبل الحفظ');
    }catch(err){ toast('خطأ في قراءة الملف','error'); console.error(err); }
  };
  reader.readAsArrayBuffer(file);
};

// ── عرض جدول المراجعة ──
function renderImportReview(){
  var newCount=0, updCount=0, skipCount=0;
  pendingImport.forEach(function(r){ if(r.type==='new') newCount++; else if(r.type==='update') updCount++; else skipCount++; });

  var html='<div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">';
  html+='<span class="badge badge-success" style="font-size:12px">جديد: '+newCount+'</span>';
  html+='<span class="badge badge-warning" style="font-size:12px">تحديث: '+updCount+'</span>';
  html+='<span class="badge badge-gray" style="font-size:12px">متجاهل: '+skipCount+'</span>';
  html+='<span style="font-size:12px;color:var(--text-muted);align-self:center">الإجمالي: '+pendingImport.length+'</span>';
  html+='</div>';

  html+='<div style="max-height:350px;overflow-y:auto;border:1px solid var(--border);border-radius:8px">';
  html+='<table style="width:100%;font-size:12px;border-collapse:collapse">';
  html+='<thead><tr style="background:var(--bg-alt);position:sticky;top:0">';
  html+='<th style="padding:8px;text-align:right">#</th>';
  html+='<th style="padding:8px;text-align:right">الاسم</th>';
  html+='<th style="padding:8px;text-align:right">الجوال</th>';
  html+='<th style="padding:8px;text-align:center">النوع</th>';
  html+='<th style="padding:8px;text-align:center">إجراء</th>';
  html+='</tr></thead><tbody>';

  pendingImport.forEach(function(r,i){
    var bgColor = r.type==='new' ? '#f0fdf4' : r.type==='update' ? '#fefce8' : '#f9fafb';
    var badge = r.type==='new' ? '<span class="badge badge-success">جديد</span>'
              : r.type==='update' ? '<span class="badge badge-warning">تحديث</span>'
              : '<span class="badge badge-gray">متجاهل</span>';
    var reason = r.skipReason ? '<div style="font-size:10px;color:#9ca3af">'+r.skipReason+'</div>' : '';
    var actions = r.type!=='skip'
      ? '<button class="btn btn-outline btn-xs" onclick="removeImportRow('+i+')" title="حذف">✕</button>'
      : '';

    var nameCell = r.type!=='skip'
      ? '<input class="form-control" value="'+escAttr(r.name)+'" onchange="pendingImport['+i+'].name=this.value.trim()" style="font-size:12px;padding:4px 6px;min-width:120px">'
      : escHtml(r.name);
    var phoneCell = r.type!=='skip'
      ? '<input class="form-control" value="'+escAttr(r.phone)+'" onchange="pendingImport['+i+'].phone=this.value.trim()" style="font-size:12px;padding:4px 6px;width:110px">'
      : escHtml(r.phone);

    html+='<tr style="background:'+bgColor+';border-bottom:1px solid var(--border)">';
    html+='<td style="padding:6px 8px">'+(i+1)+'</td>';
    html+='<td style="padding:6px 8px">'+nameCell+'</td>';
    html+='<td style="padding:6px 8px">'+phoneCell+'</td>';
    html+='<td style="padding:6px 8px;text-align:center">'+badge+reason+'</td>';
    html+='<td style="padding:6px 8px;text-align:center">'+actions+'</td>';
    html+='</tr>';
  });

  html+='</tbody></table></div>';

  document.getElementById('import-result').style.display='block';
  document.getElementById('import-result').innerHTML=html;

  // إظهار زر الحفظ إذا فيه سجلات قابلة للحفظ
  var saveBtn = document.getElementById('btn-import-save');
  if(saveBtn) saveBtn.style.display = (newCount+updCount>0) ? '' : 'none';
}

// ── حذف سجل من جدول المراجعة ──
function removeImportRow(idx){
  pendingImport.splice(idx,1);
  renderImportReview();
}

// ── إلغاء الاستيراد ──
function cancelImport(){
  pendingImport = [];
  document.getElementById('import-result').style.display='none';
  document.getElementById('import-result').innerHTML='';
  var saveBtn = document.getElementById('btn-import-save');
  if(saveBtn) saveBtn.style.display='none';
  // إعادة تعيين حقل الملف
  document.getElementById('excel-import-input').value='';
}

// ── حفظ الاستيراد عبر API ──
async function saveImport(){
  var toSave = pendingImport.filter(function(r){ return r.type==='new'||r.type==='update'; });
  if(!toSave.length){ toast('لا توجد سجلات للحفظ','error'); return; }

  var btn = document.getElementById('btn-import-save');
  setBtnLoading(btn,true);

  // عرض شريط التقدم
  var progressEl = document.getElementById('import-progress');
  if(progressEl){
    progressEl.style.display='block';
    progressEl.innerHTML='<div style="background:var(--border);border-radius:6px;height:8px;overflow:hidden"><div id="import-progress-bar" style="background:var(--green);height:100%;width:0%;transition:width 0.3s"></div></div><div id="import-progress-text" style="font-size:11px;color:var(--text-muted);margin-top:4px;text-align:center">0 / '+toSave.length+'</div>';
  }

  var success=0, failed=0, errors=[];

  for(var i=0;i<toSave.length;i++){
    var r=toSave[i];
    var payload={
      name: r.name,
      phone: r.phone,
      status: r.status,
      join_date: today(),
      family: 'العوامي',
      notes: 'مستورد من Excel'
    };

    try{
      if(r.type==='new'){
        await apiFetch('/api/members.php',{ method:'POST', body:JSON.stringify(payload) });
      } else {
        await apiFetch('/api/members.php?id='+encodeURIComponent(r.existingId),{ method:'PUT', body:JSON.stringify(payload) });
      }
      success++;
    }catch(e){
      failed++;
      errors.push(r.name+': '+e.message);
    }

    // تحديث شريط التقدم
    var pct = Math.round(((i+1)/toSave.length)*100);
    var bar = document.getElementById('import-progress-bar');
    var txt = document.getElementById('import-progress-text');
    if(bar) bar.style.width=pct+'%';
    if(txt) txt.textContent=(i+1)+' / '+toSave.length;
  }

  setBtnLoading(btn,false);

  // ملخص نهائي
  var summary='<div style="margin-top:12px;padding:12px;border-radius:8px;';
  if(failed===0){
    summary+='background:#dcfce7;border:1px solid #86efac"><div style="font-weight:700;color:#166534">✅ تم الحفظ بنجاح</div>';
    summary+='<div style="font-size:12px;color:#166534;margin-top:4px">تم حفظ '+success+' عضو في قاعدة البيانات</div>';
  } else {
    summary+='background:#fef9c3;border:1px solid #fde047"><div style="font-weight:700;color:#854d0e">⚠️ اكتمل مع أخطاء</div>';
    summary+='<div style="font-size:12px;color:#854d0e;margin-top:4px">نجح: '+success+' | فشل: '+failed+'</div>';
    if(errors.length){
      summary+='<div style="font-size:11px;color:#854d0e;margin-top:6px;max-height:60px;overflow:auto">'+errors.join('<br>')+'</div>';
    }
  }
  summary+='</div>';

  var resultEl = document.getElementById('import-result');
  resultEl.innerHTML+=summary;

  // تحديث البيانات وإعادة عرض الجدول
  await MemberService.syncMembersFromAPI();
  renderMembers();
  renderDashboard();
  updateSidebar();

  // تسجيل في سجل التدقيق
  log('استيراد '+success+' عضو من Excel'+(failed?' (فشل '+failed+')':''),'📥');

  // تنظيف
  pendingImport = [];
  if(btn) btn.style.display='none';
}

// ── مساعدات HTML ──
function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s){ return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// =====================================================
// Drag & Drop Enhancement for AI Upload
// =====================================================
(function() {
  function setupDragDrop() {
    var uploadArea = document.getElementById('upload-area');
    if (!uploadArea) return;

    uploadArea.addEventListener('dragover', function(e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = 'var(--green)';
      this.style.backgroundColor = '#f0fdf4';
    });

    uploadArea.addEventListener('dragleave', function(e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = 'var(--border)';
      this.style.backgroundColor = 'transparent';
    });

    uploadArea.addEventListener('drop', function(e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = 'var(--border)';
      this.style.backgroundColor = 'transparent';

      var files = e.dataTransfer.files;
      if (files.length > 0) {
        var fileInput = document.getElementById('file-input-ai');
        fileInput.files = files;
        handleAIFileUpload({target: {files: files}});
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupDragDrop);
  } else {
    setupDragDrop();
  }
})();
