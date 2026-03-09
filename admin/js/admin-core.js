



// =================== REAL DATA FROM PDF ===================
// COUNCIL_POSITIONS removed — now loaded dynamically from /api/positions.php

const COMMITTEES_DATA = [
  {id:'c1',name:'لجنة العمرة الرجبية',icon:'🕋',color:'linear-gradient(135deg,#1a6b3c,#2d9955)',desc:'تنظيم رحلة العمرة السنوية في شهر رجب',members:['عبدالله عماد','عبدالخالق علي','حسين عبدالله','تيسير محمد','أحمد علي السلمان','مهند مكي','مرتضى','محمد حسين']},
  {id:'c2',name:'لجنة غداء العيدين',icon:'🍖',color:'linear-gradient(135deg,#c8a84b,#e8c96a)',desc:'تنظيم وإدارة غداء عيد الفطر وعيد الأضحى',members:['محمود حسن (أبومراد)','معين علي (أبو علي)','محمد راضي (أبو ميرزا)','علي حسين علي السلمان','أحمد غازي']},
  {id:'c3',name:'لجنة المسابقة الرمضانية',icon:'🌙',color:'linear-gradient(135deg,#1a3a6b,#2d5ab9)',desc:'إعداد وتحكيم المسابقات الرمضانية',members:['منصور علي','عماد عبدالحميد','مجتبى سلمان','رضا حسين','أحمد غازي']},
  {id:'c4',name:'لجنة الرحلات',icon:'🎡',color:'linear-gradient(135deg,#2980b9,#5dade2)',desc:'تخطيط وتنفيذ الرحلات الترفيهية للعائلة',members:['محمود حسن (أبومراد)']},
  {id:'c5',name:'لجنة ليلة القدر',icon:'✨',color:'linear-gradient(135deg,#4a235a,#8e44ad)',desc:'إحياء ليلة القدر وتنظيم فعالياتها',members:['عبدالله عماد','علي العوامي (أبو حيدر)','محمد حسن','أحمد علي السلمان','حسن علي (حسنكو)','سجاد علي','محمد حسين']},
  {id:'c6',name:'لجنة تنظيف المساجد',icon:'🕌',color:'linear-gradient(135deg,#117a65,#1abc9c)',desc:'تنسيق حملات تنظيف وصيانة المساجد (العمل التطوعي)',members:['راضي ابراهيم','علي العوامي (أبو محمد)','عماد علي (أبو عبدالله)','عماد عبدالحميد','رضا حسين','باسل حسين']},
  {id:'c7',name:'لجنة مسابقة العيد',icon:'🏆',color:'linear-gradient(135deg,#b7950b,#d4ac0d)',desc:'تنظيم مسابقات وفعاليات العيد',members:['راضي ابراهيم','محمد عبدالله','حسين عبدالله','علي عبدالله','حسن علي (حسنكو)','رضا حسين','مجتبى سلمان','ليلى عبدالله']},
  {id:'c8',name:'لجنة الاستثمار',icon:'📈',color:'linear-gradient(135deg,#1B3456,#2d5a85)',desc:'إدارة واستثمار أموال الصندوق',members:['حسين عبدالحميد','عبدالله عماد','حسين علي سلمان','أحمد عبد الحميد','أحمد عبدالله','سعيد علي راضي']},
  {id:'c9',name:'اللجنة الاستشارية',icon:'🎓',color:'linear-gradient(135deg,#4a235a,#7b2d8b)',desc:'تقديم المشورة والتوجيه لإدارة المجلس',advisory:true,members:['علي العوامي (أبو حيدر)','فخري العوامي (أبو عقيل)','حسين علي سلمان العوامي (أبو علي)']},
  {id:'c10',name:'اللجنة الإعلامية',icon:'📢',color:'linear-gradient(135deg,#c0392b,#e74c3c)',desc:'إدارة المنصات الإعلامية وتوثيق الفعاليات',members:['منصور علي','حسن علي (حسنكو)','مجتبى سلمان','رضا حسين']},
  {id:'c11',name:'لجنة العقيقة الجماعية',icon:'🐑',color:'linear-gradient(135deg,#6b3a1a,#9b5a2d)',desc:'تنظيم مناسبات العقيقة الجماعية للعائلة',members:['حسين عبدالحميد','حسين علي سلمان','محمد مصطفى','علي عماد']},
];

// =================== DB ===================
// ── State initialisation ──────────────────────────────────────────
State.init(null, COMMITTEES_DATA, []);

// saveDB — kept for backup only; actual persistence is via API
function saveDB(){
  try { localStorage.setItem('awami_db_v4', JSON.stringify(State.getDB())); } catch(e) {}
}

// =================== BACKUP & RESTORE ===================
function autoBackup(){
  const backupKey = 'awami_backup_' + new Date().toISOString().split('T')[0];
  const backups = JSON.parse(localStorage.getItem('awami_backups') || '{}');
  backups[backupKey] = {data: State.getDB(), timestamp: new Date().toISOString()};
  
  // Keep only last 7 days
  const keys = Object.keys(backups).sort().reverse();
  if(keys.length > 7){
    keys.slice(7).forEach(k => delete backups[k]);
  }
  
  localStorage.setItem('awami_backups', JSON.stringify(backups));
}

function exportData(){
  const dataStr = JSON.stringify(State.getDB(), null, 2);
  const dataBlob = new Blob([dataStr], {type: 'application/json'});
  const url = URL.createObjectURL(dataBlob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `awami-data-${new Date().toISOString().split('T')[0]}.json`;
  a.click();
  URL.revokeObjectURL(url);
  toast('تم تصدير البيانات بنجاح 📥');
  log('تصدير البيانات','💾');
}

function importData(file){
  const reader = new FileReader();
  reader.onload = (e) => {
    try {
      const imported = JSON.parse(e.target.result);
      
      // Validate data structure
      if(!imported.members || !Array.isArray(imported.members)){
        toast('ملف البيانات غير صحيح','error');
        return;
      }
      
      confirm2('هل تريد استيراد البيانات؟ سيتم استبدال البيانات الحالية!', () => {
        // Backup current data before import
        localStorage.setItem('awami_before_import', JSON.stringify(State.getDB()));
        
        State.replaceDB(imported, COMMITTEES_DATA);
        saveDB();
        toast('تم استيراد البيانات بنجاح ✅');
        log('استيراد البيانات','📤');
        
        // Refresh all views
        const currentPage = document.querySelector('.page.active').id.replace('page-','');
        showPage(currentPage, document.querySelector('.nav-item.active'));
      });
    } catch(err) {
      toast('خطأ في قراءة الملف','error');
      console.error(err);
    }
  };
  reader.readAsText(file);
}

function showBackups(){
  const backups = JSON.parse(localStorage.getItem('awami_backups') || '{}');
  const keys = Object.keys(backups).sort().reverse();
  
  if(!keys.length){
    toast('لا توجد نسخ احتياطية','error');
    return;
  }
  
  const list = keys.map(k => {
    const b = backups[k];
    const date = new Date(b.timestamp).toLocaleString('ar-SA');
    return `<div style="display:flex;align-items:center;justify-content:space-between;padding:10px;border-bottom:1px solid var(--border)">
      <div><div style="font-weight:600;font-size:13px">${k.replace('awami_backup_','')}</div><div style="font-size:11px;color:var(--text-muted)">${date}</div></div>
      <button class="btn btn-primary btn-xs" onclick="restoreBackup('${k}')">استرجاع</button>
    </div>`;
  }).join('');
  
  document.getElementById('backup-list').innerHTML = list;
  openModal('modal-backups');
}

function restoreBackup(key){
  const backups = JSON.parse(localStorage.getItem('awami_backups') || '{}');
  if(!backups[key]){
    toast('النسخة الاحتياطية غير موجودة','error');
    return;
  }
  
  confirm2('هل تريد استرجاع هذه النسخة؟', () => {
    State.replaceDB(backups[key].data, COMMITTEES_DATA);
    saveDB();
    closeModal('modal-backups');
    toast('تم استرجاع النسخة الاحتياطية ✅');
    log('استرجاع نسخة احتياطية','⏮️');
    location.reload();
  });
}

function clearAllData(){
  confirm2('⚠️ هل أنت متأكد؟ سيتم حذف جميع البيانات!\n\nسيتم حفظ نسخة احتياطية قبل الحذف.', () => {
    // Final backup before clear
    localStorage.setItem('awami_before_clear', JSON.stringify(State.getDB()));
    
    State.resetDB(COMMITTEES_DATA);
    saveDB();
    toast('تم مسح جميع البيانات');
    log('مسح جميع البيانات','🗑️');
    location.reload();
  });
}

// =================== DARK MODE ===================
function toggleDarkMode(){
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  document.documentElement.setAttribute('data-theme', isDark ? '' : 'dark');
  localStorage.setItem('awami_theme', isDark ? 'light' : 'dark');
  const btn = document.getElementById('dark-mode-btn');
  if(btn) btn.textContent = isDark ? '🌙' : '☀️';
}
// تحديث أيقونة الزر عند التحميل
(function(){
  const saved = localStorage.getItem('awami_theme');
  if(saved === 'dark'){
    const btn = document.getElementById('dark-mode-btn');
    if(btn) btn.textContent = '☀️';
  }
})();

// =================== UTILS ===================
function uid(){ return Date.now().toString(36)+Math.random().toString(36).slice(2); }
function today(){ return new Date().toISOString().split('T')[0]; }
function fmt(n){ return Number(n||0).toLocaleString('ar-SA'); }

// ── XSS Protection ──
function escapeHtml(str) {
  if (str == null) return '';
  var div = document.createElement('div');
  div.textContent = String(str);
  return div.innerHTML;
}

// ── Focus Management for Modals ──
var _lastFocused = null;
function openModal(id){
  _lastFocused = document.activeElement;
  var modal = document.getElementById(id);
  modal.classList.add('open');
  var firstInput = modal.querySelector('input:not([type=hidden]),select,textarea,button:not(.modal-close)');
  if (firstInput) setTimeout(function(){ firstInput.focus(); }, 100);
}
function closeModal(id){
  var modal = document.getElementById(id);
  // تحذير عند وجود تغييرات غير محفوظة
  var inputs = modal.querySelectorAll('input:not([type=hidden]), textarea, select');
  var hasChanges = Array.from(inputs).some(function(i){ return i.value !== i.defaultValue; });
  if (hasChanges && !modal._skipUnsavedCheck) {
    if (!confirm('يوجد تغييرات غير محفوظة. هل تريد الإغلاق؟')) return;
  }
  modal._skipUnsavedCheck = false;
  modal.classList.remove('open');
  // إزالة رسائل الخطأ
  modal.querySelectorAll('.field-error').forEach(function(e){ e.remove(); });
  modal.querySelectorAll('.invalid').forEach(function(e){ e.classList.remove('invalid'); });
  if (_lastFocused) { _lastFocused.focus(); _lastFocused = null; }
}
// إغلاق بدون تحذير (بعد الحفظ الناجح)
function closeModalSilent(id){
  var modal = document.getElementById(id);
  modal._skipUnsavedCheck = true;
  closeModal(id);
}

function log(action,icon='📝'){ State.getActivity().unshift({id:uid(),action,icon,time:new Date().toLocaleString('ar-SA')}); if(State.getActivity().length>40) State.getActivity().pop(); saveDB(); }
function debounce(fn, delay){
  delay = delay || 300;
  var timer;
  return function(){
    var args = arguments, ctx = this;
    clearTimeout(timer);
    timer = setTimeout(function(){ fn.apply(ctx, args); }, delay);
  };
}

// ── Loading State for Buttons ──
async function withLoading(btn, fn) {
  if (!btn) return fn();
  var original = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> جاري...';
  try { return await fn(); }
  finally { btn.disabled = false; btn.innerHTML = original; }
}

// ── Field-level Error Messages ──
function showFieldError(inputId, msg) {
  var input = document.getElementById(inputId);
  if (!input) return;
  input.classList.add('invalid');
  input.style.borderColor = 'var(--danger)';
  var err = input.nextElementSibling;
  if (!err || !err.classList.contains('field-error')) {
    err = document.createElement('div');
    err.className = 'field-error';
    err.style.cssText = 'color:var(--danger);font-size:11px;margin-top:4px';
    input.parentNode.insertBefore(err, input.nextSibling);
  }
  err.textContent = msg;
}
function clearFieldError(inputId) {
  var input = document.getElementById(inputId);
  if (!input) return;
  input.classList.remove('invalid');
  input.style.borderColor = '';
  var err = input.nextElementSibling;
  if (err && err.classList.contains('field-error')) err.remove();
}

// =================== TOAST (IMPROVED) ===================
function toast(msg, type){
  type = type || 'success';
  var container = document.getElementById('toast-container');
  if(!container){
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }
  var icons = {success:'✅',error:'❌',warning:'⚠️',info:'ℹ️'};
  var colors = {success:'var(--green)',error:'var(--danger)',warning:'var(--warning)',info:'var(--info)'};
  var el = document.createElement('div');
  el.className = 'toast-item';
  el.style.background = colors[type] || colors.success;
  el.innerHTML = (icons[type]||icons.success) + ' ' + msg;
  el.onclick = function(){ el.className='toast-item hide'; setTimeout(function(){el.remove()},300); };
  container.appendChild(el);
  requestAnimationFrame(function(){ el.className='toast-item show'; });
  setTimeout(function(){
    el.className='toast-item hide';
    setTimeout(function(){el.remove()},300);
  }, 4000);
}

// =================== PAGINATION ===================
var _pageState = {};
function paginate(items, pageKey, page, perPage){
  perPage = perPage || 25;
  if(page !== undefined) _pageState[pageKey] = page;
  var currentPage = _pageState[pageKey] || 1;
  var total = items.length;
  var pages = Math.ceil(total / perPage) || 1;
  if(currentPage > pages) currentPage = pages;
  _pageState[pageKey] = currentPage;
  var start = (currentPage - 1) * perPage;
  return { data: items.slice(start, start + perPage), total:total, pages:pages, page:currentPage };
}
function renderPaginationHTML(info, callbackName){
  if(info.pages <= 1) return '<div class="table-footer"><span class="count-label">'+info.total+' عنصر</span></div>';
  var html = '<div class="table-footer"><span class="count-label">'+info.total+' عنصر — صفحة '+info.page+' من '+info.pages+'</span><div class="pagination">';
  if(info.page > 1) html += '<button onclick="'+callbackName+'('+(info.page-1)+')">&#x276E;</button>';
  var start = Math.max(1, info.page-2), end = Math.min(info.pages, info.page+2);
  if(start > 1) html += '<button onclick="'+callbackName+'(1)">1</button><span class="page-info">…</span>';
  for(var i=start;i<=end;i++) html += '<button class="'+(i===info.page?'active':'')+'" onclick="'+callbackName+'('+i+')">'+i+'</button>';
  if(end < info.pages) html += '<span class="page-info">…</span><button onclick="'+callbackName+'('+info.pages+')">'+info.pages+'</button>';
  if(info.page < info.pages) html += '<button onclick="'+callbackName+'('+(info.page+1)+')">&#x276F;</button>';
  html += '</div></div>';
  return html;
}

// =================== FORM VALIDATION ===================
function validateRequired(fieldId, label){
  var el = document.getElementById(fieldId);
  if(!el) return true;
  var val = el.value.trim();
  var group = el.closest('.form-group');
  if(!val){
    el.classList.add('invalid');
    if(group) group.classList.add('has-error');
    toast(label+' مطلوب','error');
    el.focus();
    return false;
  }
  el.classList.remove('invalid');
  if(group) group.classList.remove('has-error');
  return true;
}
function clearValidation(){
  document.querySelectorAll('.invalid').forEach(function(el){el.classList.remove('invalid')});
  document.querySelectorAll('.has-error').forEach(function(el){el.classList.remove('has-error')});
}

// =================== LOADING HELPERS ===================
function showSkeletonRows(tbodyId, cols, rows){
  cols = cols || 6; rows = rows || 5;
  var el = document.getElementById(tbodyId);
  if(!el) return;
  el.innerHTML = Array(rows).fill('<tr><td colspan="'+cols+'"><div class="skeleton skeleton-row"></div></td></tr>').join('');
}
function setBtnLoading(btn, loading){
  if(!btn) return;
  if(loading){
    btn._origText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-sm"></span>';
    btn.classList.add('loading');
  } else {
    btn.innerHTML = btn._origText || btn.innerHTML;
    btn.classList.remove('loading');
  }
}
function confirm2(msg,cb){ document.getElementById('confirm-msg').textContent=msg; document.getElementById('confirm-btn').onclick=()=>{ cb(); closeModal('modal-confirm'); }; openModal('modal-confirm'); }
const aColors=['#2d6b40','#1B3456','#c8a84b','#b7950b','#8e44ad','#c0392b','#117a65','#2980b9','#6b3a1a'];
function avColor(n){ let h=0; for(let c of n||'') h+=c.charCodeAt(0); return aColors[h%aColors.length]; }
function avInit(n){ let p=(n||'?').trim().split(' '); return p.length>1?p[0][0]+p[p.length-1][0]:p[0].slice(0,2); }
function curPeriod(){ return State.getPeriods()[State.getPeriods().length-1]||null; }
function committeeSelectOptions(){ return State.getCommittees().map(c=>`<option value="${c.id}">${c.name}</option>`).join(''); }
function memberCommittees(mid){ return State.getCommittees().filter(c=>(State.getCommitteeMembers()[c.id]||[]).includes(mid)).map(c=>c.name).join(', ')||'—'; }
function sBadge(s){ return s==='قادم'?'badge-info':s==='جاري'?'badge-success':s==='مكتمل'?'badge-gray':'badge-danger'; }

