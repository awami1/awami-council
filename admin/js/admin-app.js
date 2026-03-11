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
  
  const T={dashboard:'لوحة التحكم|مجلس عائلة العوامي',council:'مناصب المجلس|الهيئة الإدارية',members:'الأعضاء|إدارة الأعضاء',fees:'الرسوم|متابعة المدفوعات',reminders:'التذكيرات|تذكيرات الأعضاء غير الدافعين',committees:'اللجان|اللجان الفرعية للمجلس',orgchart:'الهيكل التنظيمي|مجلس عائلة العوامي',budget:'الميزانية|السجل المالي',events:'الفعاليات|الأنشطة',calendar:'التقويم|عرض تقويمي',familytree:'شجرة العائلة|الأفرع العائلية',voting:'التصويت|استطلاعات الرأي',portal:'بوابة العضو|الملف الشخصي','smart-reports':'التقارير الذكية|تحليل مدعوم بالذكاء الاصطناعي',reports:'التقارير|إحصائيات',audit:'سجل التدقيق|من غيّر ماذا ومتى','export':'تصدير البيانات|Excel و CSV',websettings:'الموقع العام|إدارة المحتوى',settings:'النسخ الاحتياطي|إدارة البيانات',riwaq:'الرِّوَاق|معرض الحكايات'};
  const [t,s]=(T[name]||'--|--').split('|');
  document.getElementById('topbar-title').innerHTML=t+` <span>${s}</span>`;
  const A={members:`<button class="btn btn-primary" onclick="openAddMember()">+ إضافة عضو</button>`,budget:`<button class="btn btn-primary" onclick="openModal('modal-tx')">+ معاملة</button>`,events:`<button class="btn btn-primary" onclick="openAddEvent()">+ فعالية</button>`,riwaq:`<button class="btn btn-primary" onclick="openAddGalleryStory()">+ إضافة قصة</button>`};
  document.getElementById('topbar-action').innerHTML=A[name]||'';
  const renderers={dashboard:renderDashboard,council:renderCouncil,members:renderMembers,fees:renderFees,reminders:renderReminders,committees:renderCommittees,orgchart:renderOrgChart,budget:renderBudget,events:renderEvents,calendar:renderCalendar,familytree:renderFamilyTree,voting:renderVoting,portal:renderPortalSelect,'smart-reports':function(){ srLoadSavedReports('active'); },reports:renderReports,audit:renderAuditLog,'export':function(){},websettings:renderWebsiteSettings,settings:renderSettings,messages:renderMessages,news:renderNews,riwaq:async function(){ if(!_riwaqLoaded) await loadRiwaqData(); renderRiwaq(); }};
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
      dd.innerHTML = filtered.map(m=>`<div style="padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border)" onmousedown="selectPositionMember('${m.id}','${esc(m.name)}')">${esc(m.name)}</div>`).join('');
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

// =================== MEDIA MANAGEMENT ===================
function openAddMedia(){
  document.getElementById('media-edit-id').value='';
  document.getElementById('modal-media-title').textContent='📷 إضافة ميديا';
  document.getElementById('media-title').value='';
  document.getElementById('media-type').value='images';
  document.getElementById('media-url').value='';
  document.getElementById('media-date').value=today();
  document.getElementById('media-tags').value='';
  document.getElementById('media-url-preview').style.display='none';
  openModal('modal-add-media');
}

async function saveMedia(){
  var title=document.getElementById('media-title').value.trim();
  var type=document.getElementById('media-type').value;
  var url=document.getElementById('media-url').value.trim();

  if(!title||!url){toast('الرجاء ملء العنوان والرابط','error');return;}

  var tags=document.getElementById('media-tags').value.split(',').map(function(t){return t.trim();}).filter(function(t){return t;});

  var mediaItem={
    title:title,
    type:type,
    url:url,
    date:document.getElementById('media-date').value||today(),
    tags:tags,
  };

  try {
    await AdminMedia.create(mediaItem);
    log('إضافة ميديا: '+title,'📷');
    toast('تم إضافة الميديا ✅');
    closeModalSilent('modal-add-media');
    renderMediaList();
  } catch(e) { toast('خطأ في الحفظ: ' + e.message, 'error'); }
}

function renderMediaList(){
  var container=document.getElementById('media-list-admin');
  var media=State.getMedia()||[];

  if(!media.length){
    container.innerHTML='<div class="empty-state"><div class="empty-icon">📷</div><p>لا توجد وسائط</p></div>';
    return;
  }

  var html='<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px">';
  media.forEach(function(item){
    var icon=item.type==='images'?'📷':item.type==='videos'?'🎥':item.type==='youtube'?'▶️':'🎉';
    html+='<div style="border:2px solid var(--border);border-radius:10px;overflow:hidden">';
    if(item.type==='images'){
      html+='<img src="'+item.url+'" style="width:100%;height:150px;object-fit:cover" loading="lazy">';
    }else if(item.type==='videos'){
      html+='<video src="'+item.url+'" style="width:100%;height:150px;object-fit:cover" preload="metadata"></video>';
    }else if(item.type==='youtube'){
      // صورة مصغرة من يوتيوب بدلاً من iframe بطيء
      var ytId='';var ytMatch=item.url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
      if(ytMatch)ytId=ytMatch[1];
      if(ytId){
        html+='<div style="position:relative;width:100%;height:150px;background:#000;overflow:hidden">';
        html+='<img src="https://img.youtube.com/vi/'+ytId+'/mqdefault.jpg" style="width:100%;height:100%;object-fit:cover;opacity:.8">';
        html+='<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center"><div style="width:44px;height:44px;background:rgba(255,0,0,.85);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px">▶</div></div>';
        html+='</div>';
      }else{
        html+='<div style="width:100%;height:150px;background:#111;display:flex;align-items:center;justify-content:center;font-size:48px">▶️</div>';
      }
    }else{
      html+='<div style="width:100%;height:150px;background:var(--green);display:flex;align-items:center;justify-content:center;font-size:48px">'+icon+'</div>';
    }
    html+='<div style="padding:12px">';
    html+='<div style="font-weight:700;margin-bottom:6px">'+icon+' '+item.title+'</div>';
    html+='<div style="font-size:12px;color:var(--text-muted);margin-bottom:8px">'+new Date(item.date).toLocaleDateString('ar-SA')+'</div>';
    if(item.tags&&item.tags.length){
      item.tags.forEach(function(tag){
        html+='<span class="badge badge-gray" style="margin:2px">'+tag+'</span>';
      });
    }
    html+='<div style="display:flex;gap:6px;margin-top:10px">';
    html+='<button class="btn btn-outline btn-xs" onclick="openEditMedia(\''+item.id+'\')">✏️ تعديل</button>';
    html+='<button class="btn btn-danger btn-xs" onclick="deleteMedia(\''+item.id+'\')">🗑️ حذف</button>';
    html+='</div>';
    html+='</div></div>';
  });
  html+='</div>';
  container.innerHTML=html;
}

async function deleteMedia(id){
  if(!confirm('حذف هذه الميديا؟'))return;
  try {
    await AdminMedia.delete(id);
    toast('تم الحذف');
    renderMediaList();
  } catch(e) { toast('خطأ في الحذف: ' + e.message, 'error'); }
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

// =================== COUNCIL ===================
async function renderCouncil(){
  await renderPositionsList();
}

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
    const linkedMems=(State.getCommitteeMembers()[c.id]||c.members||[]).length;
    const mems=Math.max(c.members_count||0, linkedMems);
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
  const defaultMembers=(c.members||[]);
  const customIds=State.getCommitteeMembers()[cid]||[];
  const allMembers=State.getMembers().filter(m=>customIds.includes(m.id));
  const notIn=State.getMembers().filter(m=>!customIds.includes(m.id));
  const events=State.getEvents().filter(e=>e.committeeId===cid);
  const txs=State.getTransactions().filter(t=>t.committee===cid);
  const spent=txs.filter(t=>t.type==='مصروف').reduce((s,t)=>s+t.amount,0);
  document.getElementById('cdetail-body').innerHTML=`
    <div style="padding:12px;background:#f8fbf8;border-radius:10px;margin-bottom:14px">
      <div style="font-size:13px;color:var(--text-muted)">${c.desc||''}</div>
      ${c.advisory?'<div style="margin-top:6px"><span class="badge badge-purple">🎓 لجنة استشارية - تقدم المشورة للإدارة</span></div>':''}
    </div>
    <div class="grid-3" style="margin-bottom:14px;text-align:center">
      <div style="background:#dcfce7;padding:10px;border-radius:10px"><div style="font-size:18px;font-weight:900;color:#166534">${Math.max(c.members_count||0, defaultMembers.length, allMembers.length)}</div><div style="font-size:11px;color:#166534">عضو</div></div>
      <div style="background:#dbeafe;padding:10px;border-radius:10px"><div style="font-size:18px;font-weight:900;color:#1e40af">${events.length}</div><div style="font-size:11px;color:#1e40af">فعالية</div></div>
      <div style="background:#fef9c3;padding:10px;border-radius:10px"><div style="font-size:18px;font-weight:900;color:#854d0e">${fmt(spent)}</div><div style="font-size:11px;color:#854d0e">ريال مصاريف</div></div>
    </div>
    <div style="font-size:13px;font-weight:700;margin-bottom:8px">👥 أعضاء اللجنة (من الـ PDF)</div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px">
      ${defaultMembers.map(name=>`<span style="background:linear-gradient(135deg,${c.color.split(',')[0].replace('linear-gradient(135deg,','')},transparent);color:var(--green-dark);border:1px solid rgba(71,145,92,.2);padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600">${name}</span>`).join('')}
    </div>
    ${allMembers.length?`<div style="font-size:13px;font-weight:700;margin-bottom:8px">🔗 أعضاء مرتبطون من قاعدة البيانات</div><div style="margin-bottom:12px">${allMembers.map(m=>`<div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #f0ebe0"><div class="avatar" style="background:${avColor(m.name)}">${avInit(m.name)}</div><div style="flex:1"><div style="font-weight:600;font-size:13px">${m.name}</div></div><button class="btn btn-danger btn-xs" onclick="removeMemberFromCommittee('${cid}','${m.id}')">✕</button></div>`).join('')}</div>`:''}
    ${notIn.length&&State.getMembers().length?`<div style="display:flex;gap:8px"><select class="form-control" id="add-to-c-select" style="flex:1">${notIn.map(m=>`<option value="${m.id}">${m.name}</option>`).join('')}</select><button class="btn btn-primary btn-sm" onclick="addMemberToCommittee('${cid}')">+ ربط عضو</button></div>`:''}
    ${events.length?`<div style="margin-top:14px;font-size:13px;font-weight:700;margin-bottom:8px">🗓️ فعاليات اللجنة</div><div>${events.map(e=>`<div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f0f0f0"><span>${e.icon||'🎉'}</span><div style="flex:1"><div style="font-size:13px;font-weight:600">${e.name}</div><div style="font-size:11px;color:var(--text-muted)">${e.date||'—'}</div></div><span class="badge ${sBadge(e.status)}">${e.status}</span></div>`).join('')}</div>`:''}
  `;
  openModal('modal-committee-detail');
}

function addMemberToCommittee(cid){ const mid=document.getElementById('add-to-c-select')?.value; MemberService.addMemberToCommittee(cid, mid); }
function removeMemberFromCommittee(cid,mid){ MemberService.removeMemberFromCommittee(cid, mid); }

// =================== ORG CHART ===================
function renderOrgChart(){
  const advisory=State.getCommittees().find(c=>c.advisory);
  const regular=State.getCommittees().filter(c=>!c.advisory);
  document.getElementById('org-body').innerHTML=`
    <div style="text-align:center">
      ${advisory?`<div style="margin-bottom:16px;display:flex;justify-content:center"><div style="border:2px dashed var(--card-advisory-border);border-radius:12px;padding:10px 20px;background:var(--card-advisory-bg);display:inline-flex;align-items:center;gap:10px"><span style="font-size:20px">${advisory.icon}</span><div><div style="font-weight:700;font-size:13px;color:var(--primary)">${advisory.name}</div><div style="font-size:10px;color:var(--text-muted)">جهة استشارية</div></div></div></div>`:''}
      <div style="display:flex;justify-content:center;margin-bottom:6px">
        <div style="background:linear-gradient(135deg,var(--green-dark),var(--primary));color:#fff;border-radius:14px;padding:14px 28px;display:inline-flex;align-items:center;gap:12px">
          <span style="font-size:22px">🏛️</span>
          <div style="text-align:right"><div style="font-size:14px;font-weight:700">إدارة مجلس صندوق عائلة العوامي</div><div style="font-size:10px;opacity:.7">الرئيس: منصور علي</div></div>
        </div>
      </div>
      <div style="display:flex;justify-content:center;margin:2px 0"><div style="width:2px;height:20px;background:var(--border)"></div></div>
      <div style="width:80%;height:2px;background:var(--border);margin:0 auto"></div>
      <div style="display:flex;justify-content:center;gap:8px;flex-wrap:wrap;padding-top:0">
        ${regular.map(c=>{ const linkedMems=(State.getCommitteeMembers()[c.id]||c.members||[]).length; const mems=Math.max(c.members_count||0, linkedMems); return `<div style="display:flex;flex-direction:column;align-items:center"><div style="width:2px;height:20px;background:var(--border)"></div><div style="border:2px solid var(--border);border-radius:10px;padding:10px 12px;min-width:120px;background:var(--bg-card);cursor:pointer;transition:all .2s" onmouseover="this.style.borderColor='var(--green)'" onmouseout="this.style.borderColor='var(--border)'" onclick="showPage('committees',document.querySelector('[onclick*=committees]'));setTimeout(()=>showCommitteeDetail('${c.id}'),300)"><div style="font-size:20px;margin-bottom:3px">${c.icon}</div><div style="font-size:11px;font-weight:700;color:var(--green-dark)">${c.name}</div><div style="font-size:10px;color:var(--text-muted);margin-top:2px">👥 ${mems} عضو</div></div></div>`; }).join('')}
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
        const phone = m.phone ? m.phone.replace(/^0/, '966') : '';
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
  document.getElementById('whatsapp-list').innerHTML=list.map(m=>{ const phone=m.phone?m.phone.replace(/^0/,'966'):''; const msg=encodeURIComponent(`السلام عليكم ${m.name} 👋\n\nنذكركم بسداد رسوم مجلس عائلة العوامي للدورة "${p.name}"\nالمبلغ المطلوب: ${fmt(p.feeAmount)} ريال\n\nشكراً لكم 🙏`); return `<div style="display:flex;align-items:center;gap:10px;padding:10px;border-bottom:1px solid #f0ebe0"><div class="avatar" style="background:${avColor(m.name)}">${avInit(m.name)}</div><div style="flex:1"><div style="font-weight:600">${m.name}</div><div style="font-size:11px;color:var(--text-muted)">${m.phone||'لا يوجد رقم'}</div></div>${phone?`<a href="https://wa.me/${phone}?text=${msg}" target="_blank" class="btn btn-whatsapp btn-sm">📱 إرسال</a>`:'<span class="badge badge-gray">بدون رقم</span>'}</div>`; }).join('');
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
// AI SMART REPORTS - تحليل ذكي بالذكاء الاصطناعي
// =====================================================

var aiAnalysisData = null;
var srWorkbook = null;
var srRawData = null;
var srFileName = '';
var srSelectedRows = new Set();

// ---- Stepper ----
function srSetStep(step) {
  document.querySelectorAll('.sr-step').forEach(function(el) {
    var s = parseInt(el.dataset.step);
    el.classList.remove('active', 'done');
    if (s < step) el.classList.add('done');
    if (s === step) el.classList.add('active');
  });
  document.querySelectorAll('.sr-step-line').forEach(function(el, i) {
    el.classList.toggle('done', i < step - 1);
  });
}

// ---- Drag & Drop ----
function handleAIDragOver(e) {
  e.preventDefault();
  e.stopPropagation();
  document.getElementById('upload-area').classList.add('drag-over');
}
function handleAIDragLeave(e) {
  e.preventDefault();
  document.getElementById('upload-area').classList.remove('drag-over');
}
function handleAIDrop(e) {
  e.preventDefault();
  e.stopPropagation();
  document.getElementById('upload-area').classList.remove('drag-over');
  var file = e.dataTransfer.files[0];
  if (file) srProcessFile(file);
}

// ---- File Upload ----
function handleAIFileUpload(event) {
  var file = event.target.files[0];
  if (file) srProcessFile(file);
}

function srProcessFile(file) {
  if (!file.name.match(/\.(xlsx|xls|csv)$/i)) {
    toast('نوع الملف غير مدعوم. يرجى رفع ملف Excel أو CSV', 'error');
    return;
  }
  if (file.size > 10 * 1024 * 1024) {
    toast('حجم الملف يتجاوز 10 ميجابايت', 'error');
    return;
  }

  srFileName = file.name;
  var infoEl = document.getElementById('upload-file-info');
  infoEl.style.display = 'block';
  infoEl.textContent = '📄 ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';

  var reader = new FileReader();
  reader.onload = function(e) {
    try {
      var data = new Uint8Array(e.target.result);
      srWorkbook = XLSX.read(data, {type: 'array'});
      srShowPreview(0);
    } catch (error) {
      toast('حدث خطأ في قراءة الملف: ' + error.message, 'error');
      resetAIAnalysis();
    }
  };
  reader.onerror = function() {
    toast('فشل قراءة الملف', 'error');
    resetAIAnalysis();
  };
  reader.readAsArrayBuffer(file);
}

// ---- Preview & Column Mapping ----
function srShowPreview(sheetIdx) {
  var sheetNames = srWorkbook.SheetNames;

  // Multi-sheet selector
  var sel = document.getElementById('sr-sheet-select');
  if (sheetNames.length > 1) {
    sel.style.display = 'block';
    sel.innerHTML = sheetNames.map(function(n, i) {
      return '<option value="' + i + '"' + (i === sheetIdx ? ' selected' : '') + '>' + n + '</option>';
    }).join('');
  } else {
    sel.style.display = 'none';
  }

  var sheet = srWorkbook.Sheets[sheetNames[sheetIdx]];
  srRawData = XLSX.utils.sheet_to_json(sheet, {raw: false});

  if (!srRawData || srRawData.length === 0) {
    toast('الملف فارغ أو لا يحتوي على بيانات', 'error');
    resetAIAnalysis();
    return;
  }

  // Data cleaning report
  var cleanResult = srCleanData(srRawData);
  srRawData = cleanResult.data;

  if (cleanResult.removedCount > 0) {
    var info = document.getElementById('sr-cleaning-info');
    info.style.display = 'block';
    info.innerHTML = '🧹 <strong>تنظيف تلقائي:</strong> تم إزالة ' + cleanResult.removedCount + ' صف (إجماليات/فارغة)' +
      (cleanResult.fixedAmounts > 0 ? ' • تصحيح ' + cleanResult.fixedAmounts + ' مبلغ' : '') +
      (cleanResult.fixedDates > 0 ? ' • تحويل ' + cleanResult.fixedDates + ' تاريخ' : '');
  }

  // Auto-detect columns
  var cols = Object.keys(srRawData[0] || {});
  var detected = srAutoDetectColumns(cols);

  // Populate mapping dropdowns
  var fields = [
    {id: 'sr-col-desc', detected: detected.desc},
    {id: 'sr-col-amount', detected: detected.amount},
    {id: 'sr-col-date', detected: detected.date},
    {id: 'sr-col-type', detected: detected.type}
  ];

  fields.forEach(function(f) {
    var select = document.getElementById(f.id);
    select.innerHTML = '<option value="">-- غير محدد --</option>' +
      cols.map(function(c) {
        return '<option value="' + c + '"' + (c === f.detected ? ' selected' : '') + '>' + c + '</option>';
      }).join('');
  });

  // Detection info
  var detectedCount = [detected.desc, detected.amount, detected.date, detected.type].filter(Boolean).length;
  document.getElementById('sr-detection-info').innerHTML =
    '🔍 تم كشف <strong>' + detectedCount + '/4</strong> أعمدة تلقائياً من أصل <strong>' + cols.length + '</strong> عمود';

  // Preview table (first 5 rows)
  var previewRows = srRawData.slice(0, 5);
  var thead = '<tr>' + cols.map(function(c) { return '<th>' + c + '</th>'; }).join('') + '</tr>';
  var tbody = previewRows.map(function(row) {
    return '<tr>' + cols.map(function(c) { return '<td>' + (row[c] || '') + '</td>'; }).join('') + '</tr>';
  }).join('');

  document.querySelector('#sr-preview-table thead').innerHTML = thead;
  document.querySelector('#sr-preview-table tbody').innerHTML = tbody;

  // Show preview, hide upload
  document.getElementById('sr-upload-card').style.display = 'none';
  document.getElementById('sr-preview-card').style.display = 'block';
  srSetStep(2);
}

function srSelectSheet(idx) {
  srShowPreview(parseInt(idx));
}

// ---- Column Auto-Detection ----
function srAutoDetectColumns(cols) {
  var descWords = ['الوصف', 'البيان', 'بيان', 'description', 'desc', 'وصف', 'تفاصيل', 'الملاحظات', 'ملاحظة', 'note'];
  var amountWords = ['المبلغ', 'القيمة', 'amount', 'value', 'مبلغ', 'قيمة', 'debit', 'credit', 'مدين', 'دائن', 'الرصيد'];
  var dateWords = ['التاريخ', 'date', 'تاريخ', 'تاريخ العملية', 'تاريخ المعاملة'];
  var typeWords = ['النوع', 'type', 'نوع', 'تصنيف'];

  function findCol(words) {
    for (var i = 0; i < cols.length; i++) {
      var lc = cols[i].toLowerCase().trim();
      for (var j = 0; j < words.length; j++) {
        if (lc === words[j]) return cols[i];
      }
    }
    for (var i = 0; i < cols.length; i++) {
      var lc = cols[i].toLowerCase().trim();
      for (var j = 0; j < words.length; j++) {
        if (lc.includes(words[j]) || words[j].includes(lc)) return cols[i];
      }
    }
    return '';
  }

  return { desc: findCol(descWords), amount: findCol(amountWords), date: findCol(dateWords), type: findCol(typeWords) };
}

// ---- Data Cleaning Pipeline ----
function srCleanData(data) {
  var removedCount = 0, fixedAmounts = 0, fixedDates = 0;
  var skipWords = ['المجموع', 'الإجمالي', 'total', 'subtotal', 'الرصيد النهائي', 'grand total', 'مجموع'];

  var cleaned = data.filter(function(row) {
    var vals = Object.values(row).join(' ').toLowerCase();
    for (var i = 0; i < skipWords.length; i++) {
      if (vals.includes(skipWords[i])) { removedCount++; return false; }
    }
    var hasValue = Object.values(row).some(function(v) { return v && String(v).trim(); });
    if (!hasValue) { removedCount++; return false; }
    return true;
  });

  cleaned.forEach(function(row) {
    Object.keys(row).forEach(function(key) {
      var val = String(row[key] || '');
      var arabicNums = val.replace(/[٠-٩]/g, function(d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); });
      if (arabicNums !== val) { row[key] = arabicNums; fixedAmounts++; }
    });
  });

  return { data: cleaned, removedCount: removedCount, fixedAmounts: fixedAmounts, fixedDates: fixedDates };
}

// ---- Start Analysis ----
function srStartAnalysis() {
  var descCol = document.getElementById('sr-col-desc').value;
  var amountCol = document.getElementById('sr-col-amount').value;

  if (!descCol && !amountCol) {
    toast('يرجى تحديد عمود الوصف والمبلغ على الأقل', 'error');
    return;
  }

  document.getElementById('sr-preview-card').style.display = 'none';
  document.getElementById('ai-processing').style.display = 'block';
  srSetStep(3);

  var dateCol = document.getElementById('sr-col-date').value;
  var typeCol = document.getElementById('sr-col-type').value;

  var pct = 0;
  var progressInterval = setInterval(function() {
    pct = Math.min(pct + 3, 90);
    document.getElementById('ai-progress-bar').style.width = pct + '%';
    document.getElementById('ai-progress-pct').textContent = pct + '%';
    if (pct <= 30) document.getElementById('ai-status-text').textContent = 'جاري قراءة البيانات...';
    else if (pct <= 60) document.getElementById('ai-status-text').textContent = 'جاري التصنيف الذكي...';
    else document.getElementById('ai-status-text').textContent = 'جاري إعداد النتائج...';
  }, 80);

  setTimeout(function() {
    analyzeWithAI(srRawData, descCol, amountCol, dateCol, typeCol);
    clearInterval(progressInterval);
    document.getElementById('ai-progress-bar').style.width = '100%';
    document.getElementById('ai-progress-pct').textContent = '100%';
    document.getElementById('ai-status-text').textContent = 'اكتمل التحليل!';
    setTimeout(function() { displayAIResults(); }, 400);
  }, 1500);
}

// ---- Smart Categorization with Confidence ----
function smartCategorize(description, type) {
  var desc = String(description).toLowerCase();
  var keywords = {
    'رواتب':     ['راتب', 'مرتب', 'أجر', 'salary', 'wage', 'payroll'],
    'إيجار':     ['إيجار', 'rent', 'lease', 'استئجار'],
    'مرافق':     ['كهرباء', 'ماء', 'غاز', 'electricity', 'water', 'utility', 'هاتف', 'انترنت', 'internet'],
    'تسويق':     ['تسويق', 'إعلان', 'marketing', 'advertising', 'دعاية', 'ترويج'],
    'قرطاسية':   ['قرطاسية', 'مكتب', 'طباعة', 'office', 'supplies', 'stationery', 'أدوات'],
    'صيانة':     ['صيانة', 'إصلاح', 'maintenance', 'repair', 'ترميم'],
    'اشتراكات':  ['اشتراك', 'عضوية', 'subscription', 'membership'],
    'تبرعات':    ['تبرع', 'donation', 'هبة', 'صدقة', 'زكاة'],
    'مبيعات':    ['مبيعات', 'sales', 'بيع'],
    'خدمات':     ['خدمة', 'service', 'استشارة', 'خدمات'],
    'ضيافة':     ['ضيافة', 'مأكولات', 'مشروبات', 'catering', 'food', 'قهوة', 'عشاء', 'غداء', 'بوفيه', 'أكل'],
    'نقل':       ['نقل', 'مواصلات', 'transport', 'باص', 'سيارة', 'بنزين', 'وقود', 'تذكرة', 'طيران'],
    'إعلام':     ['إعلام', 'تصوير', 'فيديو', 'media', 'photography', 'فوتو'],
    'هدايا':     ['هدية', 'هدايا', 'جائزة', 'جوائز', 'مسابقة', 'gift', 'prize'],
    'تعليم':     ['تعليم', 'تدريب', 'دورة', 'ورشة', 'education', 'training', 'محاضرة'],
    'رحلات':     ['رحلة', 'عمرة', 'حج', 'سفر', 'trip', 'travel', 'فندق', 'حجز'],
    'مناسبات':   ['عيد', 'زواج', 'عزاء', 'مناسبة', 'حفل', 'event', 'احتفال'],
    'رسوم عضوية':['رسوم', 'fee', 'اشتراك سنوي', 'رسوم سنوية', 'رسم عضوية']
  };

  for (var category in keywords) {
    var words = keywords[category];
    for (var i = 0; i < words.length; i++) {
      if (desc.includes(words[i])) return { category: category, confidence: 'high' };
    }
  }

  for (var category in keywords) {
    var words = keywords[category];
    for (var i = 0; i < words.length; i++) {
      if (words[i].length >= 3 && desc.split(/\s+/).some(function(w) {
        return w.length >= 3 && (w.includes(words[i].substring(0, 3)) || words[i].includes(w.substring(0, 3)));
      })) {
        return { category: category, confidence: 'medium' };
      }
    }
  }

  return { category: type === 'income' ? 'إيرادات عامة' : 'أخرى', confidence: 'low' };
}

// ---- Analysis Engine ----
function analyzeWithAI(rawData, descCol, amountCol, dateCol, typeCol) {
  var transactions = [];
  var categories = {};
  var totalIncome = 0;
  var totalExpense = 0;

  for (var i = 0; i < rawData.length; i++) {
    var row = rawData[i];
    var description = String(row[descCol] || '').trim();
    var amountStr = String(row[amountCol] || '0');
    var dateStr = dateCol ? String(row[dateCol] || '') : '';
    var typeStr = typeCol ? String(row[typeCol] || '') : '';

    var amount = parseFloat(amountStr.replace(/[^\d.\-]/g, '')) || 0;
    if (amount === 0) continue;

    var type = 'expense';
    var lowerType = typeStr.toLowerCase();
    var lowerDesc = description.toLowerCase();
    if (lowerType.includes('دخل') || lowerType.includes('إيراد') || lowerType.includes('income') ||
        lowerType.includes('دائن') || lowerType.includes('credit') ||
        lowerDesc.includes('اشتراك') || lowerDesc.includes('تبرع') || lowerDesc.includes('إيراد')) {
      type = 'income';
    }

    var catResult = smartCategorize(description, type);

    var date = dateStr || new Date().toISOString().split('T')[0];
    if (date.match(/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}$/)) {
      var parts = date.split(/[\/\-]/);
      if (parts[2] && parts[2].length === 4) date = parts[2] + '-' + parts[1].padStart(2,'0') + '-' + parts[0].padStart(2,'0');
    }

    transactions.push({
      id: uid(), date: date, description: description, amount: Math.abs(amount),
      type: type, category: catResult.category, confidence: catResult.confidence
    });

    if (!categories[catResult.category]) categories[catResult.category] = {income:0, expense:0, count:0};
    categories[catResult.category].count++;
    if (type === 'income') { totalIncome += Math.abs(amount); categories[catResult.category].income += Math.abs(amount); }
    else { totalExpense += Math.abs(amount); categories[catResult.category].expense += Math.abs(amount); }
  }

  aiAnalysisData = {
    transactions: transactions, categories: categories,
    totalIncome: totalIncome, totalExpense: totalExpense,
    netProfit: totalIncome - totalExpense,
    analyzedAt: new Date().toISOString(), fileName: srFileName
  };
}

// ---- Display Results ----
function displayAIResults() {
  document.getElementById('ai-processing').style.display = 'none';
  document.getElementById('ai-results').style.display = 'block';
  srSetStep(4);
  srSelectedRows.clear();
  srRecalcStats();
  document.getElementById('ai-insights').innerHTML = generateAIInsights(aiAnalysisData);
  srRenderCategories(aiAnalysisData);
  srRenderTransactionTable(aiAnalysisData.transactions);
  srLoadSavedReports('active');
}

function srRecalcStats() {
  var data = aiAnalysisData;
  data.totalIncome = 0; data.totalExpense = 0; data.categories = {};
  data.transactions.forEach(function(tx) {
    if (tx.type === 'income') data.totalIncome += tx.amount; else data.totalExpense += tx.amount;
    if (!data.categories[tx.category]) data.categories[tx.category] = {income:0, expense:0, count:0};
    data.categories[tx.category].count++;
    if (tx.type === 'income') data.categories[tx.category].income += tx.amount;
    else data.categories[tx.category].expense += tx.amount;
  });
  data.netProfit = data.totalIncome - data.totalExpense;

  document.getElementById('ai-total-count').textContent = data.transactions.length.toLocaleString();
  document.getElementById('ai-total-income').textContent = data.totalIncome.toLocaleString('ar-SA', {maximumFractionDigits: 0});
  document.getElementById('ai-total-expense').textContent = data.totalExpense.toLocaleString('ar-SA', {maximumFractionDigits: 0});
  document.getElementById('ai-net-profit').textContent = data.netProfit.toLocaleString('ar-SA', {maximumFractionDigits: 0});
  document.getElementById('ai-net-profit').style.color = data.netProfit >= 0 ? 'var(--green)' : 'var(--danger)';
  document.getElementById('sr-tx-count-label').textContent = data.transactions.length + ' معاملة';
}

function srRenderCategories(data) {
  var cats = Object.entries(data.categories).sort(function(a, b) { return (b[1].income + b[1].expense) - (a[1].income + a[1].expense); });
  var grandTotal = data.totalIncome + data.totalExpense || 1;
  var html = '<div style="display:grid;gap:10px">';
  cats.forEach(function(entry) {
    var cat = entry[0], cd = entry[1], total = cd.income + cd.expense;
    var pct = ((total / grandTotal) * 100).toFixed(1);
    html += '<div style="padding:12px;background:var(--bg);border-radius:10px">' +
      '<div style="display:flex;justify-content:space-between;margin-bottom:6px">' +
      '<div style="font-weight:700;font-size:13px">' + cat + '</div>' +
      '<div style="font-weight:700;color:var(--green)">' + total.toLocaleString('ar-SA',{maximumFractionDigits:0}) + ' ريال</div></div>' +
      '<div class="progress-bar" style="margin-bottom:6px"><div class="progress-fill" style="width:' + Math.min((total/grandTotal)*100,100) + '%"></div></div>' +
      '<div style="font-size:11px;color:var(--text-muted)">المعاملات: ' + cd.count + ' • النسبة: ' + pct + '%' +
      (cd.income > 0 ? ' • إيراد: ' + cd.income.toLocaleString('ar-SA',{maximumFractionDigits:0}) : '') +
      (cd.expense > 0 ? ' • مصروف: ' + cd.expense.toLocaleString('ar-SA',{maximumFractionDigits:0}) : '') + '</div></div>';
  });
  html += '</div>';
  document.getElementById('ai-categories').innerHTML = html;
}

// ---- Transaction Table (Editable) ----
function srRenderTransactionTable(txList) {
  var catOptions = Object.keys(aiAnalysisData.categories);
  var bulkCatSel = document.getElementById('sr-bulk-category');
  bulkCatSel.innerHTML = '<option value="">تغيير الفئة...</option>' + catOptions.map(function(c) { return '<option value="' + c + '">' + c + '</option>'; }).join('');

  var tbody = document.getElementById('sr-transactions-tbody');
  tbody.innerHTML = txList.map(function(tx, i) {
    var confClass = 'confidence-' + tx.confidence;
    var confLabel = tx.confidence === 'high' ? 'عالي' : tx.confidence === 'medium' ? 'متوسط' : 'منخفض';
    var typeLabel = tx.type === 'income' ? 'إيراد' : 'مصروف';
    var typeColor = tx.type === 'income' ? 'var(--green)' : 'var(--danger)';
    return '<tr data-idx="' + i + '">' +
      '<td><input type="checkbox" class="sr-row-check" data-idx="' + i + '" onchange="srToggleRow(' + i + ', this.checked)"></td>' +
      '<td style="color:var(--text-muted)">' + (i + 1) + '</td>' +
      '<td class="sr-editable" onclick="srInlineEdit(this,' + i + ',\'date\')">' + tx.date + '</td>' +
      '<td class="sr-editable" onclick="srInlineEdit(this,' + i + ',\'description\')" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="' + (tx.description||'').replace(/"/g,'&quot;') + '">' + tx.description + '</td>' +
      '<td class="sr-editable" onclick="srInlineEdit(this,' + i + ',\'amount\')" style="font-weight:700">' + tx.amount.toLocaleString('ar-SA',{maximumFractionDigits:2}) + '</td>' +
      '<td class="sr-editable" onclick="srInlineEditType(this,' + i + ')" style="color:' + typeColor + ';font-weight:600">' + typeLabel + '</td>' +
      '<td class="sr-editable" onclick="srInlineEditCategory(this,' + i + ')">' + tx.category + '</td>' +
      '<td><span class="' + confClass + '">' + confLabel + '</span></td></tr>';
  }).join('');
}

// ---- Inline Editing ----
function srInlineEdit(td, idx, field) {
  if (td.querySelector('input')) return;
  var tx = aiAnalysisData.transactions[idx];
  var oldVal = field === 'amount' ? tx.amount.toLocaleString('ar-SA',{maximumFractionDigits:2}) : tx[field];
  var input = document.createElement('input');
  input.className = 'sr-edit-input';
  input.value = field === 'amount' ? tx.amount : tx[field];
  if (field === 'amount') input.type = 'number';
  td.textContent = '';
  td.appendChild(input);
  input.focus();
  input.select();
  function save() {
    var newVal = input.value.trim();
    if (field === 'amount') {
      var num = parseFloat(newVal);
      if (isNaN(num) || num <= 0) { td.textContent = oldVal; return; }
      tx.amount = num;
      td.textContent = num.toLocaleString('ar-SA',{maximumFractionDigits:2});
    } else {
      if (!newVal) { td.textContent = tx[field]; return; }
      tx[field] = newVal;
      td.textContent = newVal;
    }
    srRecalcStats();
    srRenderCategories(aiAnalysisData);
  }
  input.onblur = save;
  input.onkeydown = function(e) { if (e.key === 'Enter') input.blur(); if (e.key === 'Escape') { td.textContent = tx[field]; } };
}

function srInlineEditType(td, idx) {
  if (td.querySelector('select')) return;
  var tx = aiAnalysisData.transactions[idx];
  var sel = document.createElement('select');
  sel.className = 'sr-edit-select';
  sel.innerHTML = '<option value="income"' + (tx.type==='income'?' selected':'') + '>إيراد</option><option value="expense"' + (tx.type==='expense'?' selected':'') + '>مصروف</option>';
  td.textContent = '';
  td.appendChild(sel);
  sel.focus();
  function save() {
    tx.type = sel.value;
    td.textContent = tx.type === 'income' ? 'إيراد' : 'مصروف';
    td.style.color = tx.type === 'income' ? 'var(--green)' : 'var(--danger)';
    srRecalcStats(); srRenderCategories(aiAnalysisData);
  }
  sel.onblur = save;
  sel.onchange = function() { sel.blur(); };
}

function srInlineEditCategory(td, idx) {
  if (td.querySelector('select')) return;
  var tx = aiAnalysisData.transactions[idx];
  var cats = Object.keys(aiAnalysisData.categories);
  var sel = document.createElement('select');
  sel.className = 'sr-edit-select';
  sel.innerHTML = cats.map(function(c) { return '<option value="' + c + '"' + (c === tx.category ? ' selected' : '') + '>' + c + '</option>'; }).join('') +
    '<option value="__custom__">+ فئة جديدة...</option>';
  td.textContent = '';
  td.appendChild(sel);
  sel.focus();
  function save() {
    if (sel.value === '__custom__') {
      var newCat = prompt('اسم الفئة الجديدة:');
      if (!newCat) { td.textContent = tx.category; return; }
      tx.category = newCat;
    } else { tx.category = sel.value; }
    td.textContent = tx.category;
    tx.confidence = 'high';
    srRecalcStats(); srRenderCategories(aiAnalysisData); srRenderTransactionTable(aiAnalysisData.transactions);
  }
  sel.onblur = save;
  sel.onchange = function() { sel.blur(); };
}

// ---- Row Selection & Bulk Actions ----
function srToggleRow(idx, checked) {
  if (checked) srSelectedRows.add(idx); else srSelectedRows.delete(idx);
  var tr = document.querySelector('#sr-transactions-tbody tr[data-idx="' + idx + '"]');
  if (tr) tr.classList.toggle('selected', checked);
  srUpdateBulkToolbar();
}

function srToggleSelectAll(checked) {
  srSelectedRows.clear();
  document.querySelectorAll('.sr-row-check').forEach(function(cb) {
    cb.checked = checked;
    var idx = parseInt(cb.dataset.idx);
    if (checked) srSelectedRows.add(idx);
    var tr = cb.closest('tr');
    if (tr) tr.classList.toggle('selected', checked);
  });
  srUpdateBulkToolbar();
}

function srUpdateBulkToolbar() {
  var toolbar = document.getElementById('sr-bulk-toolbar');
  toolbar.style.display = srSelectedRows.size > 0 ? 'flex' : 'none';
  document.getElementById('sr-selected-count').textContent = srSelectedRows.size + ' محدد';
}

function srApplyBulkEdit() {
  var newCat = document.getElementById('sr-bulk-category').value;
  var newType = document.getElementById('sr-bulk-type').value;
  if (!newCat && !newType) { toast('اختر فئة أو نوع للتطبيق', 'error'); return; }
  srSelectedRows.forEach(function(idx) {
    var tx = aiAnalysisData.transactions[idx];
    if (newCat) { tx.category = newCat; tx.confidence = 'high'; }
    if (newType) tx.type = newType;
  });
  toast('تم تحديث ' + srSelectedRows.size + ' معاملة', 'success');
  srSelectedRows.clear();
  document.getElementById('sr-select-all').checked = false;
  srRecalcStats(); srRenderCategories(aiAnalysisData); srRenderTransactionTable(aiAnalysisData.transactions);
}

function srDeleteSelected() {
  if (srSelectedRows.size === 0) return;
  if (!confirm('حذف ' + srSelectedRows.size + ' معاملة؟')) return;
  var indices = Array.from(srSelectedRows).sort(function(a, b) { return b - a; });
  indices.forEach(function(idx) { aiAnalysisData.transactions.splice(idx, 1); });
  toast('تم حذف ' + indices.length + ' معاملة', 'success');
  srSelectedRows.clear();
  document.getElementById('sr-select-all').checked = false;
  srRecalcStats(); srRenderCategories(aiAnalysisData); srRenderTransactionTable(aiAnalysisData.transactions);
}

// ---- Confidence Filter ----
function srFilterByConfidence(level) {
  if (!aiAnalysisData) return;
  var filtered = level === 'all' ? aiAnalysisData.transactions :
    aiAnalysisData.transactions.filter(function(tx) { return tx.confidence === level; });
  srRenderTransactionTable(filtered);
}

// ---- Insights ----
function generateAIInsights(data) {
  var html = '<div style="background:#f0f9ff;border-right:4px solid #3b82f6;padding:16px;border-radius:8px;margin-bottom:16px">';
  html += '<div style="font-weight:700;color:#1e40af;margin-bottom:8px">📊 الملخص التنفيذي</div>';
  html += '<div style="font-size:14px;color:#1e3a8a;line-height:1.8">';
  html += 'تم تحليل <strong>' + data.transactions.length + '</strong> معاملة مالية ';
  html += 'بإجمالي إيرادات <strong>' + data.totalIncome.toLocaleString('ar-SA',{maximumFractionDigits:0}) + '</strong> ريال ';
  html += 'ومصروفات <strong>' + data.totalExpense.toLocaleString('ar-SA',{maximumFractionDigits:0}) + '</strong> ريال، ';
  if (data.netProfit >= 0)
    html += 'بصافي ربح قدره <strong style="color:var(--green)">' + data.netProfit.toLocaleString('ar-SA',{maximumFractionDigits:0}) + '</strong> ريال.';
  else
    html += 'بعجز قدره <strong style="color:var(--danger)">' + Math.abs(data.netProfit).toLocaleString('ar-SA',{maximumFractionDigits:0}) + '</strong> ريال.';
  html += '</div></div>';

  var highCount = data.transactions.filter(function(t){return t.confidence==='high';}).length;
  var medCount = data.transactions.filter(function(t){return t.confidence==='medium';}).length;
  var lowCount = data.transactions.filter(function(t){return t.confidence==='low';}).length;
  html += '<div style="background:#faf5ff;border-right:4px solid #a855f7;padding:16px;border-radius:8px;margin-bottom:16px">';
  html += '<div style="font-weight:700;color:#7e22ce;margin-bottom:8px">🎯 دقة التصنيف</div>';
  html += '<div style="font-size:13px;color:#581c87;display:flex;gap:16px;flex-wrap:wrap">';
  html += '<span class="confidence-high">عالي: ' + highCount + '</span>';
  html += '<span class="confidence-medium">متوسط: ' + medCount + '</span>';
  html += '<span class="confidence-low">منخفض: ' + lowCount + '</span></div></div>';

  var largestExp = null, largestAmt = 0;
  for (var cat in data.categories) {
    if (data.categories[cat].expense > largestAmt) { largestAmt = data.categories[cat].expense; largestExp = cat; }
  }
  if (largestExp && data.totalExpense > 0) {
    html += '<div style="background:#fef3c7;border-right:4px solid #f59e0b;padding:16px;border-radius:8px;margin-bottom:16px">';
    html += '<div style="font-weight:700;color:#92400e;margin-bottom:8px">💡 ملاحظة</div>';
    html += '<div style="font-size:14px;color:#78350f">أكبر بند مصروفات: <strong>' + largestExp + '</strong> بقيمة <strong>' +
      largestAmt.toLocaleString('ar-SA',{maximumFractionDigits:0}) + '</strong> ريال (' +
      ((largestAmt / data.totalExpense) * 100).toFixed(1) + '% من المصروفات)</div></div>';
  }

  html += '<div style="background:#dcfce7;border-right:4px solid #22c55e;padding:16px;border-radius:8px">';
  html += '<div style="font-weight:700;color:#166534;margin-bottom:8px">✅ توصية</div>';
  html += '<div style="font-size:14px;color:#14532d">';
  if (lowCount > 0) html += 'يوجد ' + lowCount + ' معاملة بثقة منخفضة — راجعها وعدّل تصنيفها من الجدول أدناه. ';
  html += 'بعد المراجعة، يمكنك حفظ التقرير أو مزامنة البيانات مع النظام.</div></div>';
  return html;
}

// ---- Sync to DB via API ----
function syncAIDataToDB() {
  if (!aiAnalysisData || !aiAnalysisData.transactions.length) { toast('لا توجد بيانات للمزامنة', 'error'); return; }
  if (!confirm('هل تريد مزامنة ' + aiAnalysisData.transactions.length + ' معاملة مع قاعدة البيانات؟')) return;
  srSetStep(5);

  var payload = aiAnalysisData.transactions.map(function(tx) {
    return {
      type: tx.type === 'income' ? 'إيراد' : 'مصروف',
      amount: tx.amount, category: tx.category, description: tx.description,
      tx_date: tx.date, committee_id: '', member_id: '', period_id: ''
    };
  });

  apiFetch('/api/transactions.php?bulk=1', {
    method: 'POST',
    body: JSON.stringify({ transactions: payload })
  }).then(function(res) {
    var msg = '✅ تمت إضافة: ' + res.inserted + ' معاملة';
    if (res.duplicates_skipped > 0) msg += ' • ⚠️ تم تجاهل: ' + res.duplicates_skipped + ' مكررة';
    toast(msg, 'success');
    if (res.data && res.data.length) { res.data.forEach(function(row) { State.getBudget().push(row); }); }
    renderDashboard();
  }).catch(function(err) { toast('خطأ في المزامنة: ' + err.message, 'error'); });
}

// ---- Download CSV ----
function downloadAIReport() {
  if (!aiAnalysisData) { toast('لا توجد بيانات', 'error'); return; }
  var csv = 'التاريخ,الوصف,المبلغ,النوع,الفئة,الثقة\n';
  aiAnalysisData.transactions.forEach(function(tx) {
    var typeAr = tx.type === 'income' ? 'إيراد' : 'مصروف';
    var confAr = tx.confidence === 'high' ? 'عالي' : tx.confidence === 'medium' ? 'متوسط' : 'منخفض';
    csv += '"' + tx.date + '","' + (tx.description||'').replace(/"/g,'""') + '",' + tx.amount + ',"' + typeAr + '","' + tx.category + '","' + confAr + '"\n';
  });
  var blob = new Blob(['\uFEFF' + csv], {type: 'text/csv;charset=utf-8;'});
  var link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'smart-report-' + new Date().toISOString().split('T')[0] + '.csv';
  link.click();
  toast('تم تحميل التقرير بنجاح', 'success');
}

// ---- Reset ----
function resetAIAnalysis() {
  aiAnalysisData = null; srWorkbook = null; srRawData = null; srFileName = ''; srSelectedRows.clear();
  document.getElementById('sr-upload-card').style.display = 'block';
  document.getElementById('sr-preview-card').style.display = 'none';
  document.getElementById('ai-processing').style.display = 'none';
  document.getElementById('ai-results').style.display = 'none';
  document.getElementById('file-input-ai').value = '';
  document.getElementById('ai-progress-bar').style.width = '0%';
  document.getElementById('ai-progress-pct').textContent = '0%';
  document.getElementById('upload-file-info').style.display = 'none';
  document.getElementById('sr-cleaning-info').style.display = 'none';
  srSetStep(1);
}

// ---- Save Report ----
function srSaveReport() {
  if (!aiAnalysisData) { toast('لا توجد بيانات للحفظ', 'error'); return; }
  var title = prompt('عنوان التقرير:', 'تقرير ' + new Date().toLocaleDateString('ar-SA'));
  if (!title) return;
  var desc = prompt('وصف مختصر (اختياري):', '');
  srSetStep(5);

  ReportsAPI.save({
      title: title, description: desc || '', report_type: 'smart_analysis',
      report_data: { transactions: aiAnalysisData.transactions, categories: aiAnalysisData.categories, analyzedAt: aiAnalysisData.analyzedAt },
      summary: { totalIncome: aiAnalysisData.totalIncome, totalExpense: aiAnalysisData.totalExpense, netProfit: aiAnalysisData.netProfit },
      file_name: srFileName, total_transactions: aiAnalysisData.transactions.length,
      total_income: aiAnalysisData.totalIncome, total_expense: aiAnalysisData.totalExpense, net_profit: aiAnalysisData.netProfit
  }).then(function() {
    toast('تم حفظ التقرير: ' + title, 'success');
    srLoadSavedReports('active');
  }).catch(function(err) { toast('خطأ في حفظ التقرير: ' + err.message, 'error'); });
}

// ---- Saved Reports List ----
function srLoadSavedReports(status, tabEl) {
  if (tabEl) {
    tabEl.parentElement.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('active'); });
    tabEl.classList.add('active');
  }
  ReportsAPI.getAll({ status: status || 'active' }).then(function(res) {
    var list = res.data || [];
    var container = document.getElementById('sr-saved-list');
    if (!list.length) {
      container.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div><p>' +
        (status === 'archived' ? 'لا توجد تقارير مؤرشفة.' : 'لا توجد تقارير محفوظة بعد.') + '</p></div>';
      return;
    }
    container.innerHTML = list.map(function(r) {
      var dateStr = new Date(r.created_at).toLocaleDateString('ar-SA');
      var isArchived = r.status === 'archived';
      return '<div class="sr-saved-item">' +
        '<div class="sr-saved-icon">📊</div>' +
        '<div class="sr-saved-info"><div class="sr-saved-title">' + r.title + '</div>' +
        '<div class="sr-saved-meta"><span>📅 ' + dateStr + '</span><span>📋 ' + r.total_transactions + ' معاملة</span>' +
        '<span style="color:var(--green)">💰 ' + parseFloat(r.total_income).toLocaleString('ar-SA',{maximumFractionDigits:0}) + '</span>' +
        '<span style="color:var(--danger)">💸 ' + parseFloat(r.total_expense).toLocaleString('ar-SA',{maximumFractionDigits:0}) + '</span>' +
        (r.file_name ? '<span>📄 ' + r.file_name + '</span>' : '') + '</div></div>' +
        '<div class="sr-saved-actions">' +
        '<button class="btn btn-xs btn-primary" onclick="srViewReport(\'' + r.id + '\')">👁 عرض</button>' +
        (isArchived ? '<button class="btn btn-xs btn-success" onclick="srRestoreReport(\'' + r.id + '\')">♻️ استعادة</button>' :
          '<button class="btn btn-xs btn-outline" onclick="srArchiveReport(\'' + r.id + '\')">🗄️ أرشفة</button>') +
        '<button class="btn btn-xs btn-danger" onclick="srDeleteReport(\'' + r.id + '\')">🗑️</button></div></div>';
    }).join('');
  }).catch(function() {
    document.getElementById('sr-saved-list').innerHTML = '<div class="empty-state"><p style="color:var(--danger)">خطأ في تحميل التقارير</p></div>';
  });
}

function srViewReport(id) {
  ReportsAPI.getOne(id).then(function(res) {
    var r = res.data;
    if (!r || !r.report_data) { toast('بيانات التقرير غير متوفرة', 'error'); return; }
    aiAnalysisData = {
      transactions: r.report_data.transactions || [], categories: r.report_data.categories || {},
      totalIncome: parseFloat(r.total_income) || 0, totalExpense: parseFloat(r.total_expense) || 0,
      netProfit: parseFloat(r.net_profit) || 0,
      analyzedAt: r.report_data.analyzedAt || r.created_at, fileName: r.file_name || ''
    };
    srFileName = r.file_name || '';
    displayAIResults();
    toast('تم تحميل التقرير: ' + r.title, 'success');
  }).catch(function(err) { toast('خطأ: ' + err.message, 'error'); });
}

function srArchiveReport(id) {
  if (!confirm('أرشفة هذا التقرير؟')) return;
  ReportsAPI.archive(id)
    .then(function() { toast('تم الأرشفة', 'success'); srLoadSavedReports('active'); })
    .catch(function(err) { toast('خطأ: ' + err.message, 'error'); });
}

function srRestoreReport(id) {
  ReportsAPI.restore(id)
    .then(function() { toast('تم الاستعادة', 'success'); srLoadSavedReports('archived'); })
    .catch(function(err) { toast('خطأ: ' + err.message, 'error'); });
}

function srDeleteReport(id) {
  if (!confirm('حذف هذا التقرير نهائياً؟')) return;
  ReportsAPI.remove(id)
    .then(function() { toast('تم الحذف', 'success'); srLoadSavedReports('active'); })
    .catch(function(err) { toast('خطأ: ' + err.message, 'error'); });
}

// ---- Smart Print ----
function srPrintReport() {
  if (!aiAnalysisData) { toast('لا توجد بيانات للطباعة', 'error'); return; }
  var data = aiAnalysisData;
  var win = window.open('', '_blank');
  var cats = Object.entries(data.categories).sort(function(a,b) { return (b[1].income+b[1].expense)-(a[1].income+a[1].expense); });
  var grandTotal = data.totalIncome + data.totalExpense || 1;

  var catRows = cats.map(function(entry) {
    var c = entry[0], d = entry[1], t = d.income + d.expense;
    return '<tr><td>' + c + '</td><td>' + d.count + '</td><td>' + d.income.toLocaleString('ar-SA',{maximumFractionDigits:0}) +
      '</td><td>' + d.expense.toLocaleString('ar-SA',{maximumFractionDigits:0}) + '</td><td>' + t.toLocaleString('ar-SA',{maximumFractionDigits:0}) +
      '</td><td>' + ((t/grandTotal)*100).toFixed(1) + '%</td></tr>';
  }).join('');

  var txRows = data.transactions.map(function(tx, i) {
    return '<tr><td>' + (i+1) + '</td><td>' + tx.date + '</td><td>' + tx.description + '</td><td>' +
      tx.amount.toLocaleString('ar-SA',{maximumFractionDigits:2}) + '</td><td>' + (tx.type==='income'?'إيراد':'مصروف') + '</td><td>' + tx.category + '</td></tr>';
  }).join('');

  var html = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>التقرير المالي الذكي</title>';
  html += '<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Cairo,Tahoma,sans-serif;direction:rtl;padding:30px;color:#1a1a1a;font-size:13px;line-height:1.6}';
  html += '.header{text-align:center;margin-bottom:30px;padding-bottom:16px;border-bottom:3px double #1a5c32}';
  html += '.header h1{font-size:22px;color:#1a5c32;margin-bottom:4px}.header .sub{font-size:14px;color:#666}.header .date{font-size:12px;color:#999}';
  html += '.stats{display:flex;justify-content:space-around;margin:20px 0;gap:16px}';
  html += '.stat-box{flex:1;text-align:center;padding:16px;border:1px solid #ddd;border-radius:10px}';
  html += '.stat-box .val{font-size:20px;font-weight:900;margin:4px 0}.stat-box .lbl{font-size:11px;color:#777}';
  html += 'table{width:100%;border-collapse:collapse;margin:16px 0}th{background:#f0f5f1;padding:8px 10px;font-size:11px;text-align:right;border-bottom:2px solid #ddd}';
  html += 'td{padding:7px 10px;border-bottom:1px solid #eee;font-size:12px}tr:nth-child(even){background:#fafafa}';
  html += 'h2{font-size:15px;color:#1a5c32;margin:24px 0 10px;padding-bottom:6px;border-bottom:1px solid #eee}';
  html += '.footer{text-align:center;margin-top:30px;padding-top:12px;border-top:2px solid #eee;font-size:11px;color:#999}';
  html += '.net-pos{color:#166534}.net-neg{color:#991b1b}';
  html += '@media print{@page{size:A4;margin:15mm}body{padding:0}}</style></head><body>';

  html += '<div class="header"><h1>🏛️ مجلس عائلة العوامي</h1><div class="sub">التقرير المالي الذكي</div>';
  html += '<div class="date">' + new Date().toLocaleDateString('ar-SA',{year:'numeric',month:'long',day:'numeric'}) + '</div>';
  if (srFileName) html += '<div class="date">الملف: ' + srFileName + '</div>';
  html += '</div>';

  html += '<div class="stats">';
  html += '<div class="stat-box"><div class="lbl">المعاملات</div><div class="val">' + data.transactions.length + '</div></div>';
  html += '<div class="stat-box"><div class="lbl">الإيرادات</div><div class="val" style="color:#166534">' + data.totalIncome.toLocaleString('ar-SA',{maximumFractionDigits:0}) + '</div><div class="lbl">ريال</div></div>';
  html += '<div class="stat-box"><div class="lbl">المصروفات</div><div class="val" style="color:#991b1b">' + data.totalExpense.toLocaleString('ar-SA',{maximumFractionDigits:0}) + '</div><div class="lbl">ريال</div></div>';
  html += '<div class="stat-box"><div class="lbl">الصافي</div><div class="val ' + (data.netProfit>=0?'net-pos':'net-neg') + '">' + data.netProfit.toLocaleString('ar-SA',{maximumFractionDigits:0}) + '</div><div class="lbl">ريال</div></div>';
  html += '</div>';

  html += '<h2>🏷️ توزيع حسب الفئة</h2>';
  html += '<table><thead><tr><th>الفئة</th><th>العدد</th><th>إيراد</th><th>مصروف</th><th>الإجمالي</th><th>النسبة</th></tr></thead><tbody>' + catRows + '</tbody></table>';

  html += '<h2>📋 تفاصيل المعاملات</h2>';
  html += '<table><thead><tr><th>#</th><th>التاريخ</th><th>الوصف</th><th>المبلغ</th><th>النوع</th><th>الفئة</th></tr></thead><tbody>' + txRows + '</tbody></table>';

  html += '<div class="footer">تم إنشاء هذا التقرير بواسطة نظام مجلس عائلة العوامي — ' + new Date().toLocaleDateString('ar-SA') + '</div>';
  html += '</body></html>';

  win.document.write(html);
  win.document.close();
  setTimeout(function() { win.print(); }, 600);
}

// =====================================================

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
  document.getElementById('report-committees-tbody').innerHTML=State.getCommittees().map(c=>{ const mems=(State.getCommitteeMembers()[c.id]||c.members||[]).length; const evs=State.getEvents().filter(e=>e.committeeId===c.id).length; const spent=State.getTransactions().filter(t=>t.committee===c.id&&t.type==='مصروف').reduce((s,t)=>s+t.amount,0); return `<tr><td>${c.icon} ${c.name} ${c.advisory?'<span class="badge badge-purple">استشارية</span>':''}</td><td>${mems}</td><td>${evs}</td><td style="font-weight:700;color:var(--danger)">${fmt(spent)} ريال</td></tr>`; }).join('');
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
