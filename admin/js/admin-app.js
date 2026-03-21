// ═══════════════════════════════════════════════════════════════
// admin-app.js — Main admin application logic
// Core utilities are in admin-core.js (loaded before this file)
// ═══════════════════════════════════════════════════════════════

// =================== SIDEBAR ===================
function showPage(name,el){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  document.getElementById('page-'+name).classList.add('active');
  if(el) el.classList.add('active');
  
  // Close mobile menu
  if(window.innerWidth<=768){
    closeMobileSidebar();
  }
  
  const T={dashboard:'لوحة التحكم|مجلس عائلة العوامي',members:'الأعضاء|إدارة الأعضاء',fees:'الرسوم|متابعة المدفوعات',reminders:'التذكيرات|تذكيرات الأعضاء غير الدافعين',committees:'اللجان|اللجان الفرعية للمجلس',orgchart:'الهيكل التنظيمي|مجلس عائلة العوامي',budget:'الميزانية|السجل المالي',events:'الفعاليات|الأنشطة',calendar:'التقويم|عرض تقويمي',familytree:'شجرة العائلة|الأفرع العائلية',voting:'التصويت|استطلاعات الرأي',portal:'بوابة العضو|الملف الشخصي','smart-reports':'التقارير|إنتاج وأرشفة التقارير المالية',reports:'التقارير|إحصائيات',audit:'سجل التدقيق|من غيّر ماذا ومتى','export':'تصدير البيانات|Excel و CSV',websettings:'الموقع العام|إدارة المحتوى',settings:'النسخ الاحتياطي|إدارة البيانات',riwaq:'الرِّوَاق|معرض الحكايات'};
  const [t,s]=(T[name]||'--|--').split('|');
  document.getElementById('topbar-title').innerHTML=t+` <span>${s}</span>`;
  const A={members:`<button class="btn btn-primary" onclick="openAddMember()">+ إضافة عضو</button>`,budget:`<button class="btn btn-primary" onclick="openModal('modal-tx')">+ معاملة</button>`,events:`<button class="btn btn-primary" onclick="openAddEvent()">+ فعالية</button>`,riwaq:`<button class="btn btn-primary" onclick="openAddGalleryStory()">+ إضافة قصة</button>`};
  document.getElementById('topbar-action').innerHTML=A[name]||'';
  const renderers={dashboard:renderDashboard,members:renderMembers,fees:renderFees,reminders:renderReminders,committees:renderCommittees,orgchart:renderOrgChart,budget:renderBudget,events:renderEvents,calendar:renderCalendar,familytree:renderFamilyTree,voting:renderVoting,portal:renderPortalSelect,'smart-reports':function(){ renderReportsPage(); },reports:renderReports,audit:renderAuditLog,'export':function(){},websettings:renderWebsiteSettings,settings:renderSettings,messages:renderMessages,news:renderNews,riwaq:async function(){ if(!_riwaqLoaded) await loadRiwaqData(); renderRiwaq(); }};
  if(renderers[name]) renderers[name]();
  updateSidebar();
}

// =================== WEBSITE SETTINGS ===================
function renderWebsiteSettings(){
  const ws = State.getWebsiteSettings();
  document.getElementById('ws-header-title').value = ws.header.title;
  document.getElementById('ws-header-subtitle').value = ws.header.subtitle;
  document.getElementById('ws-hero-title').value = ws.hero.title;
  document.getElementById('ws-hero-desc').value = ws.hero.description;
  document.getElementById('ws-stats-years').value = ws.stats.years;
  document.getElementById('ws-stats-committees').value = ws.stats.committees;
  document.getElementById('ws-stats-members').value = ws.stats.members;
  
  if(ws.about){
    document.getElementById('ws-about-mission').value = ws.about.mission || '';
    document.getElementById('ws-about-vision').value = ws.about.vision || '';
  }

  if(ws.contact){
    document.getElementById('ws-contact-whatsapp').value = ws.contact.whatsapp || '';
  }

  renderPositionsList();
  renderCommitteesList();
  renderValuesList();
  renderLogoPreview();
  renderAlbumsList();
  renderMediaList();
}

function renderCommitteesList(){
  const list = document.getElementById('committees-website-list');
  const committees = State.getCommittees() || [];
  list.innerHTML = committees.map(c=>`
    <div style="border:2px solid ${c.color};border-radius:10px;padding:14px;margin-bottom:10px">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
        <div style="width:40px;height:40px;background:${c.color};border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px">${c.icon}</div>
        <div style="flex:1"><div style="font-weight:700;font-size:14px">${c.name}</div><div style="font-size:11px;color:var(--text-muted)">${c.description}</div></div>
        ${c.type==='advisory'?'<span class="badge badge-purple">استشارية</span>':''}
      </div>
      <div style="font-size:11px;color:var(--text-muted);margin-top:6px">📝 اللجان الأساسية من النظام - للتعديل ارجع لصفحة "اللجان" في لوحة التحكم</div>
    </div>
  `).join('');
}

async function saveAboutSettings(){
  const mission = document.getElementById('ws-about-mission').value.trim();
  const vision  = document.getElementById('ws-about-vision').value.trim();
  try {
    await AdminSettings.saveAbout(mission, vision);
    toast('تم حفظ "عن المجلس" ✅'); log('تحديث عن المجلس','📖');
  } catch(e) { toast('خطأ في الحفظ: ' + e.message, 'error'); }
}

async function saveContactSettings(){
  const whatsapp = document.getElementById('ws-contact-whatsapp').value.trim();
  try {
    await AdminSettings.saveContact(whatsapp);
    toast('تم حفظ معلومات التواصل ✅'); log('تحديث معلومات التواصل','📞');
  } catch(e) { toast('خطأ في الحفظ: ' + e.message, 'error'); }
}

function renderLogoPreview(){
  const ws = State.getWebsiteSettings();
  if(ws.logo){
    document.getElementById('current-logo-preview').innerHTML = `<img src="${ws.logo}" style="max-width:44px;max-height:44px">`;
  }
  
  const upload = document.getElementById('logo-upload');
  if(upload && !upload.hasAttribute('data-initialized')){
    upload.setAttribute('data-initialized', 'true');
    upload.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if(file){
        if(file.size > 500*1024){
          toast('حجم الصورة أكبر من 500KB','error');
          return;
        }
        const reader = new FileReader();
        reader.onload = (ev) => {
          document.getElementById('logo-preview-img').src = ev.target.result;
          document.getElementById('logo-preview-container').style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  }
}

async function saveLogo(){
  const preview = document.getElementById('logo-preview-img').src;
  if(!preview || preview === window.location.href){
    toast('لم يتم اختيار شعار','error');
    return;
  }
  try {
    await AdminSettings.saveLogo(preview);
    toast('تم حفظ الشعار ✅');
    log('تحديث الشعار','🎨');
    document.getElementById('current-logo-preview').innerHTML = `<img src="${preview}" style="max-width:44px;max-height:44px">`;
    document.getElementById('logo-preview-container').style.display = 'none';
    document.getElementById('logo-upload').value = '';
  } catch(e) { toast('خطأ في الحفظ: ' + e.message, 'error'); }
}

function clearLogo(){
  document.getElementById('logo-preview-container').style.display = 'none';
  document.getElementById('logo-upload').value = '';
}

function resetLogoToDefault(){
  confirm2('استعادة الشعار الافتراضي؟',()=>{
    State.getWebsiteSettings().logo = null;
    saveDB();
    toast('تم استعادة الشعار الافتراضي');
    log('استعادة الشعار الافتراضي','🔄');
    document.getElementById('current-logo-preview').innerHTML = `
      <svg width="44" height="44" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M55 12 C58 8, 65 10, 64 18 C63 26, 54 30, 50 38 C46 46, 48 56, 42 62 C36 68, 26 66, 24 58 C22 50, 30 44, 32 36" stroke="#47915C" stroke-width="6" stroke-linecap="round" fill="none"/>
        <path d="M32 36 C28 44, 20 46, 20 54 C20 62, 28 66, 34 62" stroke="#47915C" stroke-width="5" stroke-linecap="round" fill="none"/>
        <circle cx="34" cy="62" r="5" fill="#47915C"/>
      </svg>`;
  });
}

function switchWSTab(id,el){
  document.querySelectorAll('#page-websettings .tab-content').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('#page-websettings .tab').forEach(t=>t.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  el.classList.add('active');
}

async function saveHeaderSettings(){
  const title    = document.getElementById('ws-header-title').value;
  const subtitle = document.getElementById('ws-header-subtitle').value;
  try {
    await AdminSettings.saveHeader(title, subtitle);
    toast('تم حفظ إعدادات الهيدر ✅'); log('تحديث الهيدر','📌');
  } catch(e) { toast('خطأ في الحفظ: ' + e.message, 'error'); }
}

async function saveHeroSettings(){
  const title       = document.getElementById('ws-hero-title').value;
  const description = document.getElementById('ws-hero-desc').value;
  try {
    await AdminSettings.saveHero(title, description);
    toast('تم حفظ إعدادات البانر ✅'); log('تحديث البانر','🎯');
  } catch(e) { toast('خطأ في الحفظ: ' + e.message, 'error'); }
}

async function saveStatsSettings(){
  const years      = parseInt(document.getElementById('ws-stats-years').value) || 0;
  const committees = parseInt(document.getElementById('ws-stats-committees').value) || 0;
  const members    = document.getElementById('ws-stats-members').value;
  try {
    await AdminSettings.saveStats(years, committees, members);
    toast('تم حفظ الإحصائيات ✅'); log('تحديث الإحصائيات','📊');
  } catch(e) { toast('خطأ في الحفظ: ' + e.message, 'error'); }
}

// =================== POSITIONS (DB-backed) ===================
var _positionsData = [];
var _positionsViewMode = 'table'; // 'table' or 'cards'
var _posSelectedMembers = []; // [{member_id, name}]
var _allMembersCache = null;

async function loadPositions(){
  try {
    const r = await PositionsAPI.getAll();
    _positionsData = (r.data || []).sort((a,b) => a.sort_order - b.sort_order);
  } catch(e) {
    _positionsData = [];
    console.error('Failed to load positions:', e);
  }
}

async function renderPositionsList(){
  await loadPositions();
  renderPositionsTable();
  renderPositionsCards();
}

function renderPositionsTable(){
  const tbody = document.getElementById('positions-table-body');
  if(!tbody) return;
  tbody.innerHTML = _positionsData.length ? _positionsData.map((p,i)=>{
    const memberNames = (p.members||[]).map(m=>esc(m.name)).join('، ') || '<span style="color:var(--text-muted)">—</span>';
    const taskCount = (p.tasks||[]).length;
    const isCore = p.is_core;
    return `<tr>
      <td>${p.sort_order}</td>
      <td style="font-weight:700">${esc(p.title)}</td>
      <td style="font-size:20px;text-align:center">${esc(p.icon)}</td>
      <td style="font-size:12px">${memberNames}</td>
      <td>${taskCount} ${taskCount===1?'مهمة':'مهام'}</td>
      <td>${isCore?'<span class="badge badge-green">أساسي</span>':''}</td>
      <td>
        <div style="display:flex;gap:4px;flex-wrap:wrap">
          <button class="btn btn-outline btn-xs" onclick="editPosition('${p.id}')">✏️ تعديل</button>
          <button class="btn btn-outline btn-xs" onclick="movePosition('${p.id}',-1)" title="↑">↑</button>
          <button class="btn btn-outline btn-xs" onclick="movePosition('${p.id}',1)" title="↓">↓</button>
          ${!isCore?`<button class="btn btn-danger btn-xs" onclick="deletePositionById('${p.id}')">🗑</button>`:''}
        </div>
      </td>
    </tr>`;
  }).join('') : '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">👑 لا توجد مناصب</td></tr>';
}

function renderPositionsCards(){
  const grid = document.getElementById('positions-grid');
  if(!grid) return;
  grid.innerHTML = _positionsData.map(p=>{
    const isFirst = p.sort_order === 1;
    const isLast = _positionsData.indexOf(p) === _positionsData.length - 1;
    const memberNames = (p.members||[]).map(m=>m.name).join(' - ') || '';
    return `<div class="position-card ${isFirst?'president':isLast?'advisory':''}" style="border:2px solid var(--border);border-radius:14px;padding:18px;${isFirst?'border-color:var(--card-president-border);background:var(--card-president-bg)':isLast?'border-color:var(--card-advisory-border);background:var(--card-advisory-bg)':''}">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
        <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--green-dark),var(--green));display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">${esc(p.icon)}</div>
        <div>
          <div style="font-size:12px;color:var(--text-muted);font-weight:600">${esc(p.title)}</div>
          <div style="font-size:13px;font-weight:700;color:var(--green-dark)">${esc(memberNames)}</div>
        </div>
        ${isFirst?'<span class="badge badge-gold" style="margin-right:auto">⭐ رئيس</span>':''}
        ${isLast?'<span class="badge badge-purple" style="margin-right:auto">استشارية</span>':''}
      </div>
      ${(p.tasks||[]).length?`<div style="font-size:12px;font-weight:700;color:var(--text-muted);margin-bottom:6px">المهام الرئيسية:</div>
      <div style="display:flex;flex-direction:column;gap:4px">
        ${p.tasks.map(t=>`<div style="display:flex;align-items:flex-start;gap:6px;font-size:12px"><span style="color:var(--green);flex-shrink:0">•</span><span>${esc(t.task_text)}</span></div>`).join('')}
      </div>`:''}
    </div>`;
  }).join('');
}

function togglePositionsView(){
  const tableView = document.getElementById('positions-table-view');
  const cardsView = document.getElementById('positions-cards-view');
  const btn = document.getElementById('pos-view-toggle');
  if(_positionsViewMode === 'table'){
    _positionsViewMode = 'cards';
    tableView.style.display = 'none';
    cardsView.style.display = '';
    btn.textContent = '📋 عرض الجدول';
  } else {
    _positionsViewMode = 'table';
    tableView.style.display = '';
    cardsView.style.display = 'none';
    btn.textContent = '🃏 عرض الكروت';
  }
}

async function openAddPosition(){
  document.getElementById('position-id').value = '';
  document.getElementById('position-title').value = '';
  document.getElementById('position-icon').value = '';
  document.getElementById('position-modal-title').textContent = '👑 إضافة منصب جديد';
  document.getElementById('position-delete-btn').style.display = 'none';
  _posSelectedMembers = [];
  renderPositionMemberTags();
  clearPositionTaskFields();
  addPositionTaskField();
  await loadPositionCommittees();
  document.getElementById('position-committee').value = '';
  openModal('modal-position');
}

async function editPosition(id){
  const p = _positionsData.find(x=>x.id===id);
  if(!p) return;
  document.getElementById('position-id').value = p.id;
  document.getElementById('position-title').value = p.title;
  document.getElementById('position-icon').value = p.icon || '';
  document.getElementById('position-modal-title').textContent = '✏️ تعديل: ' + p.title;
  document.getElementById('position-delete-btn').style.display = p.is_core ? 'none' : 'inline-flex';
  _posSelectedMembers = (p.members||[]).map(m=>({member_id:m.member_id, name:m.name}));
  renderPositionMemberTags();
  clearPositionTaskFields();
  (p.tasks||[]).forEach(t=>addPositionTaskField(t.task_text));
  if(!(p.tasks||[]).length) addPositionTaskField();
  await loadPositionCommittees();
  document.getElementById('position-committee').value = p.committee_id || '';
  openModal('modal-position');
}

async function savePosition(){
  const id = document.getElementById('position-id').value;
  const title = document.getElementById('position-title').value.trim();
  if(!title){toast('اسم المنصب مطلوب','error');return;}

  const icon = document.getElementById('position-icon').value.trim() || '📌';
  const committeeId = document.getElementById('position-committee').value || null;
  const tasks = getPositionTaskValues();
  const memberIds = _posSelectedMembers.map(m=>m.member_id);

  const btn = document.getElementById('btn-save-position');
  setBtnLoading(btn, true);

  try {
    let posId = id;
    if(id){
      // Update existing
      await PositionsAPI.update(id, {title, icon, committee_id: committeeId});
    } else {
      // Create new
      const maxSort = _positionsData.length ? Math.max(..._positionsData.map(p=>p.sort_order)) : 0;
      const r = await PositionsAPI.create({title, icon, sort_order: maxSort + 1, committee_id: committeeId});
      posId = r.data.id;
    }
    // Update members and tasks
    await PositionsAPI.updateMembers(posId, memberIds);
    await PositionsAPI.updateTasks(posId, tasks);

    closeModalSilent('modal-position');
    toast(id ? 'تم تحديث المنصب' : 'تم إضافة المنصب');
    log(id ? `تعديل منصب: ${title}` : `إضافة منصب: ${title}`, id ? '✏️' : '👑');
    await renderPositionsList();
  } catch(e) {
    toast('خطأ: ' + e.message, 'error');
  } finally {
    setBtnLoading(btn, false);
  }
}

function deletePosition(){
  const id = document.getElementById('position-id').value;
  const p = _positionsData.find(x=>x.id===id);
  if(!p) return;
  deletePositionById(id);
}

function deletePositionById(id){
  const p = _positionsData.find(x=>x.id===id);
  if(!p) return;
  if(p.is_core){ toast('لا يمكن حذف منصب أساسي','error'); return; }
  confirm2(`حذف منصب "${p.title}"؟`, async ()=>{
    try {
      await PositionsAPI.delete(id);
      closeModalSilent('modal-position');
      toast('تم حذف المنصب');
      log(`حذف منصب: ${p.title}`,'🗑️');
      await renderPositionsList();
    } catch(e) { toast('خطأ: ' + e.message, 'error'); }
  });
}

async function movePosition(id, direction){
  const idx = _positionsData.findIndex(x=>x.id===id);
  if(idx < 0) return;
  const targetIdx = idx + direction;
  if(targetIdx < 0 || targetIdx >= _positionsData.length) return;

  const current = _positionsData[idx];
  const target = _positionsData[targetIdx];

  try {
    await PositionsAPI.update(current.id, {sort_order: target.sort_order});
    await PositionsAPI.update(target.id, {sort_order: current.sort_order});
    await renderPositionsList();
  } catch(e) { toast('خطأ في الترتيب: ' + e.message, 'error'); }
}

// ── Position Modal: Member search/select ──
async function loadPositionMembers(){
  if(!_allMembersCache){
    try {
      const r = await MembersAPI.getAll();
      _allMembersCache = r.data || [];
    } catch(e) { _allMembersCache = []; }
  }
  return _allMembersCache;
}

function renderPositionMemberTags(){
  const container = document.getElementById('position-members-tags');
  if(!container) return;
  container.innerHTML = _posSelectedMembers.map(m=>`
    <span class="badge badge-green" style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;font-size:12px">
      ${esc(m.name)} <span style="cursor:pointer;font-size:14px" onclick="removePositionMember('${m.member_id}')">✕</span>
    </span>
  `).join('');
}

function removePositionMember(mid){
  _posSelectedMembers = _posSelectedMembers.filter(m=>m.member_id !== mid);
  renderPositionMemberTags();
}

// Member search input handler
(function(){
  document.addEventListener('input', function(e){
    if(e.target.id !== 'position-member-search') return;
    const q = e.target.value.trim().toLowerCase();
    const dd = document.getElementById('position-member-dropdown');
    if(!q){ dd.style.display='none'; return; }
    loadPositionMembers().then(members=>{
      const selectedIds = _posSelectedMembers.map(m=>m.member_id);
      const filtered = members.filter(m=>!selectedIds.includes(m.id) && (m.name||'').toLowerCase().includes(q)).slice(0,8);
      if(!filtered.length){ dd.style.display='none'; return; }
      dd.innerHTML = filtered.map(m=>`<div style="background:var(--surface);padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border)" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='var(--surface)'" onmousedown="selectPositionMember('${m.id}','${esc(m.name)}')">${esc(m.name)}</div>`).join('');
      dd.style.display='block';
    });
  });
  document.addEventListener('focusout', function(e){
    if(e.target.id !== 'position-member-search') return;
    setTimeout(()=>{ document.getElementById('position-member-dropdown').style.display='none'; }, 200);
  });
})();

function selectPositionMember(id, name){
  if(_posSelectedMembers.some(m=>m.member_id===id)) return;
  _posSelectedMembers.push({member_id:id, name:name});
  renderPositionMemberTags();
  document.getElementById('position-member-search').value = '';
  document.getElementById('position-member-dropdown').style.display = 'none';
}

// ── Position Modal: Dynamic task fields ──
function clearPositionTaskFields(){
  document.getElementById('position-tasks-list').innerHTML = '';
}

function addPositionTaskField(value){
  const list = document.getElementById('position-tasks-list');
  const div = document.createElement('div');
  div.style.cssText = 'display:flex;gap:6px;align-items:center';
  div.innerHTML = `<input class="form-control" style="flex:1" placeholder="نص المهمة" value="${esc(value||'')}"><button class="btn btn-danger btn-xs" onclick="this.parentElement.remove()" type="button">✕</button>`;
  list.appendChild(div);
}

function getPositionTaskValues(){
  const inputs = document.querySelectorAll('#position-tasks-list input');
  return Array.from(inputs).map(i=>i.value.trim()).filter(Boolean);
}

// ── Position Modal: Load committees dropdown ──
async function loadPositionCommittees(){
  const select = document.getElementById('position-committee');
  if(!select) return;
  select.innerHTML = '<option value="">— بدون —</option>';
  try {
    const r = await CommitteesAPI.getAll();
    (r.data||[]).forEach(c=>{
      select.innerHTML += `<option value="${c.id}">${esc(c.icon||'')} ${esc(c.name)}</option>`;
    });
  } catch(e) {}
}

// HTML escape helper for admin positions
function esc(s){ if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

// Values
function renderValuesList(){
  const list = document.getElementById('values-list');
  const values = State.getWebsiteSettings().values || [];
  list.innerHTML = values.length ? values.map((v,i)=>`
    <div style="border:2px solid var(--border);border-radius:10px;padding:14px;margin-bottom:10px;cursor:pointer" onclick="editValue(${i})">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="font-size:28px">${v.icon}</div>
        <div style="flex:1"><div style="font-weight:700;font-size:14px">${v.title}</div><div style="font-size:12px;color:var(--text-muted)">${v.desc}</div></div>
      </div>
    </div>
  `).join('') : '<div class="empty-state"><div class="empty-icon">💎</div><p>لا توجد قيم</p></div>';
}

function openAddValue(){
  document.getElementById('value-index').value = '';
  document.getElementById('value-icon').value = '';
  document.getElementById('value-title').value = '';
  document.getElementById('value-desc').value = '';
  document.getElementById('value-modal-title').textContent = '💎 إضافة قيمة جديدة';
  document.getElementById('value-delete-btn').style.display = 'none';
  openModal('modal-value');
}

function editValue(idx){
  const v = State.getWebsiteSettings().values[idx];
  document.getElementById('value-index').value = idx;
  document.getElementById('value-icon').value = v.icon;
  document.getElementById('value-title').value = v.title;
  document.getElementById('value-desc').value = v.desc;
  document.getElementById('value-modal-title').textContent = '✏️ تعديل: ' + v.title;
  document.getElementById('value-delete-btn').style.display = 'inline-flex';
  openModal('modal-value');
}

async function saveValue(){
  const icon = document.getElementById('value-icon').value.trim();
  const title = document.getElementById('value-title').value.trim();
  if(!icon || !title){toast('الأيقونة والعنوان مطلوبان','error');return;}

  const data = {icon, title, desc: document.getElementById('value-desc').value.trim()};
  const idx = document.getElementById('value-index').value;

  if(idx !== ''){
    State.getWebsiteSettings().values[idx] = data;
    log(`تعديل قيمة: ${title}`,'✏️');
  }else{
    if(!State.getWebsiteSettings().values) State.getWebsiteSettings().values = [];
    State.getWebsiteSettings().values.push(data);
    log(`إضافة قيمة: ${title}`,'💎');
  }

  try {
    await AdminSettings.saveValues(State.getWebsiteSettings().values);
    closeModalSilent('modal-value'); toast(idx!==''?'تم التحديث':'تم الإضافة'); renderValuesList();
  } catch(e) { toast('خطأ في الحفظ: ' + e.message, 'error'); }
}

function deleteValue(){
  const idx = document.getElementById('value-index').value;
  const v = State.getWebsiteSettings().values[idx];
  confirm2(`حذف قيمة "${v.title}"؟`, async ()=>{
    State.getWebsiteSettings().values.splice(idx,1);
    try {
      await AdminSettings.saveValues(State.getWebsiteSettings().values);
      closeModalSilent('modal-value'); toast('تم الحذف'); renderValuesList(); log(`حذف قيمة: ${v.title}`,'🗑️');
    } catch(e) { toast('خطأ في الحفظ: ' + e.message, 'error'); }
  });
}

// =================== ALBUMS MANAGEMENT ===================

function _populateAlbumDropdown(selectId, selectedId) {
  var sel = document.getElementById(selectId);
  if (!sel) return;
  var albums = State.getAlbums() || [];
  sel.innerHTML = '<option value="">-- بدون ألبوم --</option>';
  albums.forEach(function(a) {
    sel.innerHTML += '<option value="' + a.id + '"' + (a.id === selectedId ? ' selected' : '') + '>' + a.title + '</option>';
  });
}

function renderAlbumsList() {
  var container = document.getElementById('albums-list-admin');
  if (!container) return;
  var albums = State.getAlbums() || [];

  if (!albums.length) {
    container.innerHTML = '<div class="empty-state"><div class="empty-icon">📁</div><p>لا توجد ألبومات</p></div>';
    return;
  }

  var html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px">';
  albums.forEach(function(album) {
    html += '<div style="border:2px solid var(--border);border-radius:10px;overflow:hidden;cursor:pointer" onclick="filterMediaByAlbum(\'' + album.id + '\')">';
    if (album.cover_url) {
      html += '<img src="' + album.cover_url + '" style="width:100%;height:140px;object-fit:cover" loading="lazy">';
    } else {
      html += '<div style="width:100%;height:140px;background:var(--bg-alt);display:flex;align-items:center;justify-content:center;font-size:48px;color:var(--text-muted)">📁</div>';
    }
    html += '<div style="padding:12px">';
    html += '<div style="font-weight:700;margin-bottom:4px">' + album.title + '</div>';
    html += '<div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">' + (album.media_count || 0) + ' عنصر';
    if (album.date) html += ' &middot; ' + album.date;
    html += '</div>';
    if (album.description) html += '<div style="font-size:12px;color:var(--text-muted);margin-bottom:8px">' + album.description.substring(0, 80) + '</div>';
    html += '<div style="display:flex;gap:6px" onclick="event.stopPropagation()">';
    html += '<button class="btn btn-outline btn-xs" onclick="openEditAlbum(\'' + album.id + '\')">✏️ تعديل</button>';
    html += '<button class="btn btn-danger btn-xs" onclick="deleteAlbum(\'' + album.id + '\')">🗑️ حذف</button>';
    html += '</div>';
    html += '</div></div>';
  });
  html += '</div>';
  container.innerHTML = html;
}

function openAddAlbum() {
  document.getElementById('album-edit-id').value = '';
  document.getElementById('modal-album-title').textContent = '📁 إضافة ألبوم';
  document.getElementById('album-title').value = '';
  document.getElementById('album-description').value = '';
  document.getElementById('album-cover-url').value = '';
  document.getElementById('album-date').value = today();
  document.getElementById('album-sort-order').value = '0';
  document.getElementById('album-cover-preview').style.display = 'none';
  document.getElementById('btn-delete-album').style.display = 'none';
  openModal('modal-add-album');
}

function openEditAlbum(id) {
  var album = (State.getAlbums() || []).find(function(a) { return a.id === id; });
  if (!album) return;
  document.getElementById('album-edit-id').value = id;
  document.getElementById('modal-album-title').textContent = '✏️ تعديل ألبوم';
  document.getElementById('album-title').value = album.title || '';
  document.getElementById('album-description').value = album.description || '';
  document.getElementById('album-cover-url').value = album.cover_url || '';
  document.getElementById('album-date').value = album.date || '';
  document.getElementById('album-sort-order').value = album.sort_order || 0;
  var preview = document.getElementById('album-cover-preview');
  if (album.cover_url) {
    document.getElementById('album-cover-preview-img').src = album.cover_url;
    preview.style.display = 'block';
  } else {
    preview.style.display = 'none';
  }
  document.getElementById('btn-delete-album').style.display = 'inline-flex';
  openModal('modal-add-album');
}

async function saveAlbum() {
  var title = document.getElementById('album-title').value.trim();
  if (!title) { toast('عنوان الألبوم مطلوب', 'error'); return; }

  var editId = document.getElementById('album-edit-id').value;
  var data = {
    title: title,
    description: document.getElementById('album-description').value.trim(),
    cover_url: document.getElementById('album-cover-url').value.trim(),
    date: document.getElementById('album-date').value || null,
    sort_order: parseInt(document.getElementById('album-sort-order').value) || 0,
  };

  try {
    if (editId) {
      await AdminAlbums.update(editId, data);
      toast('تم تعديل الألبوم');
    } else {
      await AdminAlbums.create(data);
      toast('تم إضافة الألبوم');
    }
    closeModalSilent('modal-add-album');
    renderAlbumsList();
    renderMediaList();
  } catch (e) { toast('خطأ: ' + e.message, 'error'); }
}

async function deleteAlbum(id) {
  confirm2('حذف هذا الألبوم؟ الميديا داخله ستبقى بدون ألبوم.', async function() {
    try {
      await AdminAlbums.delete(id);
      toast('تم حذف الألبوم');
      renderAlbumsList();
      renderMediaList();
    } catch (e) { toast('خطأ: ' + e.message, 'error'); }
  });
}

function deleteAlbumFromModal() {
  var id = document.getElementById('album-edit-id').value;
  if (!id) return;
  confirm2('حذف هذا الألبوم؟', async function() {
    try {
      await AdminAlbums.delete(id);
      toast('تم حذف الألبوم');
      closeModalSilent('modal-add-album');
      renderAlbumsList();
      renderMediaList();
    } catch (e) { toast('خطأ: ' + e.message, 'error'); }
  });
}

function filterMediaByAlbum(albumId) {
  renderMediaList(albumId);
  var container = document.getElementById('media-list-admin');
  if (container) container.scrollIntoView({ behavior: 'smooth' });
}

// =================== MEDIA MANAGEMENT ===================

function openAddMedia(albumId) {
  document.getElementById('media-edit-id').value = '';
  document.getElementById('modal-media-title').textContent = '📷 إضافة ميديا';
  document.getElementById('media-title').value = '';
  document.getElementById('media-type').value = 'images';
  document.getElementById('media-url').value = '';
  document.getElementById('media-date').value = today();
  document.getElementById('media-tags').value = '';
  document.getElementById('media-url-preview').style.display = 'none';
  _populateAlbumDropdown('media-album-id', albumId || '');
  openModal('modal-add-media');
}

async function saveMedia(addAnother) {
  var title = document.getElementById('media-title').value.trim();
  var url   = document.getElementById('media-url').value.trim();
  var editId = document.getElementById('media-edit-id').value;

  if (!title || !url) { toast('الرجاء ملء العنوان والرابط', 'error'); return; }

  var tags = document.getElementById('media-tags').value
    .split(',').map(function(t) { return t.trim(); }).filter(Boolean);
  var data = {
    title: title,
    type: document.getElementById('media-type').value,
    url: url,
    date: document.getElementById('media-date').value || today(),
    tags: tags,
    album_id: document.getElementById('media-album-id').value || null,
  };

  try {
    if (editId) {
      await AdminMedia.update(editId, data);
      toast('تم التعديل');
    } else {
      await AdminMedia.create(data);
      toast('تم إضافة الميديا');
    }

    if (addAnother && !editId) {
      // حفظ وإضافة جديد: فرّغ العنوان والرابط فقط
      document.getElementById('media-edit-id').value = '';
      document.getElementById('media-title').value = '';
      document.getElementById('media-url').value = '';
      document.getElementById('media-url-preview').style.display = 'none';
      document.getElementById('modal-media-title').textContent = '📷 إضافة ميديا';
    } else {
      closeModalSilent('modal-add-media');
    }
    renderAlbumsList();
    renderMediaList();
  } catch (e) { toast('خطأ: ' + e.message, 'error'); }
}

function _renderMediaThumbnail(item) {
  var icon = item.type === 'images' ? '📷' : item.type === 'videos' ? '🎥' : '▶️';
  var html = '';
  if (item.type === 'images') {
    html += '<img src="' + item.url + '" style="width:100%;height:150px;object-fit:cover" loading="lazy">';
  } else if (item.type === 'videos') {
    html += '<video src="' + item.url + '" style="width:100%;height:150px;object-fit:cover" preload="metadata"></video>';
  } else if (item.type === 'youtube') {
    var ytId = ''; var ytMatch = item.url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
    if (ytMatch) ytId = ytMatch[1];
    if (ytId) {
      html += '<div style="position:relative;width:100%;height:150px;background:#000;overflow:hidden">';
      html += '<img src="https://img.youtube.com/vi/' + ytId + '/mqdefault.jpg" style="width:100%;height:100%;object-fit:cover;opacity:.8">';
      html += '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center"><div style="width:44px;height:44px;background:rgba(255,0,0,.85);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px">▶</div></div>';
      html += '</div>';
    } else {
      html += '<div style="width:100%;height:150px;background:#111;display:flex;align-items:center;justify-content:center;font-size:48px">▶️</div>';
    }
  }
  return { html: html, icon: icon };
}

function renderMediaList(filterAlbumId) {
  var container = document.getElementById('media-list-admin');
  if (!container) return;
  var media = State.getMedia() || [];
  var albums = State.getAlbums() || [];

  // فلترة حسب الألبوم
  if (filterAlbumId) {
    media = media.filter(function(m) { return m.album_id === filterAlbumId; });
    var albumName = (albums.find(function(a) { return a.id === filterAlbumId; }) || {}).title || '';
    container.innerHTML = '<div style="margin-bottom:12px"><button class="btn btn-outline btn-sm" onclick="renderMediaList()">← كل الميديا</button> <strong>' + albumName + '</strong></div>';
  } else {
    container.innerHTML = '';
  }

  if (!media.length) {
    container.innerHTML += '<div class="empty-state"><div class="empty-icon">📷</div><p>لا توجد وسائط</p></div>';
    return;
  }

  var html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px">';
  media.forEach(function(item) {
    var thumb = _renderMediaThumbnail(item);
    var albumLabel = '';
    if (!filterAlbumId && item.album_id) {
      var alb = albums.find(function(a) { return a.id === item.album_id; });
      if (alb) albumLabel = '<span class="badge badge-green" style="margin:2px">📁 ' + alb.title + '</span>';
    }

    html += '<div style="border:2px solid var(--border);border-radius:10px;overflow:hidden">';
    html += thumb.html;
    html += '<div style="padding:12px">';
    html += '<div style="font-weight:700;margin-bottom:6px">' + thumb.icon + ' ' + item.title + '</div>';
    if (item.date) html += '<div style="font-size:12px;color:var(--text-muted);margin-bottom:8px">' + new Date(item.date).toLocaleDateString('ar-SA') + '</div>';
    html += albumLabel;
    if (item.tags && item.tags.length) {
      item.tags.forEach(function(tag) {
        html += '<span class="badge badge-gray" style="margin:2px">' + tag + '</span>';
      });
    }
    html += '<div style="display:flex;gap:6px;margin-top:10px">';
    html += '<button class="btn btn-outline btn-xs" onclick="openEditMedia(\'' + item.id + '\')">✏️ تعديل</button>';
    html += '<button class="btn btn-danger btn-xs" onclick="deleteMedia(\'' + item.id + '\')">🗑️ حذف</button>';
    html += '</div>';
    html += '</div></div>';
  });
  html += '</div>';
  container.innerHTML += html;
}

async function deleteMedia(id) {
  confirm2('حذف هذه الميديا؟', async function() {
    try {
      await AdminMedia.delete(id);
      toast('تم الحذف');
      renderAlbumsList();
      renderMediaList();
    } catch (e) { toast('خطأ: ' + e.message, 'error'); }
  });
}

// =================== BULK MEDIA ===================

function openBulkMedia(albumId) {
  _populateAlbumDropdown('bulk-album-id', albumId || '');
  document.getElementById('bulk-date').value = today();
  document.getElementById('bulk-tags').value = '';
  document.getElementById('bulk-rows').innerHTML = '';
  addBulkRow(); addBulkRow(); addBulkRow();
  openModal('modal-bulk-media');
}

function addBulkRow() {
  var container = document.getElementById('bulk-rows');
  var row = document.createElement('div');
  row.style.cssText = 'display:grid;grid-template-columns:1fr auto 1fr auto;gap:8px;margin-bottom:8px;align-items:end';
  row.innerHTML = '<div class="form-group" style="margin:0"><label class="form-label" style="font-size:11px">العنوان</label><input class="form-control bulk-title" placeholder="عنوان"></div>'
    + '<div class="form-group" style="margin:0"><label class="form-label" style="font-size:11px">النوع</label><select class="form-control bulk-type"><option value="images">📷</option><option value="videos">🎥</option><option value="youtube">▶️</option></select></div>'
    + '<div class="form-group" style="margin:0"><label class="form-label" style="font-size:11px">الرابط</label><input class="form-control bulk-url" placeholder="https://..."></div>'
    + '<button class="btn btn-danger btn-xs" onclick="this.parentElement.remove()" style="height:36px">✕</button>';
  container.appendChild(row);
}

async function saveBulkMedia() {
  var rows = document.querySelectorAll('#bulk-rows > div');
  var albumId = document.getElementById('bulk-album-id').value || null;
  var date = document.getElementById('bulk-date').value || today();
  var tags = document.getElementById('bulk-tags').value.split(',').map(function(t) { return t.trim(); }).filter(Boolean);

  var items = [];
  rows.forEach(function(row) {
    var title = row.querySelector('.bulk-title').value.trim();
    var type  = row.querySelector('.bulk-type').value;
    var url   = row.querySelector('.bulk-url').value.trim();
    if (title && url) {
      items.push({ title: title, type: type, url: url });
    }
  });

  if (!items.length) { toast('أدخل عنصر واحد على الأقل', 'error'); return; }

  try {
    await AdminMedia.createBulk({ items: items, album_id: albumId, date: date, tags: tags });
    toast('تم إضافة ' + items.length + ' عنصر');
    closeModalSilent('modal-bulk-media');
    renderAlbumsList();
    renderMediaList();
  } catch (e) { toast('خطأ: ' + e.message, 'error'); }
}

// =================== SETTINGS ===================
function renderSettings(){
  document.getElementById('stats-members').textContent = State.getMembers().length;
  document.getElementById('stats-events').textContent = State.getEvents().length;
  document.getElementById('stats-tx').textContent = State.getTransactions().length;
  
  const dataSize = new Blob([JSON.stringify(State.getDB())]).size;
  document.getElementById('stats-size').textContent = (dataSize / 1024).toFixed(1);
  
  // Load meeting data
  if(State.getNextMeeting()){
    const datetime = State.getNextMeeting().date.split('T');
    document.getElementById('meeting-date').value = datetime[0];
    document.getElementById('meeting-time').value = datetime[1] ? datetime[1].slice(0,5) : '10:00';
    document.getElementById('meeting-title').value = State.getNextMeeting().title || 'الجلسة العمومية للمجلس';
  }
  
  // Setup import file handler
  const importFile = document.getElementById('import-file');
  if(importFile && !importFile.hasAttribute('data-initialized')){
    importFile.setAttribute('data-initialized', 'true');
    importFile.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if(file) importData(file);
      e.target.value = ''; // Reset input
    });
  }
}

function saveMeeting(){
  const date = document.getElementById('meeting-date').value;
  const time = document.getElementById('meeting-time').value || '10:00';
  const title = document.getElementById('meeting-title').value.trim() || 'الجلسة العمومية للمجلس';
  
  if(!date){
    toast('اختر تاريخ الجلسة','error');
    return;
  }
  
  State.setNextMeetingObj({
    date: date + 'T' + time,
    title: title,
    visible: true
  });
  
  saveDB();
  updateCountdown();
  toast('تم حفظ موعد الجلسة ✅');
  log('تحديث موعد الجلسة','⏰');
  
  // Show preview
  const preview = document.getElementById('meeting-preview');
  const previewText = document.getElementById('meeting-preview-text');
  const dateObj = new Date(State.getNextMeeting().date);
  previewText.textContent = `${title} - ${dateObj.toLocaleDateString('ar-SA', {weekday:'long', year:'numeric', month:'long', day:'numeric', hour:'2-digit', minute:'2-digit'})}`;
  preview.style.display = 'block';
}

function hideMeeting(){
  if(!State.getNextMeeting()){
    toast('لا توجد جلسة محفوظة','error');
    return;
  }
  
  State.getNextMeeting().visible = !State.getNextMeeting().visible;
  saveDB();
  updateCountdown();
  toast(State.getNextMeeting().visible ? 'تم إظهار العد التنازلي' : 'تم إخفاء العد التنازلي');
}

function clearMeeting(){
  confirm2('هل تريد حذف موعد الجلسة القادمة؟', () => {
    State.clearNextMeeting();
    saveDB();
    updateCountdown();
    document.getElementById('meeting-date').value = '';
    document.getElementById('meeting-time').value = '10:00';
    document.getElementById('meeting-title').value = 'الجلسة العمومية للمجلس';
    document.getElementById('meeting-preview').style.display = 'none';
    toast('تم حذف الجلسة');
    log('حذف موعد الجلسة','🗑️');
  });
}

function updateSidebar(){
  document.getElementById('sb-members').textContent=State.getMembers().length;
  document.getElementById('sb-committees').textContent=State.getCommittees().length;
  const inc=State.getTransactions().filter(t=>t.type==='إيراد').reduce((s,t)=>s+t.amount,0);
  const exp=State.getTransactions().filter(t=>t.type==='مصروف').reduce((s,t)=>s+t.amount,0);
  document.getElementById('sb-balance').textContent=fmt(inc-exp);
  updateCountdown();
}

// =================== COUNTDOWN ===================
function updateCountdown(){
  const widget=document.getElementById('countdown-widget');
  const display=document.getElementById('countdown-display');
  const dateEl=document.getElementById('countdown-date');
  
  if(!State.getNextMeeting() || State.getNextMeeting().visible === false){
    widget.style.display='none';
    return;
  }
  
  widget.style.display='block';
  const target=new Date(State.getNextMeeting().date);
  const now=new Date();
  const diff=target-now;
  
  if(diff<0){
    display.textContent='انتهت';
    dateEl.textContent='';
    return;
  }
  
  const days=Math.floor(diff/(1000*60*60*24));
  const hours=Math.floor((diff%(1000*60*60*24))/(1000*60*60));
  const mins=Math.floor((diff%(1000*60*60))/(1000*60));
  display.textContent=`${days} يوم ${hours} س ${mins} د`;
  dateEl.textContent=target.toLocaleDateString('ar-SA',{weekday:'short',year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
}
setInterval(updateCountdown,60000); // update every minute

// =================== MEMBERS ===================
function openAddMember(){ clearMemberForm(); document.getElementById('modal-member-title').textContent='➕ إضافة عضو جديد'; openModal('modal-member'); _memberDupConfirmed=false; }
function clearMemberForm(){ ['mm-id','mm-name','mm-phone','mm-notes'].forEach(id=>document.getElementById(id).value=''); document.getElementById('mm-join').value=today(); document.getElementById('mm-status').value='مشترك'; _memberDupConfirmed=false; }

// ── متغير لتأكيد المتابعة عند التكرار ──
var _memberDupConfirmed = false;

// ── التحقق من بيانات العضو ──
function validateMemberForm(){
  var valid = true;
  var editId = document.getElementById('mm-id').value;

  // مسح أخطاء سابقة
  clearValidation();
  ['mm-name-error','mm-phone-error'].forEach(function(id){ var el=document.getElementById(id); if(el) el.textContent=''; });

  // — الاسم: مطلوب + ثلاثي —
  var nameEl = document.getElementById('mm-name');
  var nameVal = nameEl.value.trim();
  var nameWords = nameVal.split(/\s+/).filter(function(w){ return w.length>0; });

  if(!nameVal){
    setFieldError('mm-name','mm-name-error','يجب إدخال اسم العضو');
    valid = false;
  } else if(nameWords.length < 3){
    setFieldError('mm-name','mm-name-error','يجب إدخال الاسم الثلاثي على الأقل');
    valid = false;
  }

  // — الاسم: كشف التكرار —
  if(valid && !_memberDupConfirmed){
    var dupName = State.getMembers().find(function(m){
      if(editId && m.id === editId) return false;
      return m.name.trim().toLowerCase() === nameVal.toLowerCase();
    });
    if(dupName){
      setFieldError('mm-name','mm-name-error','يوجد عضو بنفس الاسم — اضغط حفظ مرة أخرى للمتابعة');
      _memberDupConfirmed = true;
      return false;
    }
  }

  // — الجوال: صيغة —
  var phoneEl = document.getElementById('mm-phone');
  var phoneVal = phoneEl.value.trim();
  if(phoneVal){
    if(!/^05\d{8}$/.test(phoneVal)){
      setFieldError('mm-phone','mm-phone-error','صيغة رقم الجوال غير صحيحة — يجب أن يبدأ بـ 05 ويكون 10 أرقام');
      valid = false;
    } else {
      // — الجوال: كشف التكرار —
      var dupPhone = State.getMembers().find(function(m){
        if(editId && m.id === editId) return false;
        return m.phone && m.phone.replace(/\s/g,'') === phoneVal.replace(/\s/g,'');
      });
      if(dupPhone){
        setFieldError('mm-phone','mm-phone-error','يوجد عضو بنفس رقم الجوال ('+dupPhone.name+')');
        valid = false;
      }
    }
  }

  return valid;
}

function setFieldError(fieldId, errorId, msg){
  var field = document.getElementById(fieldId);
  var err = document.getElementById(errorId);
  if(field){
    field.classList.add('invalid');
    var group = field.closest('.form-group');
    if(group) group.classList.add('has-error');
  }
  if(err) err.textContent = msg;
}

async function saveMember(){
  clearValidation();
  ['mm-name-error','mm-phone-error'].forEach(function(id){ var el=document.getElementById(id); if(el) el.textContent=''; });
  if(!validateMemberForm()) return;
  const name=document.getElementById('mm-name').value.trim();
  const id=document.getElementById('mm-id').value;
  const data={name,phone:document.getElementById('mm-phone').value.trim(),joinDate:document.getElementById('mm-join').value||today(),status:document.getElementById('mm-status').value,notes:document.getElementById('mm-notes').value};
  const btn=document.getElementById('btn-save-member');
  setBtnLoading(btn,true);
  try{ await MemberService.saveMember(id, data); _memberDupConfirmed=false; }finally{ setBtnLoading(btn,false); }
}

function editMember(id){
  const m=State.getMembers().find(x=>x.id===id); if(!m) return;
  document.getElementById('mm-id').value=id;
  document.getElementById('mm-name').value=m.name;
  document.getElementById('mm-phone').value=m.phone||'';
  document.getElementById('mm-join').value=m.joinDate||'';
  document.getElementById('mm-notes').value=m.notes||'';
  document.getElementById('mm-status').value=m.status;
  document.getElementById('modal-member-title').textContent='✏️ تعديل بيانات العضو';
  openModal('modal-member');
}

function deleteMember(id){
  MemberService.deleteMember(id);
}

function renderMembers(page){
  const s=document.getElementById('m-search').value;
  const sf=document.getElementById('m-flt-status').value;
  const af=document.getElementById('m-flt-account')?.value||'';
  let list=State.getMembers();
  if(s) list=list.filter(m=>{
    if(m.name.includes(s)||m.phone?.includes(s)) return true;
    var acc=typeof getMemberAccount==='function'?getMemberAccount(m.id):null;
    return acc && acc.awm_id && acc.awm_id.toLowerCase().includes(s.toLowerCase());
  });
  if(sf) list=list.filter(m=>m.status===sf);
  // فلترة حسب حالة الحساب
  if(af && typeof getMemberAccount==='function'){
    list=list.filter(m=>{
      const acc=getMemberAccount(m.id);
      if(af==='none') return !acc;
      if(af==='active') return acc && acc.is_active;
      if(af==='inactive') return acc && !acc.is_active;
      return true;
    });
  }
  const p=curPeriod();
  const tbody=document.getElementById('members-tbody');
  if(!list.length){tbody.innerHTML='<tr><td colspan="10"><div class="empty-state"><div class="empty-icon">👥</div><p>لا يوجد أعضاء</p></div></td></tr>';var mp=document.getElementById('members-pagination');if(mp)mp.innerHTML='';return;}
  var pg = paginate(list, 'members', page, 25);
  var startIdx = (pg.page - 1) * 25;
  tbody.innerHTML=pg.data.map((m,i)=>{
    const pay=p?State.getPayments().find(x=>x.memberId===m.id&&x.periodId===p.id):null;
    const pb=!p?'<span class="badge badge-gray">لا دورة</span>':pay?.status==='مدفوع'?'<span class="badge badge-success">✅ مدفوع</span>':'<span class="badge badge-warning">⏳ لم يدفع</span>';
    const sb=m.status==='مشترك'?'<span class="badge badge-success">مشترك</span>':m.status==='منقطع'?'<span class="badge badge-warning">منقطع</span>':'<span class="badge badge-gray">غير مشترك</span>';
    const acc=typeof getMemberAccount==='function'?getMemberAccount(m.id):null;
    const awmId=acc?acc.awm_id:'—';
    const accBadge=typeof memberAccountBadge==='function'?memberAccountBadge(m.id):'';
    var comms=memberCommittees(m.id);
    return `<tr><td data-label="#" style="color:var(--text-muted);font-size:11px">${startIdx+i+1}</td>
    <td data-label="العضو"><div style="display:flex;align-items:center;gap:8px"><div class="avatar" style="background:${avColor(m.name)}">${avInit(m.name)}</div><div><div style="font-weight:600">${m.name}</div><div style="font-size:11px;color:var(--text-muted)">${m.family}</div></div></div></td>
    <td data-label="AWM-ID" style="font-size:12px;font-weight:600;color:var(--green-dark);font-family:monospace">${awmId}</td>
    <td data-label="الجوال">${m.phone||'—'}</td>
    <td data-label="اللجان" class="committee-cell" title="${comms.replace(/<[^>]*>/g,'')}">${comms}</td>
    <td data-label="الانضمام" style="font-size:11px">${m.joinDate||'—'}</td><td data-label="الحالة">${sb}</td><td data-label="الحساب">${accBadge}</td><td data-label="الدفع">${pb}</td>
    <td data-label="إجراءات"><div class="actions-cell">
      <button class="btn btn-outline btn-xs" onclick="editMember('${m.id}')" title="تعديل">✏️</button>
      ${p?`<button class="btn btn-accent btn-xs" onclick="openPayModal('${m.id}')" title="تسجيل دفع">💳</button>`:''}
      ${typeof memberAuthButtons==='function'?memberAuthButtons(m.id):''}
      <button class="btn btn-danger btn-xs" onclick="deleteMember('${m.id}')" title="حذف">🗑️</button>
    </div></td></tr>`;
  }).join('');
  var totalAll=State.getMembers().length;
  var countEl=document.getElementById('members-count');
  if(pg.total<totalAll) countEl.textContent='عرض '+pg.total+' من أصل '+totalAll+' عضو';
  else countEl.textContent=pg.total+' عضو';
  var paginEl=document.getElementById('members-pagination');
  if(paginEl) paginEl.innerHTML = renderPaginationHTML(pg, 'renderMembers');
}
function resetMembersFilters(){
  document.getElementById('m-search').value='';
  document.getElementById('m-flt-status').value='';
  var accFlt=document.getElementById('m-flt-account'); if(accFlt) accFlt.value='';
  _pageState.members=1;
  renderMembers();
}
var debouncedRenderMembers = debounce(function(){ _pageState.members=1; renderMembers(); }, 200);

// =================== FEES ===================
async function createPeriod(){
  clearValidation();
  if(!validateRequired('pd-name','اسم الدورة')) return;
  if(!validateRequired('pd-amount','مبلغ الرسوم')) return;
  const name=document.getElementById('pd-name').value.trim(); const amount=parseFloat(document.getElementById('pd-amount').value);
  if(!amount||amount<=0){toast('مبلغ الرسوم يجب أن يكون أكبر من صفر','error');document.getElementById('pd-amount').classList.add('invalid');return;}
  const data={name,feeAmount:amount,start:document.getElementById('pd-start').value||today(),end:document.getElementById('pd-end').value||''};
  const btn=document.getElementById('btn-create-period');
  setBtnLoading(btn,true);
  try{ await FinanceService.createPeriod(data); }finally{ setBtnLoading(btn,false); }
  // بعد إنشاء الدورة — عرض مراجعة الحالات تلقائياً
  setTimeout(function(){ loadStatusReview(); }, 800);
}

function openPayModal(memberId){
  const m=State.getMembers().find(x=>x.id===memberId); if(!m) return;
  const p=curPeriod(); if(!p){toast('لا توجد دورة مفعّلة','error');return;}
  const pay=State.getPayments().find(x=>x.memberId===memberId&&x.periodId===p.id);
  document.getElementById('pay-mid').value=memberId;
  document.getElementById('pay-mname').value=m.name;
  document.getElementById('pay-amount').value=pay?.amount||p.feeAmount;
  document.getElementById('pay-date').value=pay?.date||today();
  document.getElementById('pay-method').value=pay?.method||'تحويل بنكي';
  document.getElementById('pay-status').value=pay?.status||'لم يدفع';
  document.getElementById('pay-notes').value=pay?.notes||'';
  openModal('modal-pay');
}

async function savePayment(){
  const memberId=document.getElementById('pay-mid').value; const p=curPeriod(); if(!p) return;
  const paymentData={
    status: document.getElementById('pay-status').value,
    amount: parseFloat(document.getElementById('pay-amount').value)||0,
    date:   document.getElementById('pay-date').value,
    method: document.getElementById('pay-method').value,
    notes:  document.getElementById('pay-notes').value
  };
  const btn=document.getElementById('btn-save-payment');
  setBtnLoading(btn,true);
  try{ await FinanceService.savePayment(memberId, paymentData); }finally{ setBtnLoading(btn,false); }
}

function renderFees(page){
  const p=curPeriod();
  document.getElementById('fees-period-lbl').textContent=p?p.name:'لا توجد دورة';
  if(!p){ document.getElementById('fees-stats').innerHTML='<div style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:16px">أنشئ دورة أولاً</div>'; document.getElementById('fees-tbody').innerHTML=''; var fp=document.getElementById('fees-pagination');if(fp)fp.innerHTML=''; return; }
  const pays=State.getPayments().filter(x=>x.periodId===p.id);
  const paid=pays.filter(x=>x.status==='مدفوع'); const unpaid=pays.filter(x=>x.status==='لم يدفع');
  const collected=paid.reduce((s,x)=>s+x.amount,0);
  document.getElementById('fees-stats').innerHTML=`
    <div style="background:#dcfce7;border-radius:10px;padding:12px;text-align:center"><div style="font-size:20px;font-weight:900;color:#166534">${paid.length}</div><div style="font-size:11px;color:#166534">دفعوا ✅</div></div>
    <div style="background:#fef9c3;border-radius:10px;padding:12px;text-align:center"><div style="font-size:20px;font-weight:900;color:#854d0e">${unpaid.length}</div><div style="font-size:11px;color:#854d0e">لم يدفعوا ⏳</div></div>
    <div style="background:#dbeafe;border-radius:10px;padding:12px;text-align:center"><div style="font-size:20px;font-weight:900;color:#1e40af">${fmt(collected)}</div><div style="font-size:11px;color:#1e40af">ريال محصّلة 💰</div></div>`;
  let list=pays; const s=document.getElementById('fees-search').value; const sf=document.getElementById('fees-flt').value;
  if(s) list=list.filter(x=>{ const m=State.getMembers().find(y=>y.id===x.memberId); return m?.name.includes(s); });
  if(sf) list=list.filter(x=>x.status===sf);
  var pg = paginate(list, 'fees', page, 25);
  document.getElementById('fees-tbody').innerHTML=pg.data.map(pay=>{
    const m=State.getMembers().find(x=>x.id===pay.memberId); if(!m) return '';
    const sb=pay.status==='مدفوع'?'<span class="badge badge-success">✅ مدفوع</span>':'<span class="badge badge-warning">⏳ لم يدفع</span>';
    return `<tr><td data-label="العضو"><div style="display:flex;align-items:center;gap:8px"><div class="avatar" style="background:${avColor(m.name)};width:30px;height:30px;font-size:11px">${avInit(m.name)}</div><div><div style="font-weight:600">${m.name}</div><div style="font-size:11px;color:var(--text-muted)">${m.family}</div></div></div></td>
    <td data-label="المطلوب">${fmt(pay.required||p.feeAmount)} ريال</td>
    <td data-label="المدفوع" style="font-weight:700;color:${pay.status==='مدفوع'?'var(--green)':'var(--text-muted)'}">${pay.status==='مدفوع'?fmt(pay.amount)+' ريال':'—'}</td>
    <td data-label="التاريخ" style="font-size:11px">${pay.date||'—'}</td><td data-label="الطريقة" style="font-size:11px">${pay.method||'—'}</td>
    <td data-label="الحالة">${sb}</td><td data-label="إجراء"><button class="btn btn-accent btn-xs" onclick="openPayModal('${m.id}')">💳 تحديث</button></td></tr>`;
  }).join('');
  var paginEl=document.getElementById('fees-pagination');
  if(paginEl) paginEl.innerHTML = renderPaginationHTML(pg, 'renderFees');
}
var debouncedRenderFees = debounce(function(){ _pageState.fees=1; renderFees(); });

// =================== COMMITTEES ===================
function renderCommittees(){
  document.getElementById('committees-grid').innerHTML=State.getCommittees().map(c=>{
    const mems = c.member_count || (State.getCommitteeMembers()[c.id] || []).length || 0;
    const evs=State.getEvents().filter(e=>e.committeeId===c.id).length;
    return `<div class="committee-card" onclick="showCommitteeDetail('${c.id}')">
      <div class="committee-banner" style="background:${c.color}">${c.icon}
        <span style="position:absolute;top:8px;right:8px;background:rgba(255,255,255,.85);color:#166534;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700">👥 ${mems}</span>
        <button class="btn btn-xs" style="position:absolute;top:8px;left:8px;background:rgba(255,255,255,.9);color:#333;border:none;font-size:11px" onclick="event.stopPropagation();openEditCommitteeModal('${c.id}')">✏️</button>
      </div>
      <div class="committee-body"><div class="committee-title">${c.name}</div><div class="committee-meta">${c.desc||''}</div>${c.advisory?'<div style="margin-top:5px"><span class="badge badge-purple">🎓 استشارية</span></div>':''}</div>
      <div class="committee-footer"><span style="font-size:11px;color:var(--text-muted)">👥 ${mems} عضو  •  🗓️ ${evs} فعالية</span><button class="btn btn-outline btn-xs" onclick="event.stopPropagation();showCommitteeDetail('${c.id}')">تفاصيل</button></div>
    </div>`;
  }).join('');
}

function showCommitteeDetail(cid){
  const c=State.getCommittees().find(x=>x.id===cid); if(!c) return;
  document.getElementById('cdetail-title').innerHTML=`${c.icon} ${c.name}`;

  const customIds=State.getCommitteeMembers()[cid]||[];
  const allMembers=State.getMembers().filter(m=>customIds.includes(m.id));
  const notIn=State.getMembers().filter(m=>!customIds.includes(m.id));

  document.getElementById('cdetail-body').innerHTML=`
    <div style="padding:12px;background:var(--bg);border:1px solid var(--border);border-radius:10px;margin-bottom:14px">
      <div style="font-size:13px;color:var(--text-muted)">${c.desc||'لا يوجد وصف'}</div>
      ${c.advisory?'<div style="margin-top:6px"><span class="badge badge-purple">🎓 لجنة استشارية</span></div>':''}
    </div>
    <div style="font-size:13px;font-weight:700;margin-bottom:8px">👥 أعضاء اللجنة (${allMembers.length})</div>
    ${allMembers.length?`<div style="margin-bottom:12px">${allMembers.map(m=>`<div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid var(--border)"><div class="avatar" style="background:${avColor(m.name)}">${avInit(m.name)}</div><div style="flex:1"><div style="font-weight:600;font-size:13px">${m.name}</div></div><button class="btn btn-danger btn-xs" onclick="removeMemberFromCommittee('${cid}','${m.id}')">✕</button></div>`).join('')}</div>`:'<div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px">لا يوجد أعضاء مرتبطون بعد</div>'}
    ${notIn.length&&State.getMembers().length?`<div style="display:flex;gap:8px;margin-top:8px"><select class="form-control" id="add-to-c-select" style="flex:1">${notIn.map(m=>`<option value="${m.id}">${m.name}</option>`).join('')}</select><button class="btn btn-primary btn-sm" onclick="addMemberToCommittee('${cid}')">+ ربط عضو</button></div>`:''}
  `;
  openModal('modal-committee-detail');
}

function addMemberToCommittee(cid){ const mid=document.getElementById('add-to-c-select')?.value; MemberService.addMemberToCommittee(cid, mid); }
function removeMemberFromCommittee(cid, mid) {
  const m = State.getMembers().find(x => x.id === mid);
  confirm2('إزالة "' + (m ? m.name : '') + '" من اللجنة؟', function() {
    MemberService.removeMemberFromCommittee(cid, mid);
  });
}

// =================== ORG CHART ===================
function renderOrgChart(){
  const advisory=State.getCommittees().find(c=>c.advisory);
  const regular=State.getCommittees().filter(c=>!c.advisory);
  const positions=(_positionsData||[]).filter(p=>p.title!=='اللجنة الاستشارية');
  document.getElementById('org-body').innerHTML=`
    <div style="text-align:center">
      ${advisory?`<div style="margin-bottom:16px;display:flex;justify-content:center"><div style="border:2px dashed var(--card-advisory-border);border-radius:12px;padding:10px 20px;background:var(--card-advisory-bg);display:inline-flex;align-items:center;gap:10px"><span style="font-size:20px">${advisory.icon}</span><div><div style="font-weight:700;font-size:13px;color:var(--primary)">${advisory.name}</div><div style="font-size:10px;color:var(--text-muted)">جهة استشارية</div></div></div></div>`:''}
      <div style="display:flex;justify-content:center;margin-bottom:6px">
        <div style="background:linear-gradient(135deg,var(--green-dark),var(--primary));color:#fff;border-radius:14px;padding:14px 28px;display:inline-flex;align-items:center;gap:12px">
          <span style="font-size:22px">🏛️</span>
          <div style="text-align:right"><div style="font-size:14px;font-weight:700">إدارة مجلس صندوق عائلة العوامي</div></div>
        </div>
      </div>
      ${positions.length?`<div style="display:flex;justify-content:center;margin:2px 0"><div style="width:2px;height:20px;background:var(--border)"></div></div>
      <div style="display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
        ${positions.map(p=>`<div style="border:1px solid var(--border);border-radius:8px;padding:6px 12px;background:var(--bg-card);display:inline-flex;align-items:center;gap:6px"><span style="font-size:14px">${p.icon}</span><span style="font-size:11px;font-weight:600;color:var(--text)">${p.title}</span></div>`).join('')}
      </div>`:''}
      <div style="display:flex;justify-content:center;margin:2px 0"><div style="width:2px;height:20px;background:var(--border)"></div></div>
      <div style="width:80%;height:2px;background:var(--border);margin:0 auto"></div>
      <div style="display:flex;justify-content:center;gap:8px;flex-wrap:wrap;padding-top:0">
        ${regular.map(c=>{ const mems = c.member_count || (State.getCommitteeMembers()[c.id] || []).length || 0; return `<div style="display:flex;flex-direction:column;align-items:center"><div style="width:2px;height:20px;background:var(--border)"></div><div style="border:2px solid var(--border);border-radius:10px;padding:10px 12px;min-width:120px;background:var(--bg-card);cursor:pointer;transition:all .2s" onmouseover="this.style.borderColor='var(--green)'" onmouseout="this.style.borderColor='var(--border)'" onclick="showCommitteeDetail('${c.id}')"><div style="font-size:20px;margin-bottom:3px">${c.icon}</div><div style="font-size:11px;font-weight:700;color:var(--green-dark)">${c.name}</div><div style="font-size:10px;color:var(--text-muted);margin-top:2px">👥 ${mems} عضو</div></div></div>`; }).join('')}
      </div>
    </div>`;
}

// =================== BUDGET ===================
async function addTransaction(){
  clearValidation();
  if(!validateRequired('tx-desc','وصف المعاملة')) return;
  if(!validateRequired('tx-amount','المبلغ')) return;
  const desc=document.getElementById('tx-desc').value.trim(); const amount=parseFloat(document.getElementById('tx-amount').value);
  if(!amount||amount<=0){toast('المبلغ يجب أن يكون أكبر من صفر','error');document.getElementById('tx-amount').classList.add('invalid');return;}
  const data={
    type:      document.getElementById('tx-type').value,
    amount,
    category:  document.getElementById('tx-cat').value,
    committee: document.getElementById('tx-committee').value,
    desc,
    date:      document.getElementById('tx-date').value||today()
  };
  const btn=document.getElementById('btn-add-tx');
  setBtnLoading(btn,true);
  try{ await FinanceService.addTransaction(data); }finally{ setBtnLoading(btn,false); }
}

function renderBudget(page){
  document.getElementById('tx-committee').innerHTML='<option value="">عام</option>'+committeeSelectOptions();
  const flt=document.getElementById('b-flt').value;
  let txs=[...State.getTransactions()].sort((a,b)=>b.date.localeCompare(a.date));
  if(flt) txs=txs.filter(t=>t.type===flt);
  const income=State.getTransactions().filter(t=>t.type==='إيراد').reduce((s,t)=>s+t.amount,0);
  const expense=State.getTransactions().filter(t=>t.type==='مصروف').reduce((s,t)=>s+t.amount,0);
  document.getElementById('b-income').textContent=fmt(income);
  document.getElementById('b-expense').textContent=fmt(expense);
  document.getElementById('b-net').textContent=fmt(income-expense);
  const catClr={'رسوم الأعضاء':'#27ae60','رحلة العمرة':'#2980b9','غداء العيد':'#e67e22','رحلة ترفيهية':'#8e44ad','مسابقة':'#c8a84b','مصاريف إدارية':'#95a5a6','استثمار':'#1B3456','عقيقة جماعية':'#6b3a1a','تبرعات':'#c8a84b','أخرى':'#7f8c8d'};
  if(!txs.length){ document.getElementById('budget-tbody').innerHTML='<tr><td colspan="7"><div class="empty-state"><div class="empty-icon">💰</div><p>لا معاملات</p></div></td></tr>'; var bp=document.getElementById('budget-pagination');if(bp)bp.innerHTML=''; return; }
  var pg = paginate(txs, 'budget', page, 25);
  document.getElementById('budget-tbody').innerHTML=pg.data.map(tx=>{ const c=State.getCommittees().find(x=>x.id===tx.committee); return `<tr><td data-label="التاريخ" style="font-size:11px;color:var(--text-muted)">${tx.date}</td><td data-label="الوصف" style="font-weight:600">${tx.desc}</td><td data-label="الفئة"><span style="background:${(catClr[tx.category]||'#777')}22;color:${catClr[tx.category]||'#777'};padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600">${tx.category}</span></td><td data-label="اللجنة" style="font-size:11px">${c?c.name:'عام'}</td><td data-label="النوع"><span class="badge ${tx.type==='إيراد'?'badge-success':'badge-danger'}">${tx.type==='إيراد'?'⬆️':'⬇️'} ${tx.type}</span></td><td data-label="المبلغ" style="font-weight:700;color:${tx.type==='إيراد'?'var(--green)':'var(--danger)'}">${tx.type==='إيراد'?'+':'-'}${fmt(tx.amount)} ريال</td><td data-label="حذف"><button class="btn btn-danger btn-xs" onclick="deleteTx('${tx.id}')">🗑️</button></td></tr>`; }).join('');
  var paginEl=document.getElementById('budget-pagination');
  if(paginEl) paginEl.innerHTML = renderPaginationHTML(pg, 'renderBudget');
}

function deleteTx(id){ FinanceService.deleteTx(id); }

// =================== CALENDAR ===================
let calendarDate = new Date();

function renderCalendar(){
  const year = calendarDate.getFullYear();
  const month = calendarDate.getMonth();
  const monthNames = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
  document.getElementById('cal-month-year').textContent = `${monthNames[month]} ${year}`;
  
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const startDay = firstDay === 6 ? 0 : firstDay + 1; // adjust for Sunday start
  
  const eventsInMonth = State.getEvents().filter(e => {
    if(!e.date) return false;
    const d = new Date(e.date);
    return d.getMonth() === month && d.getFullYear() === year;
  });
  
  let html = '<table style="width:100%;border-collapse:collapse;text-align:center"><thead><tr>';
  ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'].forEach(day => {
    html += `<th style="padding:10px;background:var(--bg);border:1px solid var(--border);font-size:12px;font-weight:700">${day}</th>`;
  });
  html += '</tr></thead><tbody><tr>';
  
  for(let i = 0; i < startDay; i++) html += '<td style="padding:10px;border:1px solid var(--border);background:#fafafa"></td>';
  
  for(let day = 1; day <= daysInMonth; day++){
    if((startDay + day - 1) % 7 === 0 && day !== 1) html += '</tr><tr>';
    const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
    const dayEvents = eventsInMonth.filter(e => e.date === dateStr);
    const isToday = new Date().toDateString() === new Date(year, month, day).toDateString();
    const bgColor = isToday ? 'background:#dcfce7;' : dayEvents.length ? 'background:#f0fdf4;' : '';
    html += `<td style="padding:10px;border:1px solid var(--border);vertical-align:top;height:80px;position:relative;${bgColor}">
      <div style="font-weight:${isToday?'900':'600'};font-size:14px;color:${isToday?'var(--green)':'var(--text)'};margin-bottom:4px">${day}</div>
      ${dayEvents.map(e => {
        const c = State.getCommittees().find(x => x.id === e.committeeId);
        return `<div style="background:${c?c.color:'var(--green)'};color:#fff;font-size:9px;padding:2px 4px;border-radius:4px;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${e.name}">${e.icon||'🎉'} ${e.name}</div>`;
      }).join('')}
    </td>`;
  }
  
  const remainingCells = (7 - ((startDay + daysInMonth) % 7)) % 7;
  for(let i = 0; i < remainingCells; i++) html += '<td style="padding:10px;border:1px solid var(--border);background:#fafafa"></td>';
  html += '</tr></tbody></table>';
  
  document.getElementById('calendar-container').innerHTML = html;
  
  // Month events list
  document.getElementById('month-events-list').innerHTML = eventsInMonth.length ? eventsInMonth.map(e => {
    const c = State.getCommittees().find(x => x.id === e.committeeId);
    return `<div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f0f0f0"><div style="font-size:22px">${e.icon||'🎉'}</div><div style="flex:1"><div style="font-size:13px;font-weight:600">${e.name}</div><div style="font-size:11px;color:var(--text-muted)">${e.date} ${c?'• '+c.name:''}</div></div><span class="badge ${sBadge(e.status)}">${e.status}</span></div>`;
  }).join('') : '<div class="empty-state"><div class="empty-icon">📅</div><p>لا فعاليات هذا الشهر</p></div>';
  
  // Upcoming events
  const upcoming = State.getEvents().filter(e => e.date && new Date(e.date) > new Date()).sort((a,b) => a.date.localeCompare(b.date)).slice(0,5);
  document.getElementById('upcoming-events-list').innerHTML = upcoming.length ? upcoming.map(e => {
    const c = State.getCommittees().find(x => x.id === e.committeeId);
    const daysUntil = Math.ceil((new Date(e.date) - new Date()) / (1000*60*60*24));
    return `<div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f0f0f0"><div style="font-size:22px">${e.icon||'🎉'}</div><div style="flex:1"><div style="font-size:13px;font-weight:600">${e.name}</div><div style="font-size:11px;color:var(--text-muted)">${e.date} (بعد ${daysUntil} يوم)</div></div></div>`;
  }).join('') : '<div class="empty-state"><div class="empty-icon">🗓️</div><p>لا فعاليات قادمة</p></div>';
}

function changeMonth(delta){
  calendarDate.setMonth(calendarDate.getMonth() + delta);
  renderCalendar();
}

function resetCalendar(){
  calendarDate = new Date();
  renderCalendar();
}

// =================== EVENTS ===================
const evBg={'أخرى':'linear-gradient(135deg,#47915C,#2d6b40)'};
const evIcon={'رحلة عمرة':'🕋','غداء العيد':'🍖','رحلة ترفيهية':'🎡','اجتماع':'🤝','مسابقة':'🏆','عقيقة':'🐑','أخرى':'🎉'};

function openAddEvent(){
  document.getElementById('ev-edit-id').value='';
  document.getElementById('ev-committee').innerHTML='<option value="">غير محدد</option>'+committeeSelectOptions();
  ['ev-name','ev-budget','ev-participants','ev-lead','ev-notes'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('ev-date').value=today();
  document.getElementById('ev-status').value='قادم';
  document.getElementById('ev-images-preview').innerHTML='';
  document.getElementById('ev-images').value='';
  document.getElementById('event-modal-title').textContent='🎉 إضافة فعالية';
  document.getElementById('ev-delete-btn').style.display='none';
  openModal('modal-event');
}

function openEditEvent(id){
  const ev=State.getEvents().find(e=>e.id===id); if(!ev)return;
  document.getElementById('ev-edit-id').value=id;
  document.getElementById('ev-committee').innerHTML='<option value="">غير محدد</option>'+committeeSelectOptions();
  document.getElementById('ev-name').value=ev.name||'';
  document.getElementById('ev-committee').value=ev.committeeId||'';
  document.getElementById('ev-status').value=ev.status||'قادم';
  document.getElementById('ev-date').value=ev.date||'';
  document.getElementById('ev-budget').value=ev.budget||'';
  document.getElementById('ev-participants').value=ev.participants||'';
  document.getElementById('ev-lead').value=ev.lead||'';
  document.getElementById('ev-notes').value=ev.notes||'';
  document.getElementById('ev-images').value='';
  // Show existing images preview
  const preview=document.getElementById('ev-images-preview');
  const imgs=ev.images||[];
  preview.innerHTML=imgs.map(img=>{
    const src=typeof img==='string'?img:(img.data||'');
    return src?`<div style="width:60px;height:60px;border-radius:8px;overflow:hidden;border:2px solid var(--border)"><img src="${src}" style="width:100%;height:100%;object-fit:cover"></div>`:'';
  }).join('');
  document.getElementById('event-modal-title').textContent='🎉 تعديل الفعالية';
  document.getElementById('ev-delete-btn').style.display='';
  openModal('modal-event');
}

async function saveEvent(){
  const name=document.getElementById('ev-name').value.trim(); if(!name){toast('الاسم مطلوب','error');return;}
  const editId=document.getElementById('ev-edit-id').value;
  const data={
    name,
    committeeId:document.getElementById('ev-committee').value,
    status:document.getElementById('ev-status').value,
    date:document.getElementById('ev-date').value||'',
    budget:parseFloat(document.getElementById('ev-budget').value)||0,
    participants:parseInt(document.getElementById('ev-participants').value)||0,
    lead:document.getElementById('ev-lead').value,
    notes:document.getElementById('ev-notes').value,
    icon:'🎉',
    images:[]
  };

  // Read new image files if any
  const imgFiles=document.getElementById('ev-images').files;
  if(imgFiles.length){
    for(let file of imgFiles){
      if(file.size > 2*1024*1024){toast('حجم الصورة أكبر من 2MB','error');continue;}
      const reader = new FileReader();
      await new Promise((resolve) => {
        reader.onload = (e) => { data.images.push({name:file.name, data:e.target.result}); resolve(); };
        reader.readAsDataURL(file);
      });
    }
  } else if(editId) {
    // Keep existing images when not uploading new ones
    const existing=State.getEvents().find(e=>e.id===editId);
    if(existing) data.images=existing.images||[];
  }

  const btn=document.getElementById('ev-save-btn');
  setBtnLoading(btn,true);
  try {
    if(editId){
      await EventService.update(editId, data);
      log(`تعديل فعالية: ${name}`,'✏️');
    } else {
      await EventService.create(data);
      log(`فعالية جديدة: ${name}`,'🎉');
    }
    closeModalSilent('modal-event');
  } catch(e){ toast('حدث خطأ: '+e.message,'error'); } finally { setBtnLoading(btn,false); }
}

function deleteEventFromModal(){
  const id=document.getElementById('ev-edit-id').value; if(!id)return;
  EventService.delete(id);
  closeModalSilent('modal-event');
}

// Preview images on selection
document.addEventListener('DOMContentLoaded', () => {
  const imgInput = document.getElementById('ev-images');
  if(imgInput){
    imgInput.addEventListener('change', (e) => {
      const preview = document.getElementById('ev-images-preview');
      preview.innerHTML = '';
      for(let file of e.target.files){
        const reader = new FileReader();
        reader.onload = (ev) => {
          preview.innerHTML += `<div style="width:60px;height:60px;border-radius:8px;overflow:hidden;border:2px solid var(--border)"><img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover"></div>`;
        };
        reader.readAsDataURL(file);
      }
    });
  }
});

function renderEvents(){
  const el=document.getElementById('events-grid');
  if(!State.getEvents().length){el.innerHTML='<div style="grid-column:1/-1"><div class="empty-state"><div class="empty-icon">🗓️</div><p>لا فعاليات</p></div></div>';return;}
  el.innerHTML=State.getEvents().map(ev=>{
    const c=State.getCommittees().find(x=>x.id===ev.committeeId);
    const imgs=(ev.images||[]).slice(0,3);
    const imgSrc=img=>typeof img==='string'?img:(img.data||'');
    return `<div class="committee-card" style="cursor:pointer" onclick="openEditEvent('${ev.id}')">
    <div class="committee-banner" style="background:${c?c.color:evBg['أخرى']};position:relative">
      ${imgs.length?`<div style="position:absolute;inset:0;display:flex;gap:2px">${imgs.map(img=>`<div style="flex:1;background:url('${imgSrc(img)}') center/cover"></div>`).join('')}</div>`:`${ev.icon||'🎉'}`}
      <div style="position:absolute;top:8px;right:8px"><span class="badge ${sBadge(ev.status)}">${ev.status}</span></div>
      ${imgs.length?`<div style="position:absolute;bottom:8px;left:8px;background:rgba(0,0,0,.6);color:#fff;padding:3px 8px;border-radius:12px;font-size:18px">${ev.icon||'🎉'}</div>`:''}
    </div>
    <div class="committee-body"><div class="committee-title">${ev.name}</div><div class="committee-meta">${c?'🏛️ '+c.name:''}${ev.date?' • 📅 '+ev.date:''}</div>${ev.lead?`<div style="font-size:11px;color:var(--text-muted);margin-top:2px">👤 ${ev.lead}</div>`:''}${imgs.length?`<div style="font-size:10px;color:var(--text-muted);margin-top:4px">📸 ${imgs.length} صورة</div>`:''}</div>
    <div class="committee-footer"><span style="font-size:11px;color:var(--text-muted)">${ev.budget?'💰 '+fmt(ev.budget)+' ريال':''} ${ev.participants?'👥 '+ev.participants:''}</span><div style="display:flex;gap:4px"><button class="btn btn-outline btn-xs" onclick="event.stopPropagation();openEditEvent('${ev.id}')">✏️</button><button class="btn btn-danger btn-xs" onclick="event.stopPropagation();deleteEvent('${ev.id}')">🗑️</button></div></div>
  </div>`; }).join('');
}
function deleteEvent(id){
  EventService.delete(id);
}

// =================== FAMILY TREE (شجرة العائلة الهرمية) ===================

// فتح modal إضافة شخص جديد
function openAddTreeMember(parentId){
  document.getElementById('ftm-id').value = '';
  document.getElementById('ftm-name').value = '';
  document.querySelector('input[name="ftm-gender"][value="ذكر"]').checked = true;
  document.getElementById('ftm-alive').checked = true;
  document.getElementById('ftm-spouse').value = '';
  document.getElementById('ftm-sort').value = '0';
  document.getElementById('tree-member-modal-title').textContent = '🌳 إضافة شخص للشجرة';
  document.getElementById('ftm-delete-btn').style.display = 'none';
  populateParentSelect(parentId || '');
  openModal('modal-tree-member');
}

// فتح modal تعديل شخص
function editTreeMember(id){
  const m = getFamilyTreeMembers().find(x => x.id === id);
  if(!m) return;
  document.getElementById('ftm-id').value = m.id;
  document.getElementById('ftm-name').value = m.name;
  const genderRadio = document.querySelector(`input[name="ftm-gender"][value="${m.gender || 'ذكر'}"]`);
  if(genderRadio) genderRadio.checked = true;
  document.getElementById('ftm-alive').checked = m.is_alive == 1;
  document.getElementById('ftm-spouse').value = m.spouse_name || '';
  document.getElementById('ftm-sort').value = m.sort_order || 0;
  document.getElementById('tree-member-modal-title').textContent = '✏️ تعديل: ' + m.name;
  document.getElementById('ftm-delete-btn').style.display = 'inline-flex';
  populateParentSelect(m.parent_id || '', m.id);
  openModal('modal-tree-member');
}

// بناء قائمة الآباء
function populateParentSelect(selectedId, excludeId){
  const select = document.getElementById('ftm-parent');
  const all = getFamilyTreeMembers();
  select.innerHTML = '<option value="">-- بدون (جذر الشجرة) --</option>';
  all.forEach(function(m){
    if(m.id === excludeId) return;
    const sel = m.id === selectedId ? ' selected' : '';
    select.innerHTML += `<option value="${m.id}"${sel}>${m.name}</option>`;
  });
}

// حفظ (إضافة أو تعديل)
function saveTreeMember(){
  const name = document.getElementById('ftm-name').value.trim();
  if(!name){ toast('الاسم مطلوب','error'); return; }
  const id = document.getElementById('ftm-id').value;
  const gender = document.querySelector('input[name="ftm-gender"]:checked').value;
  const data = {
    name,
    parent_id:   document.getElementById('ftm-parent').value || null,
    gender,
    is_alive:    document.getElementById('ftm-alive').checked ? 1 : 0,
    spouse_name: document.getElementById('ftm-spouse').value.trim(),
    sort_order:  parseInt(document.getElementById('ftm-sort').value) || 0,
  };
  if(id){
    FamilyTreeService.update(id, data);
  }else{
    FamilyTreeService.create(data);
  }
}

// حذف شخص
function deleteTreeMemberConfirm(){
  const id = document.getElementById('ftm-id').value;
  const m = getFamilyTreeMembers().find(x => x.id === id);
  if(!m) return;
  confirm2(`حذف "${m.name}" من الشجرة؟`, async ()=>{
    try{
      await AdminFamilyTree.delete(id);
      closeModalSilent('modal-tree-member');
      toast('تم الحذف');
      renderFamilyTreeList();
      renderTreePreview();
    }catch(e){ toast('فشل: '+e.message,'error'); }
  });
}

// عرض قائمة الأعضاء
function renderFamilyTreeList(){
  const el = document.getElementById('ftm-list');
  const countEl = document.getElementById('ftm-count');
  if(!el) return;

  const q = (document.getElementById('ftm-search')?.value || '').trim().toLowerCase();
  let all = getFamilyTreeMembers();
  if(q) all = all.filter(m => m.name.toLowerCase().includes(q) || (m.spouse_name||'').toLowerCase().includes(q));

  countEl.textContent = all.length + ' شخص';

  if(!all.length){
    el.innerHTML = '<div class="empty-state"><div class="empty-icon">🌳</div><p>لا أشخاص في الشجرة بعد</p></div>';
    return;
  }

  // بناء map للأب
  const nameMap = {};
  getFamilyTreeMembers().forEach(m => { nameMap[m.id] = m.name; });

  el.innerHTML = `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px">
    ${all.map(m => {
      const gIcon = m.gender === 'أنثى' ? '👩' : '👨';
      const alive = m.is_alive == 1;
      const bg = m.gender === 'أنثى' ? '#f5f0ff' : '#f0fdf4';
      const border = m.gender === 'أنثى' ? '#d8b4fe' : '#86efac';
      const parentName = m.parent_id ? nameMap[m.parent_id] || '' : '';
      return `<div style="background:${bg};border:1.5px solid ${border};border-radius:10px;padding:10px 12px;cursor:pointer;transition:transform .15s;${!alive?'opacity:.6;':''}" onclick="editTreeMember('${m.id}')" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
          <span style="font-size:16px">${gIcon}</span>
          <span style="font-weight:700;font-size:13px;flex:1">${m.name}</span>
          ${!alive?'<span style="font-size:10px;color:#888">متوفى</span>':''}
        </div>
        ${parentName ? `<div style="font-size:11px;color:var(--text-muted)">👤 ${parentName}</div>` : '<div style="font-size:11px;color:var(--green);font-weight:600">🌳 جذر</div>'}
        ${m.spouse_name ? `<div style="font-size:11px;color:var(--text-muted)">💑 ${m.spouse_name}</div>` : ''}
      </div>`;
    }).join('')}
  </div>`;
}

// معاينة شجرية بسيطة (HTML)
function renderTreePreview(){
  const container = document.getElementById('tree-container');
  if(!container) return;

  const all = getFamilyTreeMembers();
  if(!all.length){
    container.innerHTML = '<div class="empty-state"><div class="empty-icon">🌳</div><p>لا أشخاص في الشجرة بعد</p></div>';
    return;
  }

  // بناء شجرة
  const map = {};
  all.forEach(m => { map[m.id] = Object.assign({}, m, { kids: [] }); });
  const roots = [];
  all.forEach(m => {
    if(m.parent_id && map[m.parent_id]) map[m.parent_id].kids.push(map[m.id]);
    else roots.push(map[m.id]);
  });

  function renderNode(n, depth){
    const gColor = n.gender === 'أنثى' ? '#8e44ad' : '#1a6b3c';
    const alive = n.is_alive == 1;
    const childCount = n.kids.length;
    let html = `<div style="margin-right:${depth*24}px;margin-bottom:4px;display:flex;align-items:center;gap:6px;${!alive?'opacity:.55':''}">`;
    if(depth > 0) html += `<span style="color:var(--border)">├─</span>`;
    html += `<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${gColor};flex-shrink:0"></span>`;
    html += `<span style="font-size:12px;font-weight:600;cursor:pointer" onclick="editTreeMember('${n.id}')">${n.name}</span>`;
    if(n.spouse_name) html += `<span style="font-size:10px;color:var(--text-muted)">💑 ${n.spouse_name}</span>`;
    if(childCount) html += `<span style="font-size:10px;color:var(--text-muted)">(${childCount})</span>`;
    html += '</div>';
    n.kids.sort((a,b) => (a.sort_order||0)-(b.sort_order||0));
    n.kids.forEach(k => { html += renderNode(k, depth+1); });
    return html;
  }

  let preview = '<div style="font-family:monospace;direction:ltr;text-align:left">';
  roots.sort((a,b) => (a.sort_order||0)-(b.sort_order||0));
  roots.forEach(r => { preview += renderNode(r, 0); });
  preview += '</div>';
  container.innerHTML = preview;
}

// أزرار التحكم بالمعاينة
var _treePreviewExpanded = true;
function treePreviewExpandAll(){ _treePreviewExpanded=true; renderTreePreview(); }
function treePreviewCollapseAll(){ _treePreviewExpanded=false; renderTreePreview(); }

// تصدير JSON
function exportTreeJSON(){
  const dataStr = JSON.stringify({familyTree: getFamilyTreeMembers()}, null, 2);
  const dataBlob = new Blob([dataStr], {type:'application/json'});
  const url = URL.createObjectURL(dataBlob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `awami-family-tree-${new Date().toISOString().split('T')[0]}.json`;
  a.click();
  URL.revokeObjectURL(url);
  toast('تم تصدير الشجرة 🌳');
}

// ── الأفرع القديمة (backward compat) ──
function openAddBranch(){
  document.getElementById('branch-id').value = '';
  document.getElementById('branch-name').value = '';
  document.getElementById('branch-head').value = '';
  document.getElementById('branch-color').value = '#47915C';
  document.getElementById('branch-members').value = '';
  document.getElementById('branch-notes').value = '';
  document.getElementById('branch-modal-title').textContent = '🌳 إضافة فرع عائلي جديد';
  document.getElementById('branch-delete-btn').style.display = 'none';
  openModal('modal-add-branch');
}

function renderFamilyTree(){
  // عرض الأفرع القديمة
  const container = document.getElementById('branches-list');
  if(!container) return;

  if(!State.getFamilyBranches().length){
    container.innerHTML = '<div class="empty-state"><div class="empty-icon">🌿</div><p>لا أفرع</p></div>';
    return;
  }
  container.innerHTML = `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px">
    ${State.getFamilyBranches().map(b=>`
      <div style="border:2px solid ${b.color||'#47915C'};border-radius:10px;padding:12px;cursor:pointer" onclick="editBranch('${b.id}')">
        <div style="font-weight:700;font-size:13px;color:var(--green-dark)">${b.name}</div>
        <div style="font-size:11px;color:var(--text-muted)">${(b.members||[]).length} فرد</div>
      </div>`).join('')}
  </div>`;

  // عرض شجرة الأعضاء الجديدة
  renderFamilyTreeList();
  renderTreePreview();
}

function editBranch(id){
  const b = State.getFamilyBranches().find(x => x.id === id);
  if(!b) return;
  document.getElementById('branch-id').value = id;
  document.getElementById('branch-name').value = b.name;
  document.getElementById('branch-head').value = b.head || '';
  document.getElementById('branch-color').value = b.color || '#47915C';
  document.getElementById('branch-members').value = (b.members || []).join('\n');
  document.getElementById('branch-notes').value = b.notes || '';
  document.getElementById('branch-modal-title').textContent = '✏️ تعديل: ' + b.name;
  document.getElementById('branch-delete-btn').style.display = 'inline-flex';
  openModal('modal-add-branch');
}

function showBranchMembers(bid){
  const b = State.getFamilyBranches().find(x => x.id === bid);
  if(!b) return;
  const members = b.members || [];
  document.getElementById('branch-detail-title').textContent = b.name;
  document.getElementById('branch-detail-body').innerHTML = `
    <div style="background:${b.color||'#47915C'};color:#fff;border-radius:12px;padding:18px;margin-bottom:16px">
      <div style="font-size:18px;font-weight:700">${b.name}</div>
      <div style="font-size:13px;opacity:.9;margin-top:4px">عدد الأفراد: ${b.count || members.length}</div>
    </div>
    ${members.length ? `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px">${members.map(m=>`<div style="padding:8px;border:1px solid var(--border);border-radius:8px;font-size:13px">• ${m}</div>`).join('')}</div>` : '<div style="text-align:center;padding:20px;color:var(--text-muted)">لا أفراد</div>'}`;
  openModal('modal-branch-detail');
}

// =================== VOTING ===================
function createPoll(){
  const title=document.getElementById('poll-title').value.trim(); const optsRaw=document.getElementById('poll-options').value.trim();
  if(!title||!optsRaw){toast('العنوان والخيارات مطلوبة','error');return;}
  const options=optsRaw.split('\n').map(o=>o.trim()).filter(Boolean); if(options.length<2){toast('خيارين على الأقل','error');return;}
  const data={title,options,committee:document.getElementById('poll-committee').value,end:document.getElementById('poll-end').value||''};
  PollService.createPoll(data);
}

function vote(pollId,optIdx){ PollService.vote(pollId, optIdx); }

function renderVoting(){
  document.getElementById('poll-committee').innerHTML='<option value="">عام</option>'+committeeSelectOptions();
  const el=document.getElementById('polls-list');
  if(!State.getPolls().length){el.innerHTML='<div class="empty-state"><div class="empty-icon">🗳️</div><p>لا تصويتات. أنشئ أول تصويت</p></div>';return;}
  const uid2='user_default';
  el.innerHTML=[...State.getPolls()].reverse().map(poll=>{
    const total=poll.options.reduce((s,o)=>s+o.votes.length,0); const uv=poll.options.findIndex(o=>o.votes.includes(uid2));
    const c=State.getCommittees().find(x=>x.id===poll.committee);
    return `<div class="card" style="margin-bottom:14px">
      <div class="card-header"><div><div class="card-title">${poll.title}</div><div style="font-size:11px;color:var(--text-muted)">${c?'🏛️ '+c.name:'عام'} • ${total} صوت</div></div>
      <div style="display:flex;gap:6px"><span class="badge ${poll.active?'badge-success':'badge-gray'}">${poll.active?'نشط':'مغلق'}</span>${poll.active?`<button class="btn btn-outline btn-xs" onclick="closePollF('${poll.id}')">إغلاق</button>`:''}<button class="btn btn-danger btn-xs" onclick="deletePollF('${poll.id}')">🗑️</button></div></div>
      <div class="card-body">${poll.options.map((opt,i)=>{ const pct=total>0?Math.round(opt.votes.length/total*100):0; const iv=i===uv; return `<div style="border:2px solid ${iv?'var(--green)':'var(--border)'};border-radius:10px;padding:12px;cursor:pointer;margin-bottom:8px;background:${iv?'#f0fdf4':'#fff'};transition:all .2s" onclick="${poll.active?`vote('${poll.id}',${i})`:''}"><div style="display:flex;justify-content:space-between;margin-bottom:5px"><span style="font-size:13px;font-weight:${iv?700:500}">${iv?'✅ ':''} ${opt.text}</span><span style="font-size:12px;color:var(--text-muted)">${opt.votes.length} (${pct}%)</span></div><div style="height:5px;background:#e4ede6;border-radius:100px;overflow:hidden"><div style="height:100%;width:${pct}%;background:linear-gradient(90deg,var(--green),var(--green-light));border-radius:100px"></div></div></div>`; }).join('')}</div>
    </div>`;
  }).join('');
}
function closePollF(id){ PollService.closePoll(id); }
function deletePollF(id){ PollService.deletePoll(id); }

// =================== PORTAL ===================
function renderPortalSelect(){
  document.getElementById('portal-select').innerHTML='<option value="">-- اختر العضو --</option>'+State.getMembers().map(m=>`<option value="${m.id}">${m.name}</option>`).join('');
}

function loadPortal(){
  const id=document.getElementById('portal-select').value; const el=document.getElementById('portal-content'); if(!id){el.innerHTML='';return;}
  const m=State.getMembers().find(x=>x.id===id); if(!m){el.innerHTML='';return;}
  const coms=State.getCommittees().filter(c=>(State.getCommitteeMembers()[c.id]||[]).includes(id));
  const allPays=State.getPayments().filter(p=>p.memberId===id); const paid=allPays.filter(p=>p.status==='مدفوع'); const totalPaid=paid.reduce((s,p)=>s+p.amount,0);
  const curP=curPeriod(); const curPay=curP?State.getPayments().find(p=>p.memberId===id&&p.periodId===curP.id):null;
  el.innerHTML=`
    <div style="background:linear-gradient(135deg,var(--green-dark),var(--primary));border-radius:var(--radius);padding:22px;color:#fff;margin-bottom:14px">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
        <div class="avatar" style="background:${avColor(m.name)};width:52px;height:52px;font-size:18px">${avInit(m.name)}</div>
        <div><div style="font-size:20px;font-weight:800">${m.name}</div><div style="opacity:.7;font-size:12px">عضو منذ ${m.joinDate||'—'} • ${m.family}</div></div>
        <div style="margin-right:auto"><span class="badge ${m.status==='مشترك'?'badge-success':m.status==='منقطع'?'badge-warning':'badge-gray'}" style="font-size:12px">${m.status}</span></div>
      </div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;text-align:center">
        <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:8px"><div style="font-size:16px;font-weight:700">${fmt(totalPaid)}</div><div style="font-size:10px;opacity:.7">ريال مدفوع</div></div>
        <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:8px"><div style="font-size:16px;font-weight:700">${paid.length}</div><div style="font-size:10px;opacity:.7">دورات مدفوعة</div></div>
        <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:8px"><div style="font-size:16px;font-weight:700">${coms.length}</div><div style="font-size:10px;opacity:.7">لجنة</div></div>
      </div>
    </div>
    ${curP?`<div class="card" style="margin-bottom:12px"><div class="card-header"><div class="card-title">الدورة الحالية: ${curP.name}</div></div><div class="card-body"><div style="display:flex;align-items:center;justify-content:space-between"><div><div style="font-size:13px;color:var(--text-muted)">المطلوب: ${fmt(curP.feeAmount)} ريال</div><div style="font-size:13px">المدفوع: ${curPay?.status==='مدفوع'?fmt(curPay.amount)+' ريال':'لم يُسدَّد'}</div></div><span class="badge ${curPay?.status==='مدفوع'?'badge-success':'badge-warning'}" style="font-size:14px">${curPay?.status||'لم يدفع'}</span></div></div></div>`:''}
    ${coms.length?`<div class="card" style="margin-bottom:12px"><div class="card-header"><div class="card-title">🏛️ اللجان</div></div><div class="card-body"><div style="display:flex;flex-wrap:wrap;gap:6px">${coms.map(c=>`<span style="background:${c.color};color:#fff;padding:5px 12px;border-radius:20px;font-size:12px">${c.icon} ${c.name}</span>`).join('')}</div></div></div>`:''}
    <div class="card"><div class="card-header"><div class="card-title">سجل المدفوعات</div></div><div class="table-wrap"><table><thead><tr><th>الدورة</th><th>المطلوب</th><th>المدفوع</th><th>التاريخ</th><th>الحالة</th></tr></thead><tbody>${allPays.map(pay=>{ const p=State.getPeriods().find(x=>x.id===pay.periodId); return `<tr><td>${p?.name||'—'}</td><td>${fmt(pay.required||0)} ريال</td><td style="font-weight:700">${pay.status==='مدفوع'?fmt(pay.amount)+' ريال':'—'}</td><td style="font-size:11px">${pay.date||'—'}</td><td><span class="badge ${pay.status==='مدفوع'?'badge-success':'badge-warning'}">${pay.status}</span></td></tr>`; }).join('')}</tbody></table></div></div>
  `;
}

// =================== REMINDERS ===================
let _remHistoryPage = 1;

async function renderReminders() {
  const p = curPeriod();
  document.getElementById('rem-period-lbl').textContent = p ? p.name : 'لا توجد دورة';
  if (!p) {
    document.getElementById('rem-stats').innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:16px">أنشئ دورة أولاً</div>';
    document.getElementById('rem-list').innerHTML = '';
    return;
  }

  try {
    const res = await RemindersAPI.getUnpaid(p.id);
    const unpaid = res.data || [];
    const total = res.total || 0;

    // Stats
    const withPhone = unpaid.filter(m => m.phone && m.phone.trim());
    const alreadyReminded = unpaid.filter(m => m.reminder_count > 0);
    document.getElementById('rem-stats').innerHTML = `
      <div style="background:#fee2e2;border-radius:10px;padding:12px;text-align:center"><div style="font-size:20px;font-weight:900;color:#991b1b">${total}</div><div style="font-size:11px;color:#991b1b">لم يدفعوا</div></div>
      <div style="background:#dbeafe;border-radius:10px;padding:12px;text-align:center"><div style="font-size:20px;font-weight:900;color:#1e40af">${withPhone.length}</div><div style="font-size:11px;color:#1e40af">لديهم رقم جوال</div></div>
      <div style="background:#fef9c3;border-radius:10px;padding:12px;text-align:center"><div style="font-size:20px;font-weight:900;color:#854d0e">${alreadyReminded.length}</div><div style="font-size:11px;color:#854d0e">تم تذكيرهم سابقاً</div></div>`;

    // Member list
    if (!unpaid.length) {
      document.getElementById('rem-list').innerHTML = '<div class="empty-state"><div class="empty-icon">🎉</div><p>جميع الأعضاء دفعوا!</p></div>';
    } else {
      document.getElementById('rem-list').innerHTML = unpaid.map(m => {
        var phoneDigits = m.phone ? m.phone.replace(/\D/g, '').replace(/^0+/, '') : '';
        const phone = phoneDigits && !/^966/.test(phoneDigits) ? '966' + phoneDigits : phoneDigits;
        const msg = encodeURIComponent(`السلام عليكم ${m.name} 👋\n\nنذكركم بسداد رسوم مجلس عائلة العوامي للدورة "${p.name}"\nالمبلغ المطلوب: ${fmt(m.required)} ريال\n\nشكراً لكم 🙏`);
        const lastRem = m.last_reminder ? new Date(m.last_reminder).toLocaleDateString('ar-SA', {month:'short',day:'numeric'}) : null;
        return `<div style="display:flex;align-items:center;gap:10px;padding:10px;border-bottom:1px solid #f0ebe0">
          <div class="avatar" style="background:${avColor(m.name)}">${avInit(m.name)}</div>
          <div style="flex:1">
            <div style="font-weight:600">${m.name}</div>
            <div style="font-size:11px;color:var(--text-muted)">${m.phone || 'لا يوجد رقم'} ${m.family ? '• ' + m.family : ''}</div>
            ${m.reminder_count > 0 ? `<div style="font-size:10px;color:#854d0e;margin-top:2px">🔔 ذُكِّر ${m.reminder_count} مرة ${lastRem ? '(آخر: ' + lastRem + ')' : ''}</div>` : ''}
          </div>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            ${phone ? `<a href="https://wa.me/${phone}?text=${msg}" target="_blank" class="btn btn-whatsapp btn-xs" onclick="logReminder('${m.id}','${p.id}','whatsapp')">📱 واتساب</a>` : '<span class="badge badge-gray" style="font-size:10px">بدون رقم</span>'}
            ${phone ? `<button class="btn btn-outline btn-xs" onclick="logReminder('${m.id}','${p.id}','sms')">💬 SMS</button>` : ''}
          </div>
        </div>`;
      }).join('');
    }

    // Load history
    loadReminderHistory(p.id);
  } catch (e) {
    console.error('Reminders error:', e);
    document.getElementById('rem-list').innerHTML = '<div style="text-align:center;padding:20px;color:var(--danger)">فشل تحميل البيانات</div>';
  }
}

async function logReminder(memberId, periodId, channel) {
  try {
    await RemindersAPI.logSingle(memberId, periodId, channel);
    toast('تم تسجيل التذكير ✅');
    // Don't re-render immediately to not interrupt the user
  } catch (e) {
    console.error('Log reminder error:', e);
  }
}

async function sendBulkReminder(channel) {
  const p = curPeriod();
  if (!p) { toast('لا توجد دورة مفعّلة', 'error'); return; }

  if (channel === 'whatsapp') {
    // Open the WhatsApp modal with all unpaid members
    sendWhatsappReminders();
    // Log bulk
    try { await RemindersAPI.logBulk(p.id, 'whatsapp'); } catch(e) {}
  } else {
    toast('تم تسجيل التذكير الجماعي عبر SMS ✅');
    try { await RemindersAPI.logBulk(p.id, 'sms'); } catch(e) {}
  }
  // Reload after a delay
  setTimeout(() => renderReminders(), 1000);
}

async function loadReminderHistory(periodId) {
  try {
    const res = await RemindersAPI.getHistory({ period_id: periodId, page: _remHistoryPage, limit: 20 });
    const rows = res.data || [];
    const tbody = document.getElementById('rem-history-tbody');

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted)">لا توجد تذكيرات مرسلة بعد</td></tr>';
    } else {
      tbody.innerHTML = rows.map(r => {
        const dt = r.sent_at ? new Date(r.sent_at).toLocaleDateString('ar-SA', {year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'}) : '';
        const chIcon = r.channel === 'whatsapp' ? '📱' : r.channel === 'sms' ? '💬' : '📝';
        // Get member name from local cache
        const m = State.getMembers().find(x => x.id === r.member_id);
        return `<tr><td style="font-size:11px">${dt}</td><td>${m ? m.name : r.member_id}</td><td><span class="badge badge-info">${chIcon} ${r.channel}</span></td><td style="font-size:11px">${r.sent_by || 'admin'}</td></tr>`;
      }).join('');
    }

    // Pagination
    const pag = document.getElementById('rem-history-pagination');
    const pages = res.pages || 1;
    if (pages <= 1) { pag.innerHTML = ''; return; }
    let html = '';
    if (_remHistoryPage > 1) html += `<button class="btn btn-outline btn-xs" onclick="_remHistoryPage=${_remHistoryPage-1};loadReminderHistory('${periodId}')">❮</button>`;
    for (let i = Math.max(1, _remHistoryPage-2); i <= Math.min(pages, _remHistoryPage+2); i++) {
      html += `<button class="btn ${i===_remHistoryPage?'btn-primary':'btn-outline'} btn-xs" onclick="_remHistoryPage=${i};loadReminderHistory('${periodId}')">${i}</button>`;
    }
    if (_remHistoryPage < pages) html += `<button class="btn btn-outline btn-xs" onclick="_remHistoryPage=${_remHistoryPage+1};loadReminderHistory('${periodId}')">❯</button>`;
    pag.innerHTML = html;
  } catch(e) {
    console.error('Reminder history error:', e);
  }
}

// =================== COMMITTEE CRUD ===================
function openAddCommitteeModal() {
  document.getElementById('committee-modal-title').textContent = '🏛️ إضافة لجنة';
  document.getElementById('cm-id').value = '';
  document.getElementById('cm-name').value = '';
  document.getElementById('cm-icon').value = '🏛️';
  document.getElementById('cm-color1').value = '#47915C';
  document.getElementById('cm-color2').value = '#2d6b40';
  document.getElementById('cm-desc').value = '';
  document.getElementById('cm-members-count').value = '';
  document.getElementById('cm-advisory').checked = false;
  document.getElementById('cm-delete-btn').style.display = 'none';
  openModal('modal-add-committee');
}

function openEditCommitteeModal(cid) {
  const c = State.getCommittees().find(x => x.id === cid);
  if (!c) return;
  document.getElementById('committee-modal-title').textContent = '✏️ تعديل اللجنة';
  document.getElementById('cm-id').value = c.id;
  document.getElementById('cm-name').value = c.name;
  document.getElementById('cm-icon').value = c.icon || '🏛️';

  // Parse color gradient
  const colorMatch = (c.color || '').match(/#[0-9a-fA-F]{6}/g);
  document.getElementById('cm-color1').value = colorMatch ? colorMatch[0] : '#47915C';
  document.getElementById('cm-color2').value = colorMatch && colorMatch[1] ? colorMatch[1] : '#2d6b40';

  document.getElementById('cm-desc').value = c.desc || '';
  document.getElementById('cm-members-count').value = c.members_count || '';
  document.getElementById('cm-advisory').checked = Boolean(c.advisory);
  document.getElementById('cm-delete-btn').style.display = 'inline-block';
  openModal('modal-add-committee');
}

async function saveCommittee() {
  const id = document.getElementById('cm-id').value;
  const name = document.getElementById('cm-name').value.trim();
  if (!name) { toast('اسم اللجنة مطلوب', 'error'); return; }

  const c1 = document.getElementById('cm-color1').value;
  const c2 = document.getElementById('cm-color2').value;
  const data = {
    name,
    icon: document.getElementById('cm-icon').value || '🏛️',
    color: `linear-gradient(135deg,${c1},${c2})`,
    description: document.getElementById('cm-desc').value,
    members_count: parseInt(document.getElementById('cm-members-count').value) || 0,
    advisory: document.getElementById('cm-advisory').checked,
  };

  const btn=document.getElementById('btn-save-committee');
  setBtnLoading(btn,true);
  try {
    if (id) {
      await CommitteeService.update(id, data);
    } else {
      await CommitteeService.create(data);
    }
  } finally { setBtnLoading(btn,false); }
}

function deleteCommitteeFromModal() {
  const id = document.getElementById('cm-id').value;
  if (!id) return;
  closeModalSilent('modal-add-committee');
  CommitteeService.delete(id);
}

// =================== WHATSAPP ===================
function sendWhatsappReminders(){
  const p=curPeriod(); if(!p){toast('لا توجد دورة مفعّلة','error');return;}
  const unpaid=State.getPayments().filter(x=>x.periodId===p.id&&x.status==='لم يدفع');
  if(!unpaid.length){toast('جميع الأعضاء دفعوا! 🎉');return;}
  const list=unpaid.map(pay=>State.getMembers().find(x=>x.id===pay.memberId)).filter(Boolean);
  document.getElementById('whatsapp-list').innerHTML=list.map(m=>{ var pd=m.phone?m.phone.replace(/\D/g,'').replace(/^0+/,''):''; const phone=pd&&!/^966/.test(pd)?'966'+pd:pd; const msg=encodeURIComponent(`السلام عليكم ${m.name} 👋\n\nنذكركم بسداد رسوم مجلس عائلة العوامي للدورة "${p.name}"\nالمبلغ المطلوب: ${fmt(p.feeAmount)} ريال\n\nشكراً لكم 🙏`); return `<div style="display:flex;align-items:center;gap:10px;padding:10px;border-bottom:1px solid #f0ebe0"><div class="avatar" style="background:${avColor(m.name)}">${avInit(m.name)}</div><div style="flex:1"><div style="font-weight:600">${m.name}</div><div style="font-size:11px;color:var(--text-muted)">${m.phone||'لا يوجد رقم'}</div></div>${phone?`<a href="https://wa.me/${phone}?text=${msg}" target="_blank" class="btn btn-whatsapp btn-sm">📱 إرسال</a>`:'<span class="badge badge-gray">بدون رقم</span>'}</div>`; }).join('');
  openModal('modal-whatsapp');
}

// =================== EXPORT ===================
function exportToCSV(headers,rows,filename){ const bom='\uFEFF'; const csv=bom+[headers.join(','),...rows.map(r=>r.map(c=>`"${String(c).replace(/"/g,'""')}"`).join(','))].join('\n'); const a=document.createElement('a'); a.href='data:text/csv;charset=utf-8,'+encodeURIComponent(csv); a.download=filename+'.csv'; a.click(); }
function exportMembersExcel(){ exportToCSV(['الاسم','الجوال','رقم الهوية','الفرع','تاريخ الانضمام','الحالة'],State.getMembers().map(m=>[m.name,m.phone||'',m.idNum||'',m.family,m.joinDate||'',m.status]),'أعضاء_عائلة_العوامي'); toast('تم تصدير الملف 📊'); }
function exportFeesExcel(){ const p=curPeriod(); if(!p){toast('لا توجد دورة','error');return;} const pays=State.getPayments().filter(x=>x.periodId===p.id); exportToCSV(['الاسم','المطلوب','المدفوع','التاريخ','الطريقة','الحالة'],pays.map(pay=>{ const m=State.getMembers().find(x=>x.id===pay.memberId)||{}; return [m.name||'',pay.required||p.feeAmount,pay.amount||0,pay.date||'',pay.method||'',pay.status]; }),`رسوم_${p.name}`); toast('تم التصدير 📊'); }
function exportBudgetExcel(){ exportToCSV(['التاريخ','الوصف','الفئة','اللجنة','النوع','المبلغ'],State.getTransactions().map(t=>{ const c=State.getCommittees().find(x=>x.id===t.committee); return [t.date,t.desc,t.category,c?c.name:'عام',t.type,t.amount]; }),'المعاملات_المالية'); toast('تم التصدير 📊'); }

// =================== SERVER-SIDE EXPORT (CSV) ===================
function downloadExport(type, extraParams) {
  const params = {};
  if (extraParams) {
    Object.keys(extraParams).forEach(k => {
      if (extraParams[k]) params[k] = extraParams[k];
    });
  }
  const url = ExportAPI.url(type, params);
  const a = document.createElement('a');
  a.href = url;
  a.download = '';
  a.click();
  toast('جاري تحميل التصدير... 📥');
}

// =================== AUDIT LOG ===================
let _auditPage = 1;
let _auditDebounceTimer = null;

function renderAuditDebounced() {
  clearTimeout(_auditDebounceTimer);
  _auditDebounceTimer = setTimeout(() => renderAuditLog(), 300);
}

async function renderAuditLog(page) {
  if (page) _auditPage = page;
  const params = { page: _auditPage, limit: 30 };

  const search = (document.getElementById('audit-search') || {}).value;
  const eType  = (document.getElementById('audit-flt-type') || {}).value;
  const action = (document.getElementById('audit-flt-action') || {}).value;
  const from   = (document.getElementById('audit-from') || {}).value;
  const to     = (document.getElementById('audit-to') || {}).value;

  if (search) params.search = search;
  if (eType)  params.entity_type = eType;
  if (action) params.action = action;
  if (from)   params.from = from;
  if (to)     params.to = to;

  try {
    const res = await AuditAPI.getAll(params);
    const rows = res.data || [];
    const total = res.total || 0;
    const pages = res.pages || 1;

    const actionIcons = {
      'إضافة': '➕', 'تعديل': '✏️', 'حذف': '🗑️',
      'تصدير': '📥', 'دخول': '🔑', 'خروج': '🚪',
    };
    const actionColors = {
      'إضافة': '#dcfce7', 'تعديل': '#fef9c3', 'حذف': '#fee2e2',
      'تصدير': '#e0f2fe', 'دخول': '#f3e8ff', 'خروج': '#fce7f3',
    };
    const typeIcons = {
      'عضو': '👤', 'دفعة': '💳', 'معاملة': '💰', 'فعالية': '🗓️',
      'تصويت': '🗳️', 'إعدادات': '⚙️', 'فرع': '🌳', 'ميديا': '📷',
      'members': '👤', 'payments': '💳', 'transactions': '💰',
      'events': '🗓️', 'polls': '🗳️', 'settings': '⚙️',
      'branches': '🌳', 'media': '📷', 'auth': '🔑',
    };

    const tbody = document.getElementById('audit-tbody');
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted)">لا توجد سجلات تدقيق بعد</td></tr>';
    } else {
      tbody.innerHTML = rows.map(r => {
        const icon = actionIcons[r.action] || '📝';
        const bg   = actionColors[r.action] || '#f4f7f4';
        const tIcon = typeIcons[r.entity_type] || '📄';
        const dt = r.created_at ? new Date(r.created_at) : null;
        const dateStr = dt ? dt.toLocaleDateString('ar-SA',{year:'numeric',month:'short',day:'numeric'}) + ' ' + dt.toLocaleTimeString('ar-SA',{hour:'2-digit',minute:'2-digit'}) : '';
        const detailStr = r.details && Object.keys(r.details).length
          ? '<div style="font-size:10px;color:var(--text-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + JSON.stringify(r.details).substring(0,80) + '</div>'
          : '<span style="color:var(--text-muted);font-size:11px">—</span>';
        return `<tr>
          <td style="font-size:11px;white-space:nowrap">${dateStr}</td>
          <td style="font-size:12px">${r.user || 'admin'}</td>
          <td><span style="background:${bg};padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600">${icon} ${r.action}</span></td>
          <td style="font-size:12px">${tIcon} ${r.entity_type}</td>
          <td style="font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${r.entity_name || '—'}</td>
          <td>${detailStr}</td>
        </tr>`;
      }).join('');
    }

    document.getElementById('audit-count').textContent = total + ' سجل';

    // Pagination
    const pag = document.getElementById('audit-pagination');
    if (pages <= 1) {
      pag.innerHTML = '';
    } else {
      let html = '';
      if (_auditPage > 1) html += `<button class="btn btn-outline btn-xs" onclick="renderAuditLog(${_auditPage-1})">❮</button>`;
      const start = Math.max(1, _auditPage - 2);
      const end   = Math.min(pages, _auditPage + 2);
      for (let i = start; i <= end; i++) {
        html += `<button class="btn ${i===_auditPage?'btn-primary':'btn-outline'} btn-xs" onclick="renderAuditLog(${i})">${i}</button>`;
      }
      if (_auditPage < pages) html += `<button class="btn btn-outline btn-xs" onclick="renderAuditLog(${_auditPage+1})">❯</button>`;
      pag.innerHTML = html;
    }
  } catch (e) {
    console.error('Audit log error:', e);
    document.getElementById('audit-tbody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--danger)">فشل تحميل سجل التدقيق</td></tr>';
  }
}

// =================== DASHBOARD ===================
function renderDashboard(){
  const inc=State.getTransactions().filter(t=>t.type==='إيراد').reduce((s,t)=>s+t.amount,0);
  const exp=State.getTransactions().filter(t=>t.type==='مصروف').reduce((s,t)=>s+t.amount,0);
  document.getElementById('d-balance').textContent=fmt(inc-exp);
  document.getElementById('d-members').textContent=State.getMembers().filter(m=>m.status==='مشترك').length;
  document.getElementById('d-total').textContent=State.getMembers().length;
  document.getElementById('d-committees').textContent=State.getCommittees().length;
  const p=curPeriod(); const pays=p?State.getPayments().filter(x=>x.periodId===p.id):[];
  const paid=pays.filter(x=>x.status==='مدفوع'); const unpaid=pays.filter(x=>x.status==='لم يدفع');
  document.getElementById('d-unpaid').textContent=unpaid.length;
  document.getElementById('d-period-lbl').textContent=p?p.name:'لا دورة';
  const pct=pays.length>0?Math.round(paid.length/pays.length*100):0;
  document.getElementById('d-pct').textContent=pct+'%';
  document.getElementById('d-bar').style.width=pct+'%';
  document.getElementById('d-paid').textContent=paid.length;
  document.getElementById('d-pending').textContent=unpaid.length;
  const recent=[...State.getTransactions()].sort((a,b)=>b.date.localeCompare(a.date)).slice(0,5);
  document.getElementById('d-recent').innerHTML=recent.length?recent.map(tx=>`<div style="display:flex;align-items:center;gap:10px;padding:9px 18px;border-bottom:1px solid #f0f5f1"><div style="width:34px;height:34px;border-radius:10px;background:${tx.type==='إيراد'?'#dcfce7':'#fee2e2'};display:flex;align-items:center;justify-content:center;font-size:14px">${tx.type==='إيراد'?'⬆️':'⬇️'}</div><div style="flex:1"><div style="font-size:13px;font-weight:600">${tx.desc}</div><div style="font-size:11px;color:var(--text-muted)">${tx.date} · ${tx.category}</div></div><div style="font-weight:700;color:${tx.type==='إيراد'?'var(--green)':'var(--danger)'}">${tx.type==='إيراد'?'+':'-'}${fmt(tx.amount)}</div></div>`).join(''):'<div class="empty-state"><div class="empty-icon">📋</div><p>لا معاملات</p></div>';
  const upcoming=State.getEvents().filter(e=>e.status==='قادم'||e.status==='جاري').slice(0,4);
  document.getElementById('d-events').innerHTML=upcoming.length?upcoming.map(ev=>{ const c=State.getCommittees().find(x=>x.id===ev.committeeId); return `<div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #f0f5f1"><div style="width:38px;height:38px;border-radius:10px;background:${c?c.color:'linear-gradient(135deg,var(--green-dark),var(--green))'};display:flex;align-items:center;justify-content:center;font-size:16px">${ev.icon||'🎉'}</div><div style="flex:1"><div style="font-size:13px;font-weight:600">${ev.name}</div><div style="font-size:11px;color:var(--text-muted)">${ev.date||'—'}</div></div><span class="badge ${sBadge(ev.status)}">${ev.status}</span></div>`; }).join(''):'<div class="empty-state"><div class="empty-icon">🗓️</div><p>لا فعاليات قادمة</p></div>';
  const activePolls=State.getPolls().filter(x=>x.active).slice(0,3);
  document.getElementById('d-polls').innerHTML=activePolls.length?activePolls.map(poll=>{ const total=poll.options.reduce((s,o)=>s+o.votes.length,0); return `<div style="padding:9px 0;border-bottom:1px solid #f0f5f1"><div style="font-size:13px;font-weight:600">${poll.title}</div><div style="font-size:11px;color:var(--text-muted)">${total} صوت</div></div>`; }).join(''):'<div class="empty-state"><div class="empty-icon">🗳️</div><p>لا تصويتات نشطة</p></div>';

  // آخر الرسائل
  var dMsgs = document.getElementById('d-messages');
  var dMsgBadge = document.getElementById('d-msg-badge');
  if (dMsgs && typeof getMessages === 'function') {
    var msgs = getMessages().slice(0, 5);
    dMsgs.innerHTML = msgs.length ? msgs.map(function(m) {
      return '<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f0f5f1">' +
        '<div style="width:34px;height:34px;border-radius:50%;background:' + (m.is_read ? '#f0f5f1' : '#dcfce7') + ';display:flex;align-items:center;justify-content:center;font-size:14px">' + (m.is_read ? '✉️' : '📩') + '</div>' +
        '<div style="flex:1"><div style="font-size:13px;font-weight:' + (m.is_read ? '400' : '700') + '">' + (m.subject || m.name) + '</div>' +
        '<div style="font-size:11px;color:var(--text-muted)">' + m.name + ' · ' + (m.created_at || '').substring(0, 10) + '</div></div></div>';
    }).join('') : '<div class="empty-state"><div class="empty-icon">✉️</div><p>لا رسائل</p></div>';
    if (dMsgBadge) dMsgBadge.textContent = getMessagesUnread() + ' جديدة';
  }

  // آخر الأخبار
  var dNews = document.getElementById('d-news');
  if (dNews && typeof getNews === 'function') {
    var news = getNews().slice(0, 5);
    dNews.innerHTML = news.length ? news.map(function(n) {
      return '<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f0f5f1">' +
        '<div style="width:34px;height:34px;border-radius:10px;background:#e8f5ec;display:flex;align-items:center;justify-content:center;font-size:14px">📰</div>' +
        '<div style="flex:1"><div style="font-size:13px;font-weight:600">' + n.title + '</div>' +
        '<div style="font-size:11px;color:var(--text-muted)">' + n.category + ' · ' + (n.created_at || '').substring(0, 10) + '</div></div>' +
        '<span class="badge ' + (n.status === 'published' ? 'badge-success' : 'badge-warning') + '">' + (n.status === 'published' ? 'منشور' : 'مسودة') + '</span></div>';
    }).join('') : '<div class="empty-state"><div class="empty-icon">📰</div><p>لا أخبار</p></div>';
  }

  // مناصب لوحة القيادة
  var dashPosGrid = document.getElementById('dashboard-positions-grid');
  if (dashPosGrid && typeof _positionsData !== 'undefined' && _positionsData.length) {
    dashPosGrid.innerHTML = _positionsData.map(function(p) {
      var memberNames = (p.members||[]).map(function(m){ return esc(m.name); }).join(' - ') || '';
      return '<div style="border:2px solid var(--border);border-radius:14px;padding:14px">' +
        '<div style="display:flex;align-items:center;gap:10px">' +
        '<div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--green-dark),var(--green));display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">' + esc(p.icon) + '</div>' +
        '<div><div style="font-size:12px;color:var(--text-muted);font-weight:600">' + esc(p.title) + '</div>' +
        '<div style="font-size:13px;font-weight:700;color:var(--green-dark)">' + memberNames + '</div></div></div></div>';
    }).join('');
  }

  updateMessageBadge();
}

// =================== NEWS ===================
function renderNews(page) {
  var search = (document.getElementById('news-search') || {}).value || '';
  var fltStatus = (document.getElementById('news-flt-status') || {}).value || '';
  var fltCat = (document.getElementById('news-flt-cat') || {}).value || '';
  var all = getNews().filter(function(n) {
    if (search && n.title.indexOf(search) === -1 && (n.content || '').indexOf(search) === -1) return false;
    if (fltStatus && n.status !== fltStatus) return false;
    if (fltCat && n.category !== fltCat) return false;
    return true;
  });
  var info = paginate(all, 'news', page, 15);
  var tbody = document.getElementById('news-tbody');
  if (!tbody) return;
  tbody.innerHTML = info.data.length ? info.data.map(function(n, i) {
    var idx = (info.page - 1) * 15 + i + 1;
    return '<tr>' +
      '<td data-label="#">' + idx + '</td>' +
      '<td data-label="العنوان"><strong>' + n.title + '</strong>' + (n.excerpt ? '<br><small style="color:var(--text-muted)">' + n.excerpt.substring(0, 60) + '...</small>' : '') + '</td>' +
      '<td data-label="التصنيف"><span class="badge badge-info">' + (n.category || 'عام') + '</span></td>' +
      '<td data-label="الحالة"><span class="badge ' + (n.status === 'published' ? 'badge-success' : 'badge-warning') + '">' + (n.status === 'published' ? 'منشور' : 'مسودة') + '</span></td>' +
      '<td data-label="التاريخ">' + (n.created_at || '').substring(0, 10) + '</td>' +
      '<td data-label="إجراءات"><button class="btn btn-primary btn-sm" onclick="editNews(\'' + n.id + '\')">تعديل</button> <button class="btn btn-danger btn-sm" onclick="NewsService.delete(\'' + n.id + '\')">حذف</button></td>' +
      '</tr>';
  }).join('') : '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted)">لا توجد أخبار</td></tr>';
  document.getElementById('news-count').textContent = all.length + ' خبر';
  document.getElementById('news-pagination').innerHTML = renderPaginationHTML(info, 'renderNews');
}
var debouncedRenderNews = debounce(function() { _pageState.news = 1; renderNews(); }, 300);

function openAddNews() {
  document.getElementById('news-modal-title').textContent = 'إضافة خبر جديد';
  document.getElementById('news-edit-id').value = '';
  document.getElementById('news-title').value = '';
  document.getElementById('news-excerpt').value = '';
  document.getElementById('news-content').value = '';
  document.getElementById('news-category').value = 'عام';
  document.getElementById('news-status').value = 'published';
  document.getElementById('news-image').value = '';
  document.getElementById('news-author').value = '';
  clearValidation();
  openModal('modal-news');
}

function editNews(id) {
  var n = getNews().find(function(x) { return x.id === id; });
  if (!n) return;
  document.getElementById('news-modal-title').textContent = 'تعديل الخبر';
  document.getElementById('news-edit-id').value = id;
  document.getElementById('news-title').value = n.title || '';
  document.getElementById('news-excerpt').value = n.excerpt || '';
  document.getElementById('news-content').value = n.content || '';
  document.getElementById('news-category').value = n.category || 'عام';
  document.getElementById('news-status').value = n.status || 'published';
  document.getElementById('news-image').value = n.image || '';
  document.getElementById('news-author').value = n.author || '';
  clearValidation();
  openModal('modal-news');
}

async function saveNews() {
  clearValidation();
  if (!validateRequired('news-title', 'العنوان')) return;
  var id = document.getElementById('news-edit-id').value;
  var data = {
    title:    document.getElementById('news-title').value.trim(),
    excerpt:  document.getElementById('news-excerpt').value.trim(),
    content:  document.getElementById('news-content').value.trim(),
    category: document.getElementById('news-category').value,
    status:   document.getElementById('news-status').value,
    image:    document.getElementById('news-image').value.trim(),
    author:   document.getElementById('news-author').value.trim(),
  };
  try {
    if (id) { await NewsService.update(id, data); }
    else { await NewsService.create(data); }
  } catch (e) { toast(e.message, 'error'); }
}

// =================== MESSAGES ===================
function renderMessages(page) {
  var search = (document.getElementById('msg-search') || {}).value || '';
  var fltRead = (document.getElementById('msg-flt-read') || {}).value;
  var all = getMessages().filter(function(m) {
    if (search && m.name.indexOf(search) === -1 && (m.subject || '').indexOf(search) === -1 && (m.message || '').indexOf(search) === -1) return false;
    if (fltRead !== '' && fltRead !== undefined) {
      if (fltRead === '0' && m.is_read) return false;
      if (fltRead === '1' && !m.is_read) return false;
    }
    return true;
  });
  var info = paginate(all, 'messages', page, 15);
  var tbody = document.getElementById('msg-tbody');
  if (!tbody) return;
  tbody.innerHTML = info.data.length ? info.data.map(function(m, i) {
    var weight = m.is_read ? '400' : '700';
    var idx = (info.page - 1) * 15 + i + 1;
    return '<tr style="font-weight:' + weight + '">' +
      '<td data-label="#">' + idx + '</td>' +
      '<td data-label="المرسل">' + m.name + (m.email ? '<br><small style="color:var(--text-muted)">' + m.email + '</small>' : '') + '</td>' +
      '<td data-label="الموضوع">' + (m.subject || '—') + '</td>' +
      '<td data-label="الرسالة"><span style="font-weight:400">' + (m.message || '').substring(0, 50) + (m.message && m.message.length > 50 ? '...' : '') + '</span></td>' +
      '<td data-label="التاريخ">' + (m.created_at || '').substring(0, 10) + '</td>' +
      '<td data-label="الحالة"><span class="badge ' + (m.is_read ? 'badge-success' : 'badge-warning') + '">' + (m.is_read ? 'مقروءة' : 'جديدة') + '</span></td>' +
      '<td data-label="إجراءات">' +
        '<button class="btn btn-primary btn-sm" onclick="viewMessage(\'' + m.id + '\')">عرض</button> ' +
        (m.is_read ? '' : '<button class="btn btn-outline btn-sm" onclick="MessageService.markRead(\'' + m.id + '\',true)">قراءة</button> ') +
        '<button class="btn btn-danger btn-sm" onclick="MessageService.delete(\'' + m.id + '\')">حذف</button>' +
      '</td></tr>';
  }).join('') : '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">لا توجد رسائل</td></tr>';
  document.getElementById('msg-count').textContent = all.length + ' رسالة';
  document.getElementById('messages-pagination').innerHTML = renderPaginationHTML(info, 'renderMessages');
}
var debouncedRenderMessages = debounce(function() { _pageState.messages = 1; renderMessages(); }, 300);

function viewMessage(id) {
  var m = getMessages().find(function(x) { return x.id === id; });
  if (!m) return;
  var body = document.getElementById('msg-detail-body');
  body.innerHTML =
    '<div style="margin-bottom:12px"><strong>المرسل:</strong> ' + m.name + '</div>' +
    (m.email ? '<div style="margin-bottom:12px"><strong>البريد:</strong> ' + m.email + '</div>' : '') +
    (m.phone ? '<div style="margin-bottom:12px"><strong>الجوال:</strong> ' + m.phone + '</div>' : '') +
    (m.subject ? '<div style="margin-bottom:12px"><strong>الموضوع:</strong> ' + m.subject + '</div>' : '') +
    '<div style="margin-bottom:12px"><strong>التاريخ:</strong> ' + (m.created_at || '') + '</div>' +
    '<div style="background:var(--bg);padding:16px;border-radius:10px;line-height:1.8;white-space:pre-wrap">' + m.message + '</div>';
  openModal('modal-msg-detail');
  // تحديد كمقروءة تلقائياً
  if (!m.is_read) MessageService.markRead(id, true);
}

function updateMessageBadge() {
  var badge = document.getElementById('msg-badge');
  if (!badge || typeof getMessagesUnread !== 'function') return;
  var count = getMessagesUnread();
  badge.textContent = count;
  badge.style.display = count > 0 ? 'inline-block' : 'none';
}

// =================== STATUS REVIEW (مراجعة حالات الأعضاء) ===================
var _statusChanges = [];

async function loadStatusReview() {
    var panel = document.getElementById('status-review-panel');
    var body = document.getElementById('status-review-body');
    if (!panel || !body) return;

    body.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted)">جاري حساب الحالات...</div>';
    panel.style.display = '';

    try {
        var res = await apiFetch('/api/members.php?action=review_statuses');
        _statusChanges = res.data || [];

        if (!_statusChanges.length) {
            body.innerHTML = '<div style="text-align:center;padding:30px;color:var(--text-muted)"><div style="font-size:32px;margin-bottom:8px">✅</div>لا توجد تغييرات مقترحة — جميع الحالات متطابقة</div>';
            return;
        }

        document.getElementById('status-review-desc').textContent =
            'تم العثور على ' + _statusChanges.length + ' عضو بحالة مختلفة عن المحسوبة (من أصل ' + (res.total_periods || 0) + ' فترة مالية). الأعضاء بتجاوز يدوي لا يُعرضون.';

        renderStatusReview();
    } catch (e) {
        body.innerHTML = '<div style="text-align:center;padding:20px;color:#991b1b">خطأ: ' + (e.message || 'فشل التحميل') + '</div>';
    }
}

function renderStatusReview() {
    var body = document.getElementById('status-review-body');
    if (!body || !_statusChanges.length) return;

    var html = '<div class="table-wrap mobile-cards"><table><thead><tr>' +
        '<th>#</th><th>العضو</th><th>الحالة الحالية</th><th></th><th>الحالة المقترحة</th><th>فترات الانقطاع</th><th>القرار</th>' +
        '</tr></thead><tbody>';

    _statusChanges.forEach(function(c, i) {
        html += '<tr>' +
            '<td>' + (i + 1) + '</td>' +
            '<td style="font-weight:600">' + c.member_name + '</td>' +
            '<td>' + statusBadgeReview(c.current_status) + '</td>' +
            '<td style="font-size:18px;text-align:center">←</td>' +
            '<td>' + statusBadgeReview(c.suggested) + '</td>' +
            '<td style="text-align:center">' + c.unpaid_periods + '</td>' +
            '<td><div style="display:flex;gap:4px;align-items:center">' +
                '<button class="btn btn-primary btn-xs" onclick="acceptStatusChange(' + i + ')" title="تأكيد">✅</button>' +
                '<button class="btn btn-outline btn-xs" onclick="overrideStatusChange(' + i + ')" title="تجاوز يدوي">✋</button>' +
            '</div></td>' +
            '</tr>';
    });

    html += '</tbody></table></div>';
    body.innerHTML = html;
}

function statusBadgeReview(status) {
    if (status === 'مشترك') return '<span class="badge badge-success">مشترك</span>';
    if (status === 'منقطع') return '<span class="badge badge-warning">منقطع</span>';
    if (status === 'غير مشترك') return '<span class="badge badge-gray">غير مشترك</span>';
    return '<span class="badge badge-gray">' + status + '</span>';
}

async function acceptStatusChange(idx) {
    var c = _statusChanges[idx];
    if (!c) return;

    try {
        await apiFetch('/api/members.php?action=apply_statuses', {
            method: 'POST',
            body: JSON.stringify({
                changes: [{ member_id: c.member_id, new_status: c.suggested, override: 0 }]
            })
        });
        toast('تم تحديث حالة ' + c.member_name);
        _statusChanges.splice(idx, 1);
        if (_statusChanges.length) {
            renderStatusReview();
        } else {
            document.getElementById('status-review-body').innerHTML =
                '<div style="text-align:center;padding:30px;color:var(--text-muted)"><div style="font-size:32px;margin-bottom:8px">✅</div>تم مراجعة جميع الحالات</div>';
        }
        renderMembers();
    } catch (e) {
        toast(e.message || 'خطأ في التحديث', 'error');
    }
}

function overrideStatusChange(idx) {
    var c = _statusChanges[idx];
    if (!c) return;

    var note = prompt('سبب التجاوز اليدوي لـ "' + c.member_name + '":\n(اترك الحقل فارغاً للإلغاء)');
    if (note === null || note.trim() === '') return;

    apiFetch('/api/members.php?action=apply_statuses', {
        method: 'POST',
        body: JSON.stringify({
            changes: [{ member_id: c.member_id, new_status: c.current_status, override: 1, override_note: note.trim() }]
        })
    }).then(function() {
        toast('تم تثبيت حالة ' + c.member_name + ' يدوياً');
        _statusChanges.splice(idx, 1);
        if (_statusChanges.length) {
            renderStatusReview();
        } else {
            document.getElementById('status-review-body').innerHTML =
                '<div style="text-align:center;padding:30px;color:var(--text-muted)"><div style="font-size:32px;margin-bottom:8px">✅</div>تم مراجعة جميع الحالات</div>';
        }
        renderMembers();
    }).catch(function(e) {
        toast(e.message || 'خطأ', 'error');
    });
}

async function confirmAllStatuses() {
    if (!_statusChanges.length) { toast('لا توجد تغييرات', 'error'); return; }
    if (!confirm('هل تريد تأكيد جميع التغييرات المقترحة (' + _statusChanges.length + ' عضو)؟')) return;

    try {
        var changes = _statusChanges.map(function(c) {
            return { member_id: c.member_id, new_status: c.suggested, override: 0 };
        });
        var res = await apiFetch('/api/members.php?action=apply_statuses', {
            method: 'POST',
            body: JSON.stringify({ changes: changes })
        });
        toast(res.message || 'تم التحديث');
        _statusChanges = [];
        document.getElementById('status-review-body').innerHTML =
            '<div style="text-align:center;padding:30px;color:var(--text-muted)"><div style="font-size:32px;margin-bottom:8px">✅</div>تم تأكيد جميع التغييرات</div>';
        renderMembers();
    } catch (e) {
        toast(e.message || 'خطأ', 'error');
    }
}

function hideStatusReview() {
    var panel = document.getElementById('status-review-panel');
    if (panel) panel.style.display = 'none';
}

// =================== OBJECTIONS (اعتراضات الأعضاء) ===================
var _objections = [];
var _objectionCounts = {};
var _currentMsgTab = 'messages';

function switchMsgTab(tab) {
    _currentMsgTab = tab;
    document.getElementById('msg-panel-messages').style.display = tab === 'messages' ? '' : 'none';
    document.getElementById('msg-panel-objections').style.display = tab === 'objections' ? '' : 'none';
    var btnMsg = document.getElementById('msg-tab-messages');
    var btnObj = document.getElementById('msg-tab-objections');
    btnMsg.style.fontWeight = tab === 'messages' ? '700' : '400';
    btnMsg.style.background = tab === 'messages' ? 'var(--surface)' : '';
    btnObj.style.fontWeight = tab === 'objections' ? '700' : '400';
    btnObj.style.background = tab === 'objections' ? 'var(--surface)' : '';
    if (tab === 'objections') loadObjections();
}

async function loadObjections() {
    try {
        var res = await apiFetch('/api/objections.php');
        _objections = res.data || [];
        _objectionCounts = res.counts || {};
        updateObjBadge();
        renderObjections();
    } catch (e) {
        toast('خطأ في تحميل الاعتراضات: ' + e.message, 'error');
    }
}

function updateObjBadge() {
    var badge = document.getElementById('obj-badge');
    var c = _objectionCounts['جديد'] || 0;
    if (badge) {
        badge.textContent = c;
        badge.style.display = c > 0 ? 'inline-block' : 'none';
    }
}

function renderObjections(page) {
    var flt = (document.getElementById('obj-flt-status') || {}).value || '';
    var list = _objections;
    if (flt) list = list.filter(function(o) { return o.status === flt; });

    // عرض عداد الحالات
    var countsEl = document.getElementById('obj-status-counts');
    if (countsEl) {
        countsEl.innerHTML =
            '<span class="badge badge-warning">جديد: ' + (_objectionCounts['جديد'] || 0) + '</span>' +
            '<span class="badge badge-info" style="background:#3b82f6;color:#fff">قيد المراجعة: ' + (_objectionCounts['قيد المراجعة'] || 0) + '</span>' +
            '<span class="badge badge-success">تمت المعالجة: ' + (_objectionCounts['تمت المعالجة'] || 0) + '</span>' +
            '<span class="badge badge-gray">مرفوض: ' + (_objectionCounts['مرفوض'] || 0) + '</span>';
    }

    var tbody = document.getElementById('obj-tbody');
    if (!tbody) return;

    if (!list.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)">لا توجد اعتراضات</td></tr>';
        document.getElementById('obj-count').textContent = '0 اعتراض';
        document.getElementById('objections-pagination').innerHTML = '';
        return;
    }

    var info = paginate(list, 'objections', page, 15);
    var startIdx = (info.page - 1) * 15;

    tbody.innerHTML = info.data.map(function(o, i) {
        var sb = objStatusBadge(o.status);
        var typeBadge = '<span class="badge badge-gray" style="font-size:10px">' + (o.related_to || 'أخرى') + '</span>';
        return '<tr>' +
            '<td data-label="#">' + (startIdx + i + 1) + '</td>' +
            '<td data-label="العضو" style="font-weight:600">' + (o.member_name || '—') + '</td>' +
            '<td data-label="AWM-ID" style="font-family:monospace;font-size:12px;color:var(--green-dark)">' + (o.awm_id || '—') + '</td>' +
            '<td data-label="النوع">' + typeBadge + '</td>' +
            '<td data-label="الموضوع">' + (o.subject || '—') + '</td>' +
            '<td data-label="التاريخ" style="font-size:11px">' + (o.created_at || '').substring(0, 10) + '</td>' +
            '<td data-label="الحالة">' + sb + '</td>' +
            '<td data-label="إجراءات">' +
                '<button class="btn btn-primary btn-sm" onclick="viewObjection(\'' + o.id + '\')">عرض</button>' +
            '</td></tr>';
    }).join('');

    document.getElementById('obj-count').textContent = list.length + ' اعتراض';
    document.getElementById('objections-pagination').innerHTML = renderPaginationHTML(info, 'renderObjections');
}

function objStatusBadge(status) {
    if (status === 'جديد') return '<span class="badge badge-warning">جديد</span>';
    if (status === 'قيد المراجعة') return '<span class="badge" style="background:#3b82f6;color:#fff">قيد المراجعة</span>';
    if (status === 'تمت المعالجة') return '<span class="badge badge-success">تمت المعالجة</span>';
    if (status === 'مرفوض') return '<span class="badge badge-gray">مرفوض</span>';
    return '<span class="badge badge-gray">' + status + '</span>';
}

var _currentObjId = null;

function viewObjection(id) {
    var o = _objections.find(function(x) { return x.id === id; });
    if (!o) return;
    _currentObjId = id;

    var body = document.getElementById('obj-detail-body');
    body.innerHTML =
        '<div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap">' +
            '<div style="flex:1;min-width:200px">' +
                '<div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">العضو</div>' +
                '<div style="font-weight:700">' + (o.member_name || '—') + ' <span style="font-family:monospace;color:var(--green-dark)">' + (o.awm_id || '') + '</span></div>' +
            '</div>' +
            '<div>' +
                '<div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">النوع</div>' +
                '<span class="badge badge-gray">' + (o.related_to || 'أخرى') + '</span>' +
            '</div>' +
            '<div>' +
                '<div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">التاريخ</div>' +
                '<div style="font-size:13px">' + (o.created_at || '') + '</div>' +
            '</div>' +
        '</div>' +
        '<div style="margin-bottom:16px">' +
            '<div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">الموضوع</div>' +
            '<div style="font-weight:700;font-size:15px">' + (o.subject || '') + '</div>' +
        '</div>' +
        '<div style="margin-bottom:16px">' +
            '<div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">التفاصيل</div>' +
            '<div style="background:var(--bg);padding:14px;border-radius:8px;line-height:1.8;white-space:pre-wrap;max-height:200px;overflow-y:auto">' + escapeHtmlObj(o.body || '') + '</div>' +
        '</div>' +
        '<div style="margin-bottom:12px">' +
            '<label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px">الحالة:</label>' +
            '<select id="obj-reply-status" class="filter-select" style="width:100%">' +
                '<option value="جديد"' + (o.status === 'جديد' ? ' selected' : '') + '>جديد</option>' +
                '<option value="قيد المراجعة"' + (o.status === 'قيد المراجعة' ? ' selected' : '') + '>قيد المراجعة</option>' +
                '<option value="تمت المعالجة"' + (o.status === 'تمت المعالجة' ? ' selected' : '') + '>تمت المعالجة</option>' +
                '<option value="مرفوض"' + (o.status === 'مرفوض' ? ' selected' : '') + '>مرفوض</option>' +
            '</select>' +
        '</div>' +
        '<div>' +
            '<label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px">رد المدير:</label>' +
            '<textarea id="obj-reply-text" rows="4" style="width:100%;padding:12px;border:2px solid var(--border);border-radius:8px;font-family:inherit;font-size:13px;line-height:1.7;background:var(--bg);color:var(--text);resize:vertical" placeholder="اكتب ردك هنا...">' + escapeHtmlObj(o.admin_reply || '') + '</textarea>' +
            (o.replied_at ? '<div style="font-size:11px;color:var(--text-muted);margin-top:4px">آخر رد: ' + o.replied_at + '</div>' : '') +
        '</div>';

    openModal('modal-objection-detail');
}

function escapeHtmlObj(str) {
    var d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

async function saveObjectionReply() {
    if (!_currentObjId) return;
    var status = document.getElementById('obj-reply-status').value;
    var reply = document.getElementById('obj-reply-text').value.trim();

    try {
        await apiFetch('/api/objections.php?id=' + _currentObjId, {
            method: 'PUT',
            body: JSON.stringify({ status: status, admin_reply: reply })
        });
        toast('تم حفظ الرد بنجاح');
        closeModal('modal-objection-detail');
        loadObjections();
    } catch (e) {
        toast(e.message || 'خطأ في الحفظ', 'error');
    }
}

// تحميل الاعتراضات عند فتح صفحة الرسائل لتحديث الشارة
var _origRenderMessages = typeof renderMessages === 'function' ? null : null;
(function() {
    var origShowPage = window.showPage;
    if (!origShowPage) return;
    // تحميل الاعتراضات عند فتح صفحة الرسائل
    var _objLoaded = false;
    var _origShowPage = origShowPage;
    window.showPage = function(name, el) {
        _origShowPage(name, el);
        if (name === 'messages' && !_objLoaded) {
            _objLoaded = true;
            loadObjections();
        }
    };
})();

// =================== REPORTS ===================
// =====================================================
// REPORTS - التقارير المالية والإدارية
// =====================================================

var reportsActiveTab = 'auto-reports';

// ---- Import state ----
var importWorkbook = null;
var importRawData = null;
var importFileName = '';
var importData = [];
var importSelectedRows = new Set();
var importStep = 1;

var IMPORT_CATEGORIES = ['رسوم الأعضاء', 'رحلة العمرة', 'غداء العيد', 'رحلة ترفيهية', 'مسابقة', 'مصاريف إدارية', 'استثمار', 'عقيقة جماعية', 'تبرعات', 'اشتراكات', 'ضيافة', 'نقل', 'صيانة', 'تعليم', 'رحلات', 'مناسبات', 'أخرى'];

function renderReportsPage() {
  switchReportsTab(reportsActiveTab);
}

function switchReportsTab(tab) {
  reportsActiveTab = tab;
  document.querySelectorAll('.reports-tab-btn').forEach(function(btn) {
    btn.classList.toggle('active', btn.dataset.tab === tab);
  });
  document.querySelectorAll('.reports-tab-content').forEach(function(el) {
    el.style.display = el.id === 'rt-' + tab ? 'block' : 'none';
  });
  if (tab === 'auto-reports') renderAutoReportsTab();
  else if (tab === 'import') renderImportTab();
  else if (tab === 'archive') renderArchiveTab();
}

// =====================================================
// AUTO REPORTS — التقارير التلقائية
// =====================================================

var autoReportType = '';
var autoReportData = null;
var autoReportLoading = false;
var autoReportExtraItems = [];

var AR_TYPES = [
  { key: 'summary',       icon: '📋', label: 'ملخص تنفيذي',  desc: 'أهم الأرقام في صفحة واحدة' },
  { key: 'financial',     icon: '💰', label: 'مالي شامل',     desc: 'إيرادات ومصروفات وعُهَد اللجان' },
  { key: 'subscriptions', icon: '💳', label: 'الاشتراكات',    desc: 'من دفع ومن لم يدفع' },
  { key: 'committees',    icon: '👥', label: 'اللجان',        desc: 'الأعضاء والميزانيات' },
  { key: 'events',        icon: '🎪', label: 'الفعاليات',     desc: 'الأنشطة والميزانيات' },
];

function renderAutoReportsTab() {
  var container = document.getElementById('rt-auto-reports');
  if (!container) return;

  var html = '';

  // بطاقات الاختيار
  html += '<div class="ar-type-grid">';
  AR_TYPES.forEach(function(t) {
    html += '<div class="ar-type-card' + (autoReportType === t.key ? ' active' : '') + '" onclick="selectReportType(\'' + t.key + '\')">';
    html += '<div class="ar-type-icon">' + t.icon + '</div>';
    html += '<div class="ar-type-label">' + t.label + '</div>';
    html += '<div class="ar-type-desc">' + t.desc + '</div>';
    html += '</div>';
  });
  html += '</div>';

  // فلاتر
  if (autoReportType) {
    html += renderAutoReportFilters();
  }

  // نتائج
  html += '<div id="ar-results"></div>';

  container.innerHTML = html;

  if (autoReportData && !autoReportLoading) {
    renderAutoReportResults();
  }
}

function selectReportType(key) {
  autoReportType = key;
  autoReportData = null;
  autoReportExtraItems = [];
  renderAutoReportsTab();
}

function renderAutoReportFilters() {
  var committees = State.getCommittees ? State.getCommittees() : [];
  var periods = State.getPeriods ? State.getPeriods() : [];
  var showDate = ['summary', 'financial', 'events'].indexOf(autoReportType) >= 0;
  var showCommittee = ['summary', 'financial', 'events'].indexOf(autoReportType) >= 0;
  var showPeriod = ['summary', 'financial', 'subscriptions'].indexOf(autoReportType) >= 0;

  var html = '<div class="ar-filters">';

  if (showDate) {
    html += '<label>من: <input type="date" id="ar-date-from"></label>';
    html += '<label>إلى: <input type="date" id="ar-date-to"></label>';
  }

  if (showCommittee) {
    html += '<label>اللجنة: <select id="ar-committee"><option value="">الكل</option>';
    committees.forEach(function(c) { html += '<option value="' + c.id + '">' + c.name + '</option>'; });
    html += '</select></label>';
  }

  if (showPeriod) {
    html += '<label>الفترة: <select id="ar-period"><option value="">الكل</option>';
    periods.forEach(function(p) { html += '<option value="' + p.id + '">' + p.name + '</option>'; });
    html += '</select></label>';
  }

  html += '<button class="btn btn-primary" onclick="generateAutoReport()">🔄 توليد التقرير</button>';
  html += '</div>';
  return html;
}

function generateAutoReport() {
  autoReportLoading = true;
  renderAutoReportResults();

  var params = '?type=' + autoReportType;
  var df = document.getElementById('ar-date-from');
  var dt = document.getElementById('ar-date-to');
  var cm = document.getElementById('ar-committee');
  var pr = document.getElementById('ar-period');
  if (df && df.value) params += '&date_from=' + df.value;
  if (dt && dt.value) params += '&date_to=' + dt.value;
  if (cm && cm.value) params += '&committee_id=' + cm.value;
  if (pr && pr.value) params += '&period_id=' + pr.value;

  apiFetch('/api/report-generator.php' + params)
    .then(function(res) {
      autoReportData = res.data;
      autoReportLoading = false;
      renderAutoReportResults();
    })
    .catch(function(err) {
      autoReportLoading = false;
      autoReportData = null;
      toast('خطأ في توليد التقرير: ' + err.message, 'error');
      var c = document.getElementById('ar-results');
      if (c) c.innerHTML = '';
    });
}

function renderAutoReportResults() {
  var container = document.getElementById('ar-results');
  if (!container) return;

  if (autoReportLoading) {
    container.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)"><div style="font-size:32px;margin-bottom:8px">⏳</div>جاري توليد التقرير...</div>';
    return;
  }
  if (!autoReportData) { container.innerHTML = ''; return; }

  var html = '';
  switch (autoReportType) {
    case 'summary':       html = renderSummaryReport(autoReportData); break;
    case 'financial':     html = renderFinancialReport(autoReportData); break;
    case 'subscriptions': html = renderSubscriptionsReport(autoReportData); break;
    case 'committees':    html = renderCommitteesReport(autoReportData); break;
    case 'events':        html = renderEventsReport(autoReportData); break;
  }

  html += '<div class="ar-actions" style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">';
  html += '<button class="btn btn-outline" onclick="printReport()">🖨️ طباعة</button>';
  html += '<button class="btn btn-outline" onclick="exportReportPDF()">📄 تصدير PDF</button>';
  html += '</div>';

  container.innerHTML = html;
}

// ---- ملخص تنفيذي ----
function renderSummaryReport(d) {
  var fin = d.financial || {};
  var mem = d.members || {};
  var sub = d.subscriptions || {};
  var comms = d.committee_budgets || [];
  var upcoming = d.upcoming_events || [];

  var html = '<h3 style="margin-bottom:16px">📋 الملخص التنفيذي</h3>';

  // بطاقات مالية
  html += '<div class="ar-stat-grid">';
  html += arStatCard(fmt(fin.total_income || 0), 'إجمالي الإيرادات', 'var(--green)');
  html += arStatCard(fmt(fin.total_expense || 0), 'إجمالي المصروفات', 'var(--danger)');
  html += arStatCard(fmt(fin.net_balance || 0), 'صافي الرصيد', (fin.net_balance || 0) >= 0 ? 'var(--green)' : 'var(--danger)');
  html += arStatCard((sub.collection_rate || 0) + '%', 'نسبة تحصيل الاشتراكات', '#0369a1');
  html += '</div>';

  // أعضاء
  html += '<div class="card" style="margin-bottom:16px"><div class="card-header"><div class="card-title">👥 الأعضاء</div></div><div class="card-body">';
  html += '<div style="display:flex;gap:16px;flex-wrap:wrap">';
  Object.keys(mem).forEach(function(s) {
    if (s === 'total') return;
    html += '<div style="text-align:center;padding:8px 16px;background:var(--bg);border-radius:8px"><div style="font-size:20px;font-weight:900">' + mem[s] + '</div><div style="font-size:11px;color:var(--text-muted)">' + s + '</div></div>';
  });
  html += '</div></div></div>';

  // لجان
  if (comms.length) {
    html += '<div class="card" style="margin-bottom:16px"><div class="card-header"><div class="card-title">🏛️ مصروفات اللجان</div></div><div class="card-body">';
    html += '<table class="ar-table"><thead><tr><th>اللجنة</th><th>المصروف</th></tr></thead><tbody>';
    comms.forEach(function(c) {
      html += '<tr><td>' + (c.name || '') + '</td><td>' + fmt(c.spent) + ' ريال</td></tr>';
    });
    html += '</tbody></table></div></div>';
  }

  // فعاليات قادمة
  if (upcoming.length) {
    html += '<div class="card"><div class="card-header"><div class="card-title">📅 فعاليات قادمة</div></div><div class="card-body">';
    html += '<table class="ar-table"><thead><tr><th>الفعالية</th><th>التاريخ</th><th>اللجنة</th></tr></thead><tbody>';
    upcoming.forEach(function(e) {
      html += '<tr><td>' + (e.name || '') + '</td><td>' + (e.date || '') + '</td><td>' + (e.committee_name || '—') + '</td></tr>';
    });
    html += '</tbody></table></div></div>';
  }

  return html;
}

// ---- تقرير مالي شامل ----
function renderFinancialReport(d) {
  var s = d.summary || {};
  var inc = d.income_by_category || [];
  var exp = d.expense_by_category || [];
  var comms = d.committee_budgets || [];
  var sub = d.subscription_summary || {};

  var html = '<h3 style="margin-bottom:16px">💰 التقرير المالي الشامل</h3>';

  // بطاقات
  html += '<div class="ar-stat-grid">';
  html += arStatCard(fmt(s.total_income || 0), 'إجمالي الإيرادات', 'var(--green)');
  html += arStatCard(fmt(s.total_expense || 0), 'إجمالي المصروفات', 'var(--danger)');
  html += arStatCard(fmt(s.net_balance || 0), 'صافي الرصيد', (s.net_balance || 0) >= 0 ? 'var(--green)' : 'var(--danger)');
  html += arStatCard(s.total_count || 0, 'عدد المعاملات', '#6b21a8');
  html += '</div>';

  // إيرادات بالفئة
  if (inc.length) {
    html += '<div class="card" style="margin-bottom:16px"><div class="card-header"><div class="card-title">📈 الإيرادات حسب الفئة</div></div><div class="card-body">';
    html += '<table class="ar-table"><thead><tr><th>الفئة</th><th>المبلغ</th><th>العدد</th><th>النسبة</th></tr></thead><tbody>';
    inc.forEach(function(r) {
      html += '<tr><td>' + (r.category || 'بدون') + '</td><td style="color:var(--green);font-weight:700">' + fmt(r.amount) + '</td><td>' + r.count + '</td><td>' + r.percentage + '%</td></tr>';
    });
    html += '</tbody></table></div></div>';
  }

  // مصروفات بالفئة
  if (exp.length) {
    html += '<div class="card" style="margin-bottom:16px"><div class="card-header"><div class="card-title">📉 المصروفات حسب الفئة</div></div><div class="card-body">';
    html += '<table class="ar-table"><thead><tr><th>الفئة</th><th>المبلغ</th><th>العدد</th><th>النسبة</th></tr></thead><tbody>';
    exp.forEach(function(r) {
      html += '<tr><td>' + (r.category || 'بدون') + '</td><td style="color:var(--danger);font-weight:700">' + fmt(r.amount) + '</td><td>' + r.count + '</td><td>' + r.percentage + '%</td></tr>';
    });
    html += '</tbody></table></div></div>';
  }

  // لجان
  if (comms.length) {
    html += '<div class="card" style="margin-bottom:16px"><div class="card-header"><div class="card-title">🏛️ مصروفات اللجان</div></div><div class="card-body">';
    html += '<table class="ar-table"><thead><tr><th>اللجنة</th><th>المصروف</th></tr></thead><tbody>';
    comms.forEach(function(c) {
      html += '<tr><td>' + (c.icon || '') + ' ' + (c.name || '') + '</td><td>' + fmt(c.spent) + ' ريال</td></tr>';
    });
    html += '</tbody></table></div></div>';
  }

  // اشتراكات
  html += '<div class="card" style="margin-bottom:16px"><div class="card-header"><div class="card-title">💳 ملخص الاشتراكات</div></div><div class="card-body">';
  html += '<div style="display:flex;gap:20px;flex-wrap:wrap">';
  html += '<div>المستحق: <strong>' + fmt(sub.total_due || 0) + '</strong> ريال</div>';
  html += '<div>المحصّل: <strong style="color:var(--green)">' + fmt(sub.total_paid || 0) + '</strong> ريال</div>';
  html += '<div>نسبة التحصيل: <strong>' + (sub.collection_rate || 0) + '%</strong></div>';
  html += '</div></div></div>';

  // بنود يدوية
  html += renderExtraItemsSection();

  return html;
}

// ---- تقرير الاشتراكات ----
function renderSubscriptionsReport(d) {
  var mem = d.members || {};
  var periods = d.periods || [];
  var defaulters = d.defaulters || [];

  var html = '<h3 style="margin-bottom:16px">💳 تقرير الاشتراكات</h3>';

  // أعضاء
  html += '<div class="ar-stat-grid">';
  Object.keys(mem).forEach(function(s) {
    html += arStatCard(mem[s], s, s === 'total' ? '#1B3456' : 'var(--text)');
  });
  html += '</div>';

  // فترات
  if (periods.length) {
    html += '<div class="card" style="margin-bottom:16px"><div class="card-header"><div class="card-title">📊 الفترات المالية</div></div><div class="card-body">';
    html += '<table class="ar-table"><thead><tr><th>الفترة</th><th>الرسم</th><th>دفعوا</th><th>لم يدفعوا</th><th>المحصّل</th><th>الالتزام</th></tr></thead><tbody>';
    periods.forEach(function(p) {
      html += '<tr><td>' + (p.name || '') + '</td><td>' + fmt(p.fee_amount) + '</td><td>' + p.paid_count + '</td><td>' + p.unpaid_count + '</td>';
      html += '<td style="color:var(--green)">' + fmt(p.paid_amount) + '</td>';
      html += '<td><strong>' + p.collection_rate + '%</strong></td></tr>';
    });
    html += '</tbody></table></div></div>';
  }

  // متخلفين
  if (defaulters.length) {
    html += '<div class="card"><div class="card-header"><div class="card-title" style="color:var(--danger)">⚠️ المتخلفون عن الدفع (' + defaulters.length + ')</div></div><div class="card-body">';
    html += '<table class="ar-table"><thead><tr><th>الاسم</th><th>الهاتف</th><th>الفترة</th><th>المبلغ</th></tr></thead><tbody>';
    defaulters.forEach(function(d) {
      html += '<tr><td>' + (d.name || '') + '</td><td dir="ltr">' + (d.phone || '—') + '</td><td>' + (d.period_name || '') + '</td><td>' + fmt(d.fee_amount) + '</td></tr>';
    });
    html += '</tbody></table></div></div>';
  } else {
    html += '<div class="card"><div class="card-body"><div class="empty-state"><div class="empty-icon">✅</div><p>لا يوجد متخلفون — ممتاز!</p></div></div></div>';
  }

  return html;
}

// ---- تقرير اللجان ----
function renderCommitteesReport(d) {
  var comms = d.committees || [];
  var summary = d.summary || {};

  var html = '<h3 style="margin-bottom:16px">👥 تقرير اللجان</h3>';

  html += '<div class="ar-stat-grid">';
  html += arStatCard(summary.total_committees || 0, 'عدد اللجان', '#1B3456');
  html += arStatCard(fmt(summary.total_spent || 0), 'إجمالي المصروف', 'var(--danger)');
  html += '</div>';

  comms.forEach(function(c) {
    html += '<div class="card" style="margin-bottom:12px"><div class="card-header"><div class="card-title">' + (c.icon || '🏛️') + ' ' + (c.name || '') + '</div>';
    html += '<div style="font-size:12px;color:var(--text-muted)">' + (c.member_count || 0) + ' عضو · ' + (c.event_count || 0) + ' فعالية</div></div>';
    html += '<div class="card-body">';
    html += '<div style="margin-bottom:8px">المصروف: <strong style="color:var(--danger)">' + fmt(c.spent) + '</strong> ريال</div>';
    if (c.members && c.members.length) {
      html += '<div style="font-size:12px;color:var(--text-muted)">الأعضاء: ';
      html += c.members.map(function(m) { return m.name + (m.role ? ' (' + m.role + ')' : ''); }).join('، ');
      html += '</div>';
    }
    html += '</div></div>';
  });

  if (!comms.length) {
    html += '<div class="empty-state"><div class="empty-icon">🏛️</div><p>لا توجد لجان</p></div>';
  }

  return html;
}

// ---- تقرير الفعاليات ----
function renderEventsReport(d) {
  var stats = d.status_stats || {};
  var events = d.events || [];
  var summary = d.summary || {};

  var html = '<h3 style="margin-bottom:16px">🎪 تقرير الفعاليات</h3>';

  html += '<div class="ar-stat-grid">';
  html += arStatCard(summary.total_events || 0, 'إجمالي الفعاليات', '#1B3456');
  html += arStatCard(fmt(summary.total_budget || 0), 'إجمالي الميزانيات', 'var(--green)');
  html += arStatCard(summary.total_participants || 0, 'إجمالي المشاركين', '#6b21a8');
  var statusLabels = { 'قادم': '📅', 'جاري': '🔄', 'مكتمل': '✅', 'ملغي': '❌' };
  Object.keys(stats).forEach(function(s) {
    html += arStatCard(stats[s], s + ' ' + (statusLabels[s] || ''), 'var(--text)');
  });
  html += '</div>';

  if (events.length) {
    html += '<div class="card"><div class="card-header"><div class="card-title">📋 قائمة الفعاليات</div></div><div class="card-body">';
    html += '<table class="ar-table"><thead><tr><th>الفعالية</th><th>التاريخ</th><th>اللجنة</th><th>الميزانية</th><th>المشاركين</th><th>الحالة</th></tr></thead><tbody>';
    events.forEach(function(e) {
      html += '<tr><td>' + (e.icon || '') + ' ' + (e.name || '') + '</td><td>' + (e.date || '') + '</td><td>' + (e.committee_name || '—') + '</td>';
      html += '<td>' + fmt(e.budget || 0) + '</td><td>' + (e.participants || 0) + '</td><td>' + (e.status || '') + '</td></tr>';
    });
    html += '</tbody></table></div></div>';
  } else {
    html += '<div class="empty-state"><div class="empty-icon">🎪</div><p>لا توجد فعاليات</p></div>';
  }

  return html;
}

// ---- Helpers ----
function arStatCard(value, label, color) {
  return '<div class="ar-stat-card"><div class="ar-stat-value" style="color:' + color + '">' + value + '</div><div class="ar-stat-label">' + label + '</div></div>';
}

// ---- بنود يدوية إضافية ----
function renderExtraItemsSection() {
  var html = '<div class="card" style="margin-bottom:16px"><div class="card-header"><div class="card-title">📝 بنود إضافية (اختياري)</div></div>';
  html += '<div class="card-body">';
  html += '<p style="font-size:12px;color:var(--text-muted);margin-bottom:12px">أضف أرقاماً غير موجودة في النظام — تظهر في التقرير فقط ولا تُحفظ في قاعدة البيانات</p>';
  html += '<div id="ar-extra-list"></div>';
  html += '<div style="display:flex;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap">';
  html += '<input type="text" id="ar-extra-label" placeholder="البند" class="form-control" style="flex:2;min-width:120px">';
  html += '<input type="number" id="ar-extra-amount" placeholder="المبلغ" class="form-control" style="flex:1;min-width:80px">';
  html += '<select id="ar-extra-type" class="form-control" style="flex:1;min-width:80px"><option value="إيراد">إيراد</option><option value="مصروف">مصروف</option><option value="معلومة">معلومة</option></select>';
  html += '<button class="btn btn-primary btn-sm" onclick="addExtraItem()">+ إضافة</button>';
  html += '</div></div></div>';
  return html;
}

function addExtraItem() {
  var label = document.getElementById('ar-extra-label').value.trim();
  var amount = parseFloat(document.getElementById('ar-extra-amount').value) || 0;
  var type = document.getElementById('ar-extra-type').value;
  if (!label) { toast('أدخل اسم البند', 'error'); return; }
  autoReportExtraItems.push({ label: label, amount: amount, type: type });
  document.getElementById('ar-extra-label').value = '';
  document.getElementById('ar-extra-amount').value = '';
  renderExtraItemsList();
}

function removeExtraItem(idx) {
  autoReportExtraItems.splice(idx, 1);
  renderExtraItemsList();
}

function renderExtraItemsList() {
  var el = document.getElementById('ar-extra-list');
  if (!el) return;
  if (!autoReportExtraItems.length) { el.innerHTML = ''; return; }
  el.innerHTML = autoReportExtraItems.map(function(item, i) {
    return '<div style="display:flex;gap:8px;align-items:center;padding:6px 0;border-bottom:1px solid var(--border)">' +
      '<span style="flex:2">' + item.label + '</span>' +
      '<span style="flex:1;font-weight:700;color:' + (item.type === 'مصروف' ? 'var(--danger)' : 'var(--green)') + '">' + fmt(item.amount) + ' ريال</span>' +
      '<span style="font-size:11px;color:var(--text-muted)">' + item.type + '</span>' +
      '<button class="btn btn-xs" style="color:var(--danger)" onclick="removeExtraItem(' + i + ')">✕</button></div>';
  }).join('');
}

// ---- طباعة ----
function printReport() {
  if (!autoReportData) { toast('لا توجد بيانات للطباعة', 'error'); return; }
  var content = document.getElementById('ar-results').innerHTML;
  var win = window.open('', '_blank');
  var h = '<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="utf-8">';
  h += '<title>تقرير — مجلس عائلة العوامي</title><style>';
  h += 'body{font-family:Cairo,Tajawal,sans-serif;direction:rtl;padding:20px;color:#333}';
  h += 'h2,h3{color:#1B3456}';
  h += 'table{width:100%;border-collapse:collapse;margin:10px 0}';
  h += 'th,td{border:1px solid #ddd;padding:8px;text-align:right;font-size:13px}';
  h += 'th{background:#1B3456;color:#fff}';
  h += '.ar-actions,button,.ar-extra-items,.card-header .card-title+div{display:none!important}';
  h += '.ar-stat-card{display:inline-block;border:1px solid #ddd;border-radius:8px;padding:12px;margin:4px;min-width:120px;text-align:center}';
  h += '.card{border:1px solid #ddd;border-radius:8px;margin-bottom:12px}';
  h += '.card-header{padding:10px 16px;border-bottom:1px solid #ddd;background:#f8f8f8}';
  h += '.card-body{padding:12px 16px}';
  h += '@media print{body{padding:0}}';
  h += '</style></head><body>';
  h += '<h2 style="text-align:center;margin-bottom:4px">مجلس عائلة العوامي</h2>';
  h += '<p style="text-align:center;color:#666;font-size:13px">تاريخ الإصدار: ' + new Date().toLocaleDateString('ar-SA') + '</p>';
  h += '<hr style="border-color:#c8a84b">';
  h += content;
  h += '<hr style="border-color:#c8a84b;margin-top:30px">';
  h += '<p style="text-align:center;font-size:11px;color:#999">صدر من: الموقع الرسمي لعائلة العوامي</p>';
  h += '</body></html>';
  win.document.write(h);
  win.document.close();
  setTimeout(function() { win.print(); }, 500);
}

function exportReportPDF() {
  if (!autoReportData || !autoReportType) {
    toast('ولّد التقرير أولاً', 'error');
    return;
  }
  var params = '?type=' + autoReportType;
  var df = document.getElementById('ar-date-from');
  var dt = document.getElementById('ar-date-to');
  var cm = document.getElementById('ar-committee');
  var pr = document.getElementById('ar-period');
  if (df && df.value) params += '&date_from=' + df.value;
  if (dt && dt.value) params += '&date_to=' + dt.value;
  if (cm && cm.value) params += '&committee_id=' + cm.value;
  if (pr && pr.value) params += '&period_id=' + pr.value;
  if (autoReportType === 'financial' && autoReportExtraItems.length > 0) {
    params += '&extra_items=' + encodeURIComponent(JSON.stringify(autoReportExtraItems));
  }
  window.open('/api/report-pdf.php' + params, '_blank');
}

// =====================================================
// ARCHIVE — أرشيف التقارير
// =====================================================

var archiveList = [];
var archiveLoading = false;
var archiveSearch = '';
var archiveFilterType = '';
var archiveFilterCommittee = '';

function renderArchiveTab() {
  var container = document.getElementById('rt-archive');
  if (!container) return;

  var html = '<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px">';
  html += '<button class="btn btn-primary" onclick="openArchiveModal()">+ رفع تقرير جديد</button>';
  html += '<input type="text" id="archive-search" placeholder="🔍 بحث في العنوان والوصف..." value="' + archiveSearch.replace(/"/g, '&quot;') + '" ';
  html += 'oninput="archiveSearch=this.value; debouncedLoadArchive()" class="form-control" style="flex:1;min-width:200px">';
  html += '</div>';

  html += '<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">';
  html += '<select id="archive-filter-type" onchange="archiveFilterType=this.value; loadArchiveList()" class="form-control" style="min-width:130px">';
  html += '<option value="">كل الأنواع</option>';
  ['مالي', 'إداري', 'محضر اجتماع', 'كشف حساب', 'أخرى'].forEach(function(t) {
    html += '<option value="' + t + '"' + (archiveFilterType === t ? ' selected' : '') + '>' + t + '</option>';
  });
  html += '</select>';

  html += '<select id="archive-filter-committee" onchange="archiveFilterCommittee=this.value; loadArchiveList()" class="form-control" style="min-width:130px">';
  html += '<option value="">كل اللجان</option>';
  var committees = State.getCommittees ? State.getCommittees() : [];
  committees.forEach(function(c) {
    html += '<option value="' + c.id + '"' + (archiveFilterCommittee === c.id ? ' selected' : '') + '>' + c.name + '</option>';
  });
  html += '</select>';
  html += '</div>';

  html += '<div id="archive-list-container">';
  if (archiveLoading) {
    html += '<div style="text-align:center;padding:40px;color:var(--text-muted)">⏳ جاري التحميل...</div>';
  } else if (!archiveList.length) {
    html += '<div class="empty-state"><div class="empty-icon">📁</div><p>لا توجد تقارير في الأرشيف</p></div>';
  } else {
    html += archiveList.map(renderArchiveCard).join('');
  }
  html += '</div>';

  container.innerHTML = html;

  if (!archiveList.length && !archiveLoading) loadArchiveList();
}

function loadArchiveList() {
  archiveLoading = true;
  var listEl = document.getElementById('archive-list-container');
  if (listEl) listEl.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)">⏳ جاري التحميل...</div>';

  var params = { status: 'active' };
  if (archiveFilterType) params.type = archiveFilterType;
  if (archiveFilterCommittee) params.committee_id = archiveFilterCommittee;
  if (archiveSearch) params.search = archiveSearch;

  ReportArchiveAPI.getAll(params)
    .then(function(res) {
      archiveList = res.data || [];
      archiveLoading = false;
      var el = document.getElementById('archive-list-container');
      if (el) {
        el.innerHTML = archiveList.length
          ? archiveList.map(renderArchiveCard).join('')
          : '<div class="empty-state"><div class="empty-icon">📁</div><p>لا توجد تقارير</p></div>';
      }
    })
    .catch(function(err) {
      archiveLoading = false;
      toast('خطأ في تحميل الأرشيف: ' + err.message, 'error');
    });
}

var debouncedLoadArchive = debounce(loadArchiveList, 400);

function renderArchiveCard(item) {
  var typeBadges = {
    'مالي':           { bg: '#dcfce7', color: '#166534', icon: '💰' },
    'إداري':          { bg: '#dbeafe', color: '#1e40af', icon: '📋' },
    'محضر اجتماع':    { bg: '#fef3c7', color: '#92400e', icon: '📝' },
    'كشف حساب':       { bg: '#f3e8ff', color: '#6b21a8', icon: '💳' },
    'أخرى':           { bg: '#f1f5f9', color: '#475569', icon: '📄' },
  };
  var badge = typeBadges[item.report_type] || typeBadges['أخرى'];

  var sourceBadge = '';
  if (item.source === 'import') {
    sourceBadge = '<span style="font-size:10px;background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px">📥 مُستورد من Excel</span>';
    if (item.import_stats) {
      sourceBadge += ' <span style="font-size:10px;color:var(--text-muted)">' + (item.import_stats.imported || 0) + ' معاملة</span>';
    }
  } else if (item.source === 'generated') {
    sourceBadge = '<span style="font-size:10px;background:#dcfce7;color:#166534;padding:2px 8px;border-radius:10px">📈 مُولَّد من النظام</span>';
  }

  var committeeName = '';
  if (item.committee_id) {
    var comm = (State.getCommittees ? State.getCommittees() : []).find(function(c) { return c.id === item.committee_id; });
    if (comm) committeeName = ' • ' + (comm.icon || '') + ' ' + comm.name;
  }

  var dateStr = item.report_date || '';
  try { if (dateStr) dateStr = new Date(item.report_date).toLocaleDateString('ar-SA'); } catch(e) {}

  return '<div class="archive-card">' +
    '<div class="archive-card-header">' +
      '<div class="archive-card-title">' + (item.title || '') + '</div>' +
      '<div class="archive-card-meta">' +
        '<span class="archive-badge" style="background:' + badge.bg + ';color:' + badge.color + '">' + badge.icon + ' ' + item.report_type + '</span>' +
        '<span>📅 ' + dateStr + '</span>' +
        committeeName +
      '</div>' +
      (sourceBadge ? '<div style="margin-top:4px">' + sourceBadge + '</div>' : '') +
      (item.description ? '<div class="archive-card-desc">' + item.description + '</div>' : '') +
    '</div>' +
    '<div class="archive-card-actions">' +
      (item.file_url ? '<a href="' + item.file_url + '" target="_blank" class="btn btn-xs btn-primary">🔗 فتح</a>' : '') +
      '<button class="btn btn-xs btn-outline" onclick="openArchiveModal(\'' + item.id + '\')">✏️ تعديل</button>' +
      '<button class="btn btn-xs" style="color:var(--danger)" onclick="deleteArchiveItem(\'' + item.id + '\')">🗑️</button>' +
    '</div>' +
  '</div>';
}

function openArchiveModal(editId) {
  document.getElementById('archive-edit-id').value = '';
  document.getElementById('archive-title').value = '';
  document.getElementById('archive-type').value = 'أخرى';
  document.getElementById('archive-date').value = new Date().toISOString().split('T')[0];
  document.getElementById('archive-url').value = '';
  document.getElementById('archive-desc').value = '';
  document.getElementById('archive-committee').value = '';
  document.getElementById('archive-period').value = '';

  // ملء اللجان
  var commSelect = document.getElementById('archive-committee');
  commSelect.innerHTML = '<option value="">— بدون —</option>';
  (State.getCommittees ? State.getCommittees() : []).forEach(function(c) {
    commSelect.innerHTML += '<option value="' + c.id + '">' + c.name + '</option>';
  });

  // ملء الفترات
  var perSelect = document.getElementById('archive-period');
  perSelect.innerHTML = '<option value="">— بدون —</option>';
  (State.getPeriods ? State.getPeriods() : []).forEach(function(p) {
    perSelect.innerHTML += '<option value="' + p.id + '">' + p.name + '</option>';
  });

  if (editId) {
    document.getElementById('archive-modal-title').textContent = 'تعديل التقرير';
    var item = archiveList.find(function(r) { return r.id === editId; });
    if (item) {
      document.getElementById('archive-edit-id').value = item.id;
      document.getElementById('archive-title').value = item.title;
      document.getElementById('archive-type').value = item.report_type;
      document.getElementById('archive-date').value = item.report_date;
      document.getElementById('archive-url').value = item.file_url || '';
      document.getElementById('archive-desc').value = item.description || '';
      document.getElementById('archive-committee').value = item.committee_id || '';
      document.getElementById('archive-period').value = item.period_id || '';
    }
  } else {
    document.getElementById('archive-modal-title').textContent = 'إضافة تقرير جديد';
  }

  openModal('modal-archive');
}

function saveArchiveItem() {
  var title = document.getElementById('archive-title').value.trim();
  var reportDate = document.getElementById('archive-date').value;
  var fileUrl = document.getElementById('archive-url').value.trim();

  if (!title) { toast('العنوان مطلوب', 'error'); return; }
  if (!reportDate) { toast('التاريخ مطلوب', 'error'); return; }
  if (!fileUrl) { toast('رابط الملف مطلوب', 'error'); return; }

  var data = {
    title: title,
    report_type: document.getElementById('archive-type').value,
    report_date: reportDate,
    file_url: fileUrl,
    file_type: guessFileType(fileUrl),
    description: document.getElementById('archive-desc').value.trim(),
    committee_id: document.getElementById('archive-committee').value || null,
    period_id: document.getElementById('archive-period').value || null,
  };

  var editId = document.getElementById('archive-edit-id').value;
  var btn = document.getElementById('archive-save-btn');
  btn.disabled = true;
  btn.textContent = 'جاري الحفظ...';

  var promise = editId
    ? ReportArchiveAPI.update(editId, data)
    : ReportArchiveAPI.save(data);

  promise.then(function() {
    toast(editId ? 'تم تحديث التقرير' : 'تم إضافة التقرير');
    closeModal('modal-archive');
    loadArchiveList();
  }).catch(function(err) {
    toast('خطأ: ' + err.message, 'error');
  }).finally(function() {
    btn.disabled = false;
    btn.textContent = '💾 حفظ';
  });
}

function guessFileType(url) {
  var lower = url.toLowerCase();
  if (lower.includes('.pdf')) return 'pdf';
  if (lower.includes('.xlsx') || lower.includes('.xls')) return 'xlsx';
  if (lower.includes('.csv')) return 'csv';
  if (lower.includes('.docx') || lower.includes('.doc')) return 'docx';
  if (lower.includes('drive.google.com')) return 'gdrive';
  return 'link';
}

function deleteArchiveItem(id) {
  if (!confirm('حذف هذا التقرير نهائياً؟')) return;
  ReportArchiveAPI.remove(id)
    .then(function() {
      toast('تم الحذف');
      loadArchiveList();
    })
    .catch(function(err) { toast('خطأ: ' + err.message, 'error'); });
}

// =====================================================
// IMPORT — استيراد معاملات من Excel
// =====================================================

function importBuildStepper(step) {
  var labels = ['رفع', 'ربط', 'مراجعة', 'إدخال'];
  var html = '<div class="import-stepper">';
  for (var i = 1; i <= 4; i++) {
    if (i > 1) html += '<div class="import-stepper-line' + (i <= step ? ' done' : '') + '"></div>';
    html += '<div class="import-stepper-dot' + (i === step ? ' active' : (i < step ? ' done' : '')) + '">' + i + '</div>';
  }
  html += '</div>';
  html += '<div style="display:flex;justify-content:center;gap:40px;margin-bottom:20px;font-size:12px;color:var(--text-muted)">';
  labels.forEach(function(l, i) {
    var cls = (i + 1) === step ? 'color:var(--green);font-weight:700' : '';
    html += '<span style="' + cls + '">' + l + '</span>';
  });
  html += '</div>';
  return html;
}

function renderImportTab() {
  var container = document.getElementById('rt-import');
  if (!container) return;
  if (importStep === 1) renderImportStep1(container);
  else if (importStep === 2) renderImportStep2(container);
  else if (importStep === 3) renderImportStep3(container);
  else if (importStep === 4) renderImportStep4(container);
}

// ---- Step 1: رفع الملف ----
function renderImportStep1(container) {
  var html = importBuildStepper(1);
  html += '<div class="card"><div class="card-body" style="padding:32px">';
  html += '<div class="import-upload-area" id="import-upload-area" onclick="document.getElementById(\'import-file-input\').click()">';
  html += '<input type="file" id="import-file-input" accept=".xlsx,.xls,.csv" style="display:none" onchange="importProcessFile(this.files[0])">';
  html += '<div style="font-size:56px;margin-bottom:12px">📊</div>';
  html += '<div style="font-size:17px;font-weight:700;margin-bottom:8px">اسحب ملف Excel هنا أو انقر للاختيار</div>';
  html += '<div style="font-size:13px;color:var(--text-muted)">يدعم: .xlsx, .xls, .csv (حد أقصى 10 ميجابايت)</div>';
  html += '</div>';
  html += '</div></div>';

  if (importWorkbook) {
    html += '<div style="text-align:center;margin-top:16px">';
    html += '<button class="btn btn-outline" onclick="importReset()">🔄 بدء استيراد جديد</button>';
    html += '</div>';
  }

  container.innerHTML = html;

  // Drag & drop
  var area = document.getElementById('import-upload-area');
  if (area) {
    area.addEventListener('dragover', function(e) { e.preventDefault(); e.stopPropagation(); this.classList.add('drag-over'); });
    area.addEventListener('dragleave', function(e) { e.preventDefault(); e.stopPropagation(); this.classList.remove('drag-over'); });
    area.addEventListener('drop', function(e) {
      e.preventDefault(); e.stopPropagation(); this.classList.remove('drag-over');
      if (e.dataTransfer.files.length > 0) importProcessFile(e.dataTransfer.files[0]);
    });
  }
}

function importProcessFile(file) {
  if (!file) return;
  if (!file.name.match(/\.(xlsx|xls|csv)$/i)) {
    toast('نوع الملف غير مدعوم. يدعم: xlsx, xls, csv', 'error');
    return;
  }
  if (file.size > 10 * 1024 * 1024) {
    toast('حجم الملف يتجاوز 10 ميجابايت', 'error');
    return;
  }
  importFileName = file.name;
  var reader = new FileReader();
  reader.onload = function(e) {
    try {
      var data = new Uint8Array(e.target.result);
      importWorkbook = XLSX.read(data, { type: 'array', cellDates: true });
      importReadSheet(0);
    } catch (err) {
      toast('خطأ في قراءة الملف: ' + err.message, 'error');
    }
  };
  reader.readAsArrayBuffer(file);
}

function importReadSheet(sheetIndex) {
  var sheetName = importWorkbook.SheetNames[sheetIndex];
  var sheet = importWorkbook.Sheets[sheetName];
  var json = XLSX.utils.sheet_to_json(sheet, { defval: '' });
  if (!json.length) {
    toast('الملف فارغ أو لا يحتوي بيانات', 'error');
    return;
  }
  importRawData = json;
  importStep = 2;
  renderImportTab();
}

// ---- Step 2: ربط الأعمدة ----
function renderImportStep2(container) {
  var cols = Object.keys(importRawData[0]);
  var html = importBuildStepper(2);

  // Sheet selector
  if (importWorkbook.SheetNames.length > 1) {
    html += '<div style="margin-bottom:16px"><label class="form-label">اختر الصفحة:</label>';
    html += '<select class="form-control" style="max-width:300px" onchange="importReadSheet(parseInt(this.value))">';
    importWorkbook.SheetNames.forEach(function(name, i) {
      html += '<option value="' + i + '">' + name + '</option>';
    });
    html += '</select></div>';
  }

  html += '<div class="card"><div class="card-header"><div class="card-title">📄 ' + importFileName + ' — ' + importRawData.length + ' صف</div></div>';
  html += '<div class="card-body">';

  // Column mapping
  html += '<div style="background:var(--bg);border-radius:12px;padding:16px;margin-bottom:20px">';
  html += '<div style="font-weight:700;font-size:13px;margin-bottom:12px">🔗 ربط الأعمدة</div>';
  html += '<div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">';

  var fields = [
    { id: 'import-col-desc', label: 'الوصف / البيان *', required: true },
    { id: 'import-col-amount', label: 'المبلغ *', required: true },
    { id: 'import-col-date', label: 'التاريخ' },
    { id: 'import-col-type', label: 'النوع (إيراد/مصروف)' }
  ];

  fields.forEach(function(f) {
    html += '<div class="form-group"><label class="form-label">' + f.label + '</label>';
    html += '<select id="' + f.id + '" class="form-control">';
    html += '<option value="">— لا يوجد —</option>';
    cols.forEach(function(c) { html += '<option value="' + c + '">' + c + '</option>'; });
    html += '</select></div>';
  });

  html += '</div>';
  html += '<div id="import-detect-info" style="margin-top:10px;font-size:12px;color:var(--text-muted)"></div>';
  html += '</div>';

  // Preview
  html += '<div style="font-weight:700;font-size:13px;margin-bottom:8px">📋 معاينة أول 5 صفوف</div>';
  html += '<div class="table-wrap" style="max-height:300px;overflow:auto"><table class="import-review-table"><thead><tr>';
  cols.forEach(function(c) { html += '<th>' + c + '</th>'; });
  html += '</tr></thead><tbody>';
  importRawData.slice(0, 5).forEach(function(row) {
    html += '<tr>';
    cols.forEach(function(c) { html += '<td>' + (row[c] != null ? String(row[c]).substring(0, 50) : '') + '</td>'; });
    html += '</tr>';
  });
  html += '</tbody></table></div>';

  // Buttons
  html += '<div style="display:flex;gap:10px;margin-top:20px;justify-content:flex-end">';
  html += '<button class="btn btn-outline" onclick="importStep=1;renderImportTab()">← رجوع</button>';
  html += '<button class="btn btn-primary" onclick="importPrepareReview()">التالي: مراجعة المعاملات →</button>';
  html += '</div>';

  html += '</div></div>';
  container.innerHTML = html;

  // Auto-detect columns
  importAutoDetectColumns(cols);
}

function importAutoDetectColumns(cols) {
  var descWords = ['الوصف', 'البيان', 'بيان', 'description', 'desc', 'وصف', 'تفاصيل', 'ملاحظة'];
  var amountWords = ['المبلغ', 'القيمة', 'amount', 'value', 'مبلغ', 'قيمة', 'مدين', 'دائن'];
  var dateWords = ['التاريخ', 'date', 'تاريخ'];
  var typeWords = ['النوع', 'type', 'نوع'];

  var detected = 0;
  function tryDetect(selectId, keywords) {
    var el = document.getElementById(selectId);
    if (!el) return;
    for (var i = 0; i < cols.length; i++) {
      var cl = cols[i].toLowerCase().trim();
      for (var j = 0; j < keywords.length; j++) {
        if (cl.includes(keywords[j].toLowerCase())) {
          el.value = cols[i];
          detected++;
          return;
        }
      }
    }
  }

  tryDetect('import-col-desc', descWords);
  tryDetect('import-col-amount', amountWords);
  tryDetect('import-col-date', dateWords);
  tryDetect('import-col-type', typeWords);

  var info = document.getElementById('import-detect-info');
  if (info) info.textContent = '🔍 تم كشف ' + detected + '/4 أعمدة تلقائياً';
}

function importPrepareReview() {
  var descCol = document.getElementById('import-col-desc').value;
  var amountCol = document.getElementById('import-col-amount').value;
  var dateCol = document.getElementById('import-col-date').value;
  var typeCol = document.getElementById('import-col-type').value;

  if (!descCol || !amountCol) {
    toast('يرجى تحديد عمود الوصف والمبلغ', 'error');
    return;
  }

  importData = [];
  importSelectedRows = new Set();

  importRawData.forEach(function(row, i) {
    var desc = String(row[descCol] || '').trim();
    var amountStr = String(row[amountCol] || '0');
    var amount = parseFloat(amountStr.replace(/[^\d.\-]/g, '')) || 0;
    var dateStr = dateCol ? row[dateCol] : '';
    var typeStr = typeCol ? String(row[typeCol] || '') : '';

    // Parse date
    var date = '';
    if (dateStr) {
      if (dateStr instanceof Date) {
        date = dateStr.toISOString().split('T')[0];
      } else {
        var ds = String(dateStr);
        if (!isNaN(Date.parse(ds))) {
          date = new Date(ds).toISOString().split('T')[0];
        } else if (ds.match(/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}$/)) {
          var parts = ds.split(/[\/\-]/);
          if (parts[2] && parts[2].length === 4) {
            date = parts[2] + '-' + parts[1].padStart(2, '0') + '-' + parts[0].padStart(2, '0');
          }
        }
      }
    }
    if (!date) date = new Date().toISOString().split('T')[0];

    // Detect type
    var type = '';
    if (typeStr) {
      var lt = typeStr.toLowerCase();
      if (lt.includes('إيراد') || lt.includes('دخل') || lt.includes('income') || lt.includes('دائن') || lt.includes('credit')) type = 'إيراد';
      else if (lt.includes('مصروف') || lt.includes('expense') || lt.includes('مدين') || lt.includes('debit')) type = 'مصروف';
    }
    if (!type) {
      var ld = desc.toLowerCase();
      if (ld.includes('اشتراك') || ld.includes('تبرع') || ld.includes('إيراد') || ld.includes('دخل')) type = 'إيراد';
    }

    importData.push({
      index: i,
      description: desc,
      amount: Math.abs(amount),
      date: date,
      type: type,
      category: '',
      committee_id: '',
      valid: desc.length > 0 && amount !== 0
    });

    if (desc.length > 0 && amount !== 0) importSelectedRows.add(i);
  });

  importStep = 3;
  renderImportTab();
}

// ---- Step 3: مراجعة وتعديل ----
function renderImportStep3(container) {
  var committees = State.getCommittees ? State.getCommittees() : [];
  var html = importBuildStepper(3);

  // Bulk bar
  html += '<div class="import-bulk-bar">';
  html += '<label style="display:flex;align-items:center;gap:4px"><input type="checkbox" id="import-select-all" onchange="importToggleAll(this.checked)"> تحديد الكل</label>';
  html += '<span id="import-selected-count" style="font-weight:700">0 محدد</span>';
  html += '<select id="import-bulk-type" class="form-control" style="width:auto;font-size:12px"><option value="">النوع...</option><option value="إيراد">إيراد</option><option value="مصروف">مصروف</option></select>';
  html += '<select id="import-bulk-category" class="form-control" style="width:auto;font-size:12px"><option value="">الفئة...</option>';
  IMPORT_CATEGORIES.forEach(function(c) { html += '<option value="' + c + '">' + c + '</option>'; });
  html += '</select>';
  html += '<select id="import-bulk-committee" class="form-control" style="width:auto;font-size:12px"><option value="">اللجنة...</option>';
  committees.forEach(function(c) { html += '<option value="' + c.id + '">' + c.name + '</option>'; });
  html += '</select>';
  html += '<button class="btn btn-primary btn-sm" onclick="importApplyBulk()">✓ تطبيق</button>';
  html += '<button class="btn btn-sm" style="color:var(--danger)" onclick="importDeleteSelected()">🗑️ حذف</button>';
  html += '</div>';

  // Table
  html += '<div class="table-wrap" style="max-height:500px;overflow:auto"><table class="import-review-table"><thead><tr>';
  html += '<th style="width:36px">✓</th><th>التاريخ</th><th>الوصف</th><th>المبلغ</th><th>النوع</th><th>الفئة</th><th>اللجنة</th>';
  html += '</tr></thead><tbody>';

  importData.forEach(function(row, idx) {
    var isSelected = importSelectedRows.has(idx);
    var cls = isSelected ? 'selected' : '';
    if (!row.valid) cls += ' invalid';
    html += '<tr class="' + cls + '" data-idx="' + idx + '">';
    html += '<td><input type="checkbox" class="import-row-check" data-idx="' + idx + '"' + (isSelected ? ' checked' : '') + ' onchange="importToggleRow(' + idx + ',this.checked)"></td>';
    html += '<td><input type="date" value="' + row.date + '" onchange="importData[' + idx + '].date=this.value" class="import-edit-input" style="width:130px"></td>';
    html += '<td><input type="text" value="' + (row.description || '').replace(/"/g, '&quot;') + '" onchange="importData[' + idx + '].description=this.value.trim()" class="import-edit-input import-edit-desc"></td>';
    html += '<td><input type="number" value="' + row.amount + '" min="0" step="0.01" onchange="importData[' + idx + '].amount=parseFloat(this.value)||0;importUpdateSummary()" class="import-edit-input import-edit-amount"></td>';

    // Type select
    html += '<td><select onchange="importData[' + idx + '].type=this.value;importUpdateSummary()" class="import-edit-select">';
    html += '<option value="">— اختر —</option>';
    html += '<option value="إيراد"' + (row.type === 'إيراد' ? ' selected' : '') + '>إيراد</option>';
    html += '<option value="مصروف"' + (row.type === 'مصروف' ? ' selected' : '') + '>مصروف</option>';
    html += '</select></td>';

    // Category select
    html += '<td><select onchange="importData[' + idx + '].category=this.value" class="import-edit-select">';
    html += '<option value="">— بدون —</option>';
    IMPORT_CATEGORIES.forEach(function(c) {
      html += '<option value="' + c + '"' + (row.category === c ? ' selected' : '') + '>' + c + '</option>';
    });
    html += '</select></td>';

    // Committee select
    html += '<td><select onchange="importData[' + idx + '].committee_id=this.value" class="import-edit-select">';
    html += '<option value="">عام</option>';
    committees.forEach(function(c) {
      html += '<option value="' + c.id + '"' + (row.committee_id === c.id ? ' selected' : '') + '>' + c.name + '</option>';
    });
    html += '</select></td>';

    html += '</tr>';
  });

  html += '</tbody></table></div>';

  // Summary bar
  html += '<div class="import-summary-bar" id="import-summary-bar"></div>';

  // Buttons
  html += '<div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">';
  html += '<button class="btn btn-outline" onclick="importStep=2;renderImportTab()">← رجوع لربط الأعمدة</button>';
  html += '<button class="btn btn-primary" onclick="importValidateAndConfirm()">إدخال في النظام →</button>';
  html += '</div>';

  container.innerHTML = html;
  importUpdateSummary();
}

function importToggleRow(idx, checked) {
  if (checked) importSelectedRows.add(idx);
  else importSelectedRows.delete(idx);
  var tr = document.querySelector('tr[data-idx="' + idx + '"]');
  if (tr) tr.classList.toggle('selected', checked);
  importUpdateSummary();
}

function importToggleAll(checked) {
  importData.forEach(function(row, idx) {
    if (row.valid) {
      if (checked) importSelectedRows.add(idx);
      else importSelectedRows.delete(idx);
    }
  });
  document.querySelectorAll('.import-row-check').forEach(function(cb) {
    var idx = parseInt(cb.dataset.idx);
    if (importData[idx] && importData[idx].valid) cb.checked = checked;
  });
  document.querySelectorAll('.import-review-table tbody tr').forEach(function(tr) {
    var idx = parseInt(tr.dataset.idx);
    if (importData[idx] && importData[idx].valid) tr.classList.toggle('selected', checked);
  });
  importUpdateSummary();
}

function importApplyBulk() {
  var bulkType = document.getElementById('import-bulk-type').value;
  var bulkCat = document.getElementById('import-bulk-category').value;
  var bulkComm = document.getElementById('import-bulk-committee').value;
  if (!bulkType && !bulkCat && !bulkComm) {
    toast('اختر قيمة لتطبيقها', 'error');
    return;
  }
  var count = 0;
  importSelectedRows.forEach(function(idx) {
    if (bulkType) importData[idx].type = bulkType;
    if (bulkCat) importData[idx].category = bulkCat;
    if (bulkComm) importData[idx].committee_id = bulkComm;
    count++;
  });
  toast('تم التطبيق على ' + count + ' صف');
  renderImportStep3(document.getElementById('rt-import'));
}

function importDeleteSelected() {
  if (importSelectedRows.size === 0) { toast('لم يتم تحديد أي صف', 'error'); return; }
  var newData = [];
  importData.forEach(function(row, idx) {
    if (!importSelectedRows.has(idx)) newData.push(row);
  });
  importData = newData;
  importSelectedRows = new Set();
  // Re-index
  importData.forEach(function(row, i) { row.index = i; });
  renderImportStep3(document.getElementById('rt-import'));
}

function importUpdateSummary() {
  var bar = document.getElementById('import-summary-bar');
  if (!bar) return;
  var totalIncome = 0, totalExpense = 0;
  importSelectedRows.forEach(function(idx) {
    var row = importData[idx];
    if (!row) return;
    if (row.type === 'إيراد') totalIncome += row.amount;
    else if (row.type === 'مصروف') totalExpense += row.amount;
  });
  var net = totalIncome - totalExpense;
  var countEl = document.getElementById('import-selected-count');
  if (countEl) countEl.textContent = importSelectedRows.size + ' محدد';
  bar.innerHTML =
    '<span>محدد: <strong>' + importSelectedRows.size + '</strong> من <strong>' + importData.length + '</strong></span>' +
    '<span>إيرادات: <strong style="color:var(--green)">' + fmt(totalIncome) + '</strong> ريال</span>' +
    '<span>مصروفات: <strong style="color:var(--danger)">' + fmt(totalExpense) + '</strong> ريال</span>' +
    '<span>صافي: <strong style="color:' + (net >= 0 ? 'var(--green)' : 'var(--danger)') + '">' + (net >= 0 ? '+' : '') + fmt(net) + '</strong> ريال</span>';
}

function importValidateAndConfirm() {
  var missing = [];
  importSelectedRows.forEach(function(idx) {
    var row = importData[idx];
    if (!row.type) missing.push(idx + 1);
  });
  if (missing.length > 0) {
    toast('الصفوف التالية تحتاج تحديد النوع (إيراد/مصروف): ' + missing.slice(0, 5).join('، ') + (missing.length > 5 ? '...' : ''), 'error');
    return;
  }
  if (importSelectedRows.size === 0) {
    toast('لم يتم تحديد أي معاملة', 'error');
    return;
  }
  importStep = 4;
  renderImportTab();
}

// ---- Step 4: تأكيد وإدخال ----
function renderImportStep4(container) {
  var html = importBuildStepper(4);

  var incomeCount = 0, expenseCount = 0, incomeTotal = 0, expenseTotal = 0;
  importSelectedRows.forEach(function(idx) {
    var row = importData[idx];
    if (row.type === 'إيراد') { incomeCount++; incomeTotal += row.amount; }
    else { expenseCount++; expenseTotal += row.amount; }
  });

  html += '<div class="import-confirm-card">';
  html += '<div style="font-size:28px;margin-bottom:12px">⚠️</div>';
  html += '<div style="font-size:18px;font-weight:700;margin-bottom:16px">تأكيد الإدخال</div>';
  html += '<div style="font-size:14px;line-height:2;text-align:right;margin-bottom:20px">';
  html += 'سيتم إدخال <strong>' + importSelectedRows.size + '</strong> معاملة في النظام:<br>';
  html += '• إيرادات: <strong>' + incomeCount + '</strong> معاملة (<strong style="color:var(--green)">' + fmt(incomeTotal) + '</strong> ريال)<br>';
  html += '• مصروفات: <strong>' + expenseCount + '</strong> معاملة (<strong style="color:var(--danger)">' + fmt(expenseTotal) + '</strong> ريال)<br>';
  html += '• الملف: <strong>' + importFileName + '</strong>';
  html += '</div>';
  html += '<div style="display:flex;gap:10px;justify-content:center">';
  html += '<button class="btn btn-outline" onclick="importStep=3;renderImportTab()">← رجوع للمراجعة</button>';
  html += '<button class="btn btn-primary" id="import-execute-btn" onclick="importExecute()">✅ إدخال في النظام</button>';
  html += '</div>';
  html += '</div>';

  container.innerHTML = html;
}

function importExecute() {
  var btn = document.getElementById('import-execute-btn');
  if (btn) { btn.disabled = true; btn.textContent = 'جاري الإدخال...'; }

  var transactions = [];
  importSelectedRows.forEach(function(idx) {
    var row = importData[idx];
    transactions.push({
      type: row.type,
      amount: row.amount,
      description: row.description,
      tx_date: row.date,
      category: row.category || 'أخرى',
      committee_id: row.committee_id || ''
    });
  });

  apiFetch('/api/transactions.php?bulk=1', {
    method: 'POST',
    body: JSON.stringify({ transactions: transactions })
  }).then(function(res) {
    // Archive the import
    var incomeTotal = 0, expenseTotal = 0;
    transactions.forEach(function(tx) {
      if (tx.type === 'إيراد') incomeTotal += tx.amount;
      else expenseTotal += tx.amount;
    });

    return ReportArchiveAPI.save({
      title: 'استيراد: ' + importFileName,
      report_type: 'كشف حساب',
      source: 'import',
      description: 'تم استيراد ' + res.inserted + ' معاملة من هذا الملف' +
        (res.duplicates_skipped > 0 ? ' (تم تجاهل ' + res.duplicates_skipped + ' مكررة)' : ''),
      report_date: new Date().toISOString().split('T')[0],
      import_stats: {
        file_name: importFileName,
        total_rows: importData.length,
        imported: res.inserted,
        skipped: res.duplicates_skipped || 0,
        total_income: incomeTotal,
        total_expense: expenseTotal
      }
    }).then(function() { return res; });
  }).then(function(res) {
    importShowResult(res);
    // Update State
    if (res.data && res.data.length) {
      var txs = State.getTransactions();
      res.data.forEach(function(row) { txs.push(row); });
    }
  }).catch(function(err) {
    toast('خطأ في الإدخال: ' + err.message, 'error');
    if (btn) { btn.disabled = false; btn.textContent = '✅ إدخال في النظام'; }
  });
}

function importShowResult(res) {
  var container = document.getElementById('rt-import');
  if (!container) return;

  var html = '<div class="import-result-card">';
  html += '<div style="font-size:48px;margin-bottom:12px">✅</div>';
  html += '<div style="font-size:20px;font-weight:700;margin-bottom:16px;color:var(--green)">تم بنجاح!</div>';
  html += '<div style="font-size:14px;line-height:2;text-align:right;margin-bottom:20px">';
  html += '• تم إدخال: <strong>' + res.inserted + '</strong> معاملة<br>';
  if (res.duplicates_skipped > 0) {
    html += '• تكرارات تم تجاهلها: <strong>' + res.duplicates_skipped + '</strong><br>';
  }
  html += '• الملف محفوظ في الأرشيف';
  html += '</div>';
  html += '<div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">';
  html += '<button class="btn btn-primary" onclick="showPage(\'budget\')">📊 عرض الميزانية</button>';
  html += '<button class="btn btn-outline" onclick="importReset()">📥 استيراد آخر</button>';
  html += '</div>';
  html += '</div>';

  container.innerHTML = html;
}

function importReset() {
  importWorkbook = null;
  importRawData = null;
  importFileName = '';
  importData = [];
  importSelectedRows = new Set();
  importStep = 1;
  renderImportTab();
}


function renderReports(){
  const expByCat={}; State.getTransactions().filter(t=>t.type==='مصروف').forEach(t=>{ expByCat[t.category]=(expByCat[t.category]||0)+t.amount; });
  const colors=['#47915C','#1B3456','#c8a84b','#e67e22','#8e44ad','#c0392b','#117a65','#2980b9'];
  const total=Object.values(expByCat).reduce((s,v)=>s+v,0);
  const cats=Object.entries(expByCat);
  const donutEl=document.getElementById('report-donut');
  if(!cats.length){donutEl.innerHTML='<div class="empty-state"><div class="empty-icon">📊</div><p>لا بيانات</p></div>';}
  else{ const circ=2*Math.PI*50; let offset=0; const segs=cats.map(([cat,val],i)=>{ const pct=val/total; const dash=pct*circ; const seg=`<circle cx="65" cy="65" r="50" fill="none" stroke="${colors[i%colors.length]}" stroke-width="18" stroke-dasharray="${dash} ${circ-dash}" stroke-dashoffset="${-offset*circ}" transform="rotate(-90 65 65)"/>`; offset+=pct; return seg; }).join(''); donutEl.innerHTML=`<div style="display:flex;align-items:center;gap:14px"><svg width="130" height="130" viewBox="0 0 130 130"><circle cx="65" cy="65" r="50" fill="none" stroke="#e4ede6" stroke-width="18"/>${segs}<text x="65" y="68" text-anchor="middle" fill="var(--green-dark)" font-size="10" font-weight="700" font-family="Cairo">${fmt(total)}</text><text x="65" y="80" text-anchor="middle" fill="var(--text-muted)" font-size="8" font-family="Cairo">ريال</text></svg><div>${cats.map(([cat,val],i)=>`<div style="display:flex;align-items:center;gap:6px;margin-bottom:5px"><div style="width:10px;height:10px;border-radius:50%;background:${colors[i%colors.length]}"></div><div><div style="font-size:12px;font-weight:600">${cat}</div><div style="font-size:11px;color:var(--text-muted)">${fmt(val)} (${Math.round(val/total*100)}%)</div></div></div>`).join('')}</div></div>`; }
  const income=State.getTransactions().filter(t=>t.type==='إيراد').reduce((s,t)=>s+t.amount,0);
  const expense=State.getTransactions().filter(t=>t.type==='مصروف').reduce((s,t)=>s+t.amount,0);
  const mx=Math.max(income,expense,1);
  document.getElementById('report-compare').innerHTML=`<div style="margin-bottom:10px"><div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:12px">الإيرادات</span><span style="font-weight:700;color:var(--green)">${fmt(income)} ريال</span></div><div class="progress-bar"><div class="progress-fill" style="width:${income/mx*100}%"></div></div></div><div style="margin-bottom:10px"><div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:12px">المصاريف</span><span style="font-weight:700;color:var(--danger)">${fmt(expense)} ريال</span></div><div class="progress-bar"><div class="progress-fill" style="width:${expense/mx*100}%;background:linear-gradient(90deg,var(--danger),#e74c3c)"></div></div></div><div style="border-top:1px solid var(--border);padding-top:10px;display:flex;justify-content:space-between"><span style="font-weight:600">صافي الرصيد</span><span style="font-weight:900;font-size:18px;color:${income-expense>=0?'var(--green)':'var(--danger)'}">${fmt(income-expense)} ريال</span></div>`;
  const byStatus={}; State.getMembers().forEach(m=>{ byStatus[m.status]=(byStatus[m.status]||0)+1; });
  document.getElementById('report-members').innerHTML=`<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px">${Object.entries(byStatus).map(([s,v])=>`<div style="background:var(--bg);border-radius:8px;padding:10px;text-align:center"><div style="font-size:18px;font-weight:900;color:var(--green-dark)">${v}</div><div style="font-size:11px;color:var(--text-muted)">${s}</div></div>`).join('')}</div>`;
  const ptot={}; State.getPayments().filter(p=>p.status==='مدفوع').forEach(p=>{ ptot[p.memberId]=(ptot[p.memberId]||0)+p.amount; });
  const top=Object.entries(ptot).sort((a,b)=>b[1]-a[1]).slice(0,5);
  document.getElementById('report-top-payers').innerHTML=top.length?top.map(([id,tot],i)=>{ const m=State.getMembers().find(x=>x.id===id); if(!m) return ''; return `<div style="display:flex;align-items:center;gap:10px;padding:10px 18px;border-bottom:1px solid #f0ebe0"><div style="font-size:15px;color:var(--accent);width:20px;font-weight:900">${i+1}</div><div class="avatar" style="background:${avColor(m.name)}">${avInit(m.name)}</div><div style="flex:1"><div style="font-weight:600;font-size:13px">${m.name}</div><div style="font-size:11px;color:var(--text-muted)">${m.family}</div></div><div style="font-weight:700;color:var(--green)">${fmt(tot)} ريال</div></div>`; }).join(''):'<div class="empty-state"><div class="empty-icon">🏆</div><p>لا بيانات</p></div>';
  document.getElementById('report-committees-tbody').innerHTML=State.getCommittees().map(c=>{ const mems = c.member_count || (State.getCommitteeMembers()[c.id] || []).length || 0; const evs=State.getEvents().filter(e=>e.committeeId===c.id).length; const spent=State.getTransactions().filter(t=>t.committee===c.id&&t.type==='مصروف').reduce((s,t)=>s+t.amount,0); return `<tr><td>${c.icon} ${c.name} ${c.advisory?'<span class="badge badge-purple">استشارية</span>':''}</td><td>${mems}</td><td>${evs}</td><td style="font-weight:700;color:var(--danger)">${fmt(spent)} ريال</td></tr>`; }).join('');
}

function switchTab(id,el){ document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active')); document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active')); document.getElementById(id).classList.add('active'); el.classList.add('active'); renderReports(); }

// =================== SAMPLE DATA ===================
function loadSampleData(){
  if(State.getMembers().length) return;
  const names=[['منصور علي العوامي','العوامي','0501111111'],['حسين عبدالحميد العوامي','العوامي','0502222222'],['عبدالله عماد العوامي','العوامي','0503333333'],['محمود حسن العوامي','العوامي','0504444444'],['راضي ابراهيم العوامي','العوامي','0505555555'],['رضا حسين العوامي','العوامي','0506666666'],['مجتبى سلمان العوامي','العوامي','0507777777'],['حسن علي العوامي','العوامي','0508888888'],['أحمد غازي العوامي','العوامي','0509999999'],['عماد عبدالحميد العوامي','العوامي','0501010101']];
  names.forEach(([name,family,phone],i)=>{ State.getMembers().push({id:'m'+i,name,family,phone,idNum:`1${100000000+i}`,joinDate:`2023-0${(i%9)+1}-01`,status:i===7?'غير مشترك':'مشترك',notes:''}); });
  const p={id:'p1',name:'الدورة الأولى 2025',feeAmount:400,start:'2025-01-01',end:'2025-06-30'};
  State.getPeriods().push(p);
  State.getMembers().forEach((m,i)=>{ const s=i<7?'مدفوع':'لم يدفع'; State.getPayments().push({id:'pay'+i,memberId:m.id,periodId:p.id,amount:s==='مدفوع'?400:0,required:400,date:s==='مدفوع'?`2025-0${(i%6)+1}-15`:'',method:'تحويل بنكي',status:s,notes:''}); });
  [{type:'إيراد',amount:2800,category:'رسوم الأعضاء',committee:'',desc:'رسوم الأعضاء الدورة الأولى 2025',date:'2025-01-15'},{type:'مصروف',amount:8000,category:'رحلة العمرة',committee:'c1',desc:'تكاليف رحلة العمرة الرجبية',date:'2025-02-10'},{type:'مصروف',amount:3200,category:'غداء العيد',committee:'c2',desc:'غداء عيد الفطر',date:'2025-04-11'},{type:'إيراد',amount:1200,category:'تبرعات',committee:'',desc:'تبرع أحد الأعضاء',date:'2025-01-20'},{type:'مصروف',amount:500,category:'مصاريف إدارية',committee:'',desc:'مصاريف إدارية',date:'2025-03-05'}].forEach((t,i)=>State.getTransactions().push({id:'tx'+i,...t}));
  [{name:'رحلة العمرة الرجبية 2025',committeeId:'c1',status:'مكتمل',date:'2025-02-10',budget:8000,participants:15,lead:'عبدالله عماد',icon:'🕋',images:[]},{name:'غداء عيد الفطر',committeeId:'c2',status:'مكتمل',date:'2025-04-11',budget:3200,participants:40,lead:'محمود حسن',icon:'🍖',images:[]},{name:'المسابقة الرمضانية',committeeId:'c3',status:'قادم',date:'2025-03-01',budget:2000,participants:30,icon:'🌙',images:[]},{name:'رحلة الباحة الترفيهية',committeeId:'c4',status:'قادم',date:'2025-09-01',budget:5000,participants:25,icon:'🎡',images:[]},{name:'ليلة القدر',committeeId:'c5',status:'قادم',date:'2025-04-25',budget:1500,participants:35,icon:'✨',images:[]}].forEach((e,i)=>State.getEvents().push({id:'ev'+i,...e,type:'أخرى',notes:''}));
  State.setCommitteeMembers({'c1':['m2','m0'],'c2':['m3','m4'],'c3':['m0','m6'],'c4':['m3'],'c8':['m1','m2'],'c10':['m0','m7']});
  State.getPolls().push({id:'poll1',title:'هل توافق على رفع قيمة الاشتراك من 300 إلى 400 ريال؟',options:[{text:'نعم، أوافق',votes:['u1','u2','u3','u4']},{text:'لا، أرفض',votes:['u5']},{text:'محايد',votes:['u6']}],committee:'',end:'2025-07-01',active:true,created:'2025-06-01'});
  
  // Sample next meeting
  const nextMonth = new Date();
  nextMonth.setMonth(nextMonth.getMonth() + 1);
  nextMonth.setDate(15);
  State.setNextMeetingObj({date:nextMonth.toISOString().split('T')[0], title:'الجلسة العمومية للمجلس'});
  
  // Real family branches from PDF
  State.setFamilyBranches([
    {id:'b_kazim',name:'آل كاظم',head:'كاظم العوامي',count:7,color:'#47915C',notes:'الفرع الرئيسي',members:['علي','محمد','علي (أبو الحجي)','محمد','علي','هاني','تيسير']},
    {id:'b_ibrahim',name:'آل إبراهيم (أبو خليل)',head:'إبراهيم (أبو خليل)',count:3,color:'#1B3456',notes:'',members:['سعيد (أبو خديجة)','عارف هنيدي','هاجر (أم وائل)']},
    {id:'b_radi',name:'آل راضي',head:'راضي',count:8,color:'#c8a84b',notes:'',members:['علي (أبو صبري)','صبري','راضي','أمين','فخري','عماد','سعيد','معين']},
    {id:'b_salman',name:'آل سلمان علي',head:'سلمان علي',count:11,color:'#8e44ad',notes:'',members:['سلمان علي','عبدالله','مصطفى','مدينة (أم علي)','فاطمة (أم حسين)','حسن','علي','وديع','زكي','محمد','مروان']},
    {id:'b_ali_haidar',name:'آل علي (أبو حيدر)',head:'علي (أبو حيدر)',count:5,color:'#2980b9',notes:'',members:['محمد (أبو عبدالله)','عبدالله','غازي','سعيد','جهاد']},
    {id:'b_ahmed',name:'آل أحمد عبد الحميد',head:'أحمد عبد الحميد (أبو عالء)',count:3,color:'#c0392b',notes:'',members:['مكي (أبو أحمد)','زهراء','زينب (أم أمجد وقع)']},
    {id:'b_naser',name:'آل ناصر العوامي',head:'ناصر العوامي',count:10,color:'#27ae60',notes:'',members:['علي','محمد','ناصر','طارق','محمود','عزيز','مكي','عبدالله','مهند','علي']},
    {id:'b_ibrahim2',name:'آل إبراهيم (أبو مصطفى)',head:'إبراهيم (أبو مصطفى)',count:5,color:'#e67e22',notes:'',members:['مصطفى','خليل','مرتضى','مجتبى','أمين']},
    {id:'b_hussein',name:'آل حسين',head:'حسين',count:0,color:'#34495e',notes:'',members:[]}
  ]);
  
  log('تم تهيئة النظام ببيانات عائلة العوامي 🚀','🎯'); saveDB();
}

// =================== INIT ===================
document.querySelectorAll('.modal-overlay').forEach(ov=>ov.addEventListener('click',e=>{ if(e.target===ov) ov.classList.remove('open'); }));

// =================== MOBILE ENHANCEMENTS ===================
// Backdrop for sidebar
(function initMobileBackdrop() {
  const backdrop = document.createElement('div');
  backdrop.className = 'sidebar-backdrop';
  document.body.appendChild(backdrop);
  backdrop.addEventListener('click', () => closeMobileSidebar());
})();

function openMobileSidebar() {
  document.querySelector('.sidebar').classList.add('open');
  document.querySelector('.sidebar-backdrop').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeMobileSidebar() {
  document.querySelector('.sidebar').classList.remove('open');
  document.querySelector('.sidebar-backdrop').classList.remove('active');
  document.body.style.overflow = '';
}

// Update mobile toggle to use backdrop
document.querySelector('.mobile-toggle').onclick = function(e) {
  e.stopPropagation();
  const sidebar = document.querySelector('.sidebar');
  if (sidebar.classList.contains('open')) {
    closeMobileSidebar();
  } else {
    openMobileSidebar();
  }
};

// Close sidebar on nav click (mobile)
document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => {
    if (window.innerWidth <= 768) closeMobileSidebar();
  });
});

// Swipe to open/close sidebar
(function initSwipeGesture() {
  let touchStartX = 0;
  let touchStartY = 0;
  let swiping = false;

  document.addEventListener('touchstart', e => {
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
    swiping = false;
  }, { passive: true });

  document.addEventListener('touchmove', e => {
    if (swiping) return;
    const dx = e.touches[0].clientX - touchStartX;
    const dy = e.touches[0].clientY - touchStartY;
    if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 30) {
      swiping = true;
      const sidebar = document.querySelector('.sidebar');
      // Swipe left from right edge → open sidebar (RTL)
      if (dx < -50 && touchStartX > window.innerWidth - 40 && !sidebar.classList.contains('open') && window.innerWidth <= 768) {
        openMobileSidebar();
      }
      // Swipe right → close sidebar (RTL)
      if (dx > 50 && sidebar.classList.contains('open') && window.innerWidth <= 768) {
        closeMobileSidebar();
      }
    }
  }, { passive: true });
})();

// Close mobile sidebar when clicking outside (fallback)
document.addEventListener('click', e => {
  const sidebar = document.querySelector('.sidebar');
  const toggle = document.querySelector('.mobile-toggle');
  if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
    if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
      closeMobileSidebar();
    }
  }
});

// Handle orientation change
window.addEventListener('resize', () => {
  if (window.innerWidth > 768) {
    document.querySelector('.sidebar').classList.remove('open');
    document.querySelector('.sidebar-backdrop').classList.remove('active');
    document.body.style.overflow = '';
  }
});

// Load all data from API then render initial view
(async function initApp() {
  await loadAllData();
  updateSidebar();
  renderDashboard();
})();
