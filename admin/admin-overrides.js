// ============================================================
// admin-overrides.js
// يُستدعى بعد تحميل الأدمن — يعيد تعريف الدوال لتستخدم API
// ============================================================

// –– حفظ الجلسة ––
async function saveMeeting() {
const date  = document.getElementById(‘meeting-date’).value;
const time  = document.getElementById(‘meeting-time’).value || ‘10:00’;
const title = document.getElementById(‘meeting-title’).value.trim() || ‘الجلسة العمومية للمجلس’;

```
if (!date) { toast('اختر تاريخ الجلسة', 'error'); return; }

try {
    await AdminMeeting.save(date, time, title);
    toast('تم حفظ موعد الجلسة ✅');

    const preview = document.getElementById('meeting-preview');
    const previewText = document.getElementById('meeting-preview-text');
    const dateObj = new Date(`${date}T${time}`);
    previewText.textContent = `${title} - ${dateObj.toLocaleDateString('ar-SA', { weekday:'long', year:'numeric', month:'long', day:'numeric', hour:'2-digit', minute:'2-digit' })}`;
    preview.style.display = 'block';
} catch (e) {
    toast('فشل الحفظ: ' + e.message, 'error');
}
```

}

async function hideMeeting() {
try {
await AdminMeeting.hide();
toast(DB.nextMeeting?.visible ? ‘تم إظهار العد التنازلي’ : ‘تم إخفاء العد التنازلي’);
} catch (e) {
toast(’فشل: ’ + e.message, ‘error’);
}
}

async function clearMeeting() {
confirm2(‘هل تريد حذف موعد الجلسة القادمة؟’, async () => {
try {
await AdminMeeting.delete();
document.getElementById(‘meeting-date’).value = ‘’;
document.getElementById(‘meeting-time’).value = ‘10:00’;
document.getElementById(‘meeting-title’).value = ‘الجلسة العمومية للمجلس’;
document.getElementById(‘meeting-preview’).style.display = ‘none’;
toast(‘تم حذف الجلسة’);
} catch (e) {
toast(’فشل: ’ + e.message, ‘error’);
}
});
}

// –– حفظ إعدادات الموقع ––
async function saveHeaderSettings() {
try {
await AdminSettings.saveHeader(
document.getElementById(‘ws-header-title’).value,
document.getElementById(‘ws-header-subtitle’).value
);
toast(‘تم حفظ إعدادات الهيدر ✅’);
} catch (e) { toast(’فشل: ’ + e.message, ‘error’); }
}

async function saveHeroSettings() {
try {
await AdminSettings.saveHero(
document.getElementById(‘ws-hero-title’).value,
document.getElementById(‘ws-hero-desc’).value
);
toast(‘تم حفظ إعدادات البانر ✅’);
} catch (e) { toast(’فشل: ’ + e.message, ‘error’); }
}

async function saveStatsSettings() {
try {
await AdminSettings.saveStats(
parseInt(document.getElementById(‘ws-stats-years’).value) || 0,
parseInt(document.getElementById(‘ws-stats-committees’).value) || 0,
document.getElementById(‘ws-stats-members’).value
);
toast(‘تم حفظ الإحصائيات ✅’);
} catch (e) { toast(’فشل: ’ + e.message, ‘error’); }
}

async function saveAboutSettings() {
try {
await AdminSettings.saveAbout(
document.getElementById(‘ws-about-mission’).value.trim(),
document.getElementById(‘ws-about-vision’).value.trim()
);
toast(‘تم حفظ “عن المجلس” ✅’);
} catch (e) { toast(’فشل: ’ + e.message, ‘error’); }
}

// –– المناصب ––
async function savePosition() {
const role  = document.getElementById(‘position-role’).value.trim();
const name  = document.getElementById(‘position-name’).value.trim();
if (!role || !name) { toast(‘المنصب والاسم مطلوبان’, ‘error’); return; }

```
const tasksText = document.getElementById('position-tasks').value.trim();
const data = {
    role, name,
    icon:  document.getElementById('position-icon').value.trim() || '👤',
    type:  document.getElementById('position-type').value,
    tasks: tasksText ? tasksText.split('\n').map(t => t.trim()).filter(Boolean) : [],
};

const positions = DB.settings.councilPositions ?? [];
const idx = document.getElementById('position-index').value;

if (idx !== '') positions[idx] = data;
else positions.push(data);

try {
    await AdminSettings.savePositions(positions);
    closeModal('modal-position');
    toast(idx !== '' ? 'تم التحديث' : 'تمت الإضافة');
    renderPositionsList();
} catch (e) { toast('فشل: ' + e.message, 'error'); }
```

}

async function deletePosition() {
const idx = document.getElementById(‘position-index’).value;
const positions = DB.settings.councilPositions ?? [];
const p = positions[idx];
confirm2(`حذف منصب "${p?.role}"؟`, async () => {
positions.splice(idx, 1);
try {
await AdminSettings.savePositions(positions);
closeModal(‘modal-position’);
toast(‘تم الحذف’);
renderPositionsList();
} catch (e) { toast(’فشل: ’ + e.message, ‘error’); }
});
}

// –– القيم ––
async function saveValue() {
const icon  = document.getElementById(‘value-icon’).value.trim();
const title = document.getElementById(‘value-title’).value.trim();
if (!icon || !title) { toast(‘الأيقونة والعنوان مطلوبان’, ‘error’); return; }

```
const data   = { icon, title, desc: document.getElementById('value-desc').value.trim() };
const values = DB.settings.values ?? [];
const idx    = document.getElementById('value-index').value;

if (idx !== '') values[idx] = data;
else values.push(data);

try {
    await AdminSettings.saveValues(values);
    closeModal('modal-value');
    toast(idx !== '' ? 'تم التحديث' : 'تمت الإضافة');
    renderValuesList();
} catch (e) { toast('فشل: ' + e.message, 'error'); }
```

}

async function deleteValue() {
const idx    = document.getElementById(‘value-index’).value;
const values = DB.settings.values ?? [];
const v      = values[idx];
confirm2(`حذف قيمة "${v?.title}"؟`, async () => {
values.splice(idx, 1);
try {
await AdminSettings.saveValues(values);
closeModal(‘modal-value’);
toast(‘تم الحذف’);
renderValuesList();
} catch (e) { toast(’فشل: ’ + e.message, ‘error’); }
});
}

// –– الشعار ––
async function saveLogo() {
const preview = document.getElementById(‘logo-preview-img’).src;
if (!preview || preview === window.location.href) {
toast(‘لم يتم اختيار شعار’, ‘error’);
return;
}
try {
await AdminSettings.saveLogo(preview);
toast(‘تم حفظ الشعار ✅’);
document.getElementById(‘current-logo-preview’).innerHTML = `<img src="${preview}" style="max-width:44px;max-height:44px">`;
document.getElementById(‘logo-preview-container’).style.display = ‘none’;
document.getElementById(‘logo-upload’).value = ‘’;
} catch (e) { toast(’فشل: ’ + e.message, ‘error’); }
}

async function resetLogoToDefault() {
confirm2(‘استعادة الشعار الافتراضي؟’, async () => {
try {
await AdminSettings.saveLogo(null);
toast(‘تم استعادة الشعار الافتراضي’);
document.getElementById(‘current-logo-preview’).innerHTML = ` <svg width="44" height="44" viewBox="0 0 80 80" fill="none"> <path d="M55 12 C58 8, 65 10, 64 18 C63 26, 54 30, 50 38 C46 46, 48 56, 42 62 C36 68, 26 66, 24 58 C22 50, 30 44, 32 36" stroke="#47915C" stroke-width="6" stroke-linecap="round" fill="none"/> <path d="M32 36 C28 44, 20 46, 20 54 C20 62, 28 66, 34 62" stroke="#47915C" stroke-width="5" stroke-linecap="round" fill="none"/> <circle cx="34" cy="62" r="5" fill="#47915C"/> </svg>`;
} catch (e) { toast(’فشل: ’ + e.message, ‘error’); }
});
}

// –– الميديا ––
async function saveMedia() {
const title = document.getElementById(‘media-title’).value.trim();
const url   = document.getElementById(‘media-url’).value.trim();
if (!title || !url) { toast(‘الرجاء ملء العنوان والرابط’, ‘error’); return; }

```
const tags = document.getElementById('media-tags').value
    .split(',').map(t => t.trim()).filter(Boolean);

try {
    await AdminMedia.create({
        title,
        type:  document.getElementById('media-type').value,
        url,
        date:  document.getElementById('media-date').value || today(),
        tags,
    });
    toast('تم إضافة الميديا ✅');
    closeModal('modal-add-media');
    DB.media = (await MediaAPI.getAll()).media ?? [];
    renderMediaList();
} catch (e) { toast('فشل: ' + e.message, 'error'); }
```

}

async function deleteMedia(id) {
if (!confirm(‘حذف هذه الميديا؟’)) return;
try {
await AdminMedia.delete(id);
toast(‘تم الحذف’);
renderMediaList();
} catch (e) { toast(’فشل: ’ + e.message, ‘error’); }
}

// –– شجرة العائلة ––
async function saveBranch() {
const name = document.getElementById(‘branch-name’).value.trim();
if (!name) { toast(‘اسم الفرع مطلوب’, ‘error’); return; }

```
const id          = document.getElementById('branch-id').value || undefined;
const membersText = document.getElementById('branch-members').value.trim();
const members     = membersText ? membersText.split('\n').map(m => m.trim()).filter(Boolean) : [];

try {
    const branch = await AdminBranch.save({
        id,
        name,
        head:    document.getElementById('branch-head').value.trim(),
        color:   document.getElementById('branch-color').value,
        members,
        notes:   document.getElementById('branch-notes').value.trim(),
    });
    closeModal('modal-add-branch');
    toast(id ? 'تم التحديث ✅' : 'تمت الإضافة ✅');
    renderFamilyTree();
} catch (e) { toast('فشل: ' + e.message, 'error'); }
```

}

async function deleteBranchConfirm() {
const id = document.getElementById(‘branch-id’).value;
const b  = DB.branches.find(x => x.id === id);
confirm2(`حذف فرع "${b?.name}" نهائياً؟`, async () => {
try {
await AdminBranch.delete(id);
closeModal(‘modal-add-branch’);
toast(‘تم الحذف’);
renderFamilyTree();
} catch (e) { toast(’فشل: ’ + e.message, ‘error’); }
});
}

// –– فعاليات ––
async function addEvent() {
const name = document.getElementById(‘ev-name’).value.trim();
if (!name) { toast(‘الاسم مطلوب’, ‘error’); return; }

```
const imgFiles = document.getElementById('ev-images').files;
const images   = [];

for (const file of imgFiles) {
    if (file.size > 2 * 1024 * 1024) { toast('حجم الصورة أكبر من 2MB', 'error'); continue; }
    await new Promise(resolve => {
        const reader = new FileReader();
        reader.onload = e => { images.push({ name: file.name, data: e.target.result }); resolve(); };
        reader.readAsDataURL(file);
    });
}

try {
    await AdminEvent.create({
        name,
        committeeId:  document.getElementById('ev-committee').value,
        status:       document.getElementById('ev-status').value,
        date:         document.getElementById('ev-date').value || '',
        budget:       parseFloat(document.getElementById('ev-budget').value) || 0,
        participants: parseInt(document.getElementById('ev-participants').value) || 0,
        lead:         document.getElementById('ev-lead').value,
        notes:        document.getElementById('ev-notes').value,
        icon:         '🎉',
        images,
    });
    closeModal('modal-event');
    toast('تم ✅');
    renderEvents();
    renderCalendar();
    renderDashboard();
} catch (e) { toast('فشل: ' + e.message, 'error'); }
```

}

async function deleteEvent(id) {
confirm2(‘حذف الفعالية؟’, async () => {
try {
await AdminEvent.delete(id);
toast(‘تم الحذف’);
renderEvents(); renderCalendar(); renderDashboard();
} catch (e) { toast(’فشل: ’ + e.message, ‘error’); }
});
}

// –– التصويت ––
async function createPoll() {
const title   = document.getElementById(‘poll-title’).value.trim();
const optsRaw = document.getElementById(‘poll-options’).value.trim();
if (!title || !optsRaw) { toast(‘العنوان والخيارات مطلوبة’, ‘error’); return; }

```
const options = optsRaw.split('\n').map(o => o.trim()).filter(Boolean);
if (options.length < 2) { toast('خيارين على الأقل', 'error'); return; }

try {
    await AdminPoll.create({
        title,
        options,
        committee: document.getElementById('poll-committee').value,
        end:       document.getElementById('poll-end').value || '',
    });
    toast('تم إنشاء التصويت ✅');
    renderVoting();
    document.getElementById('poll-title').value   = '';
    document.getElementById('poll-options').value = '';
} catch (e) { toast('فشل: ' + e.message, 'error'); }
```

}

async function vote(pollId, optIdx) {
try { await AdminPoll.vote(pollId, optIdx); renderVoting(); }
catch (e) { toast(‘فشل التصويت’, ‘error’); }
}

async function closePollF(id) {
try { await AdminPoll.close(id); toast(‘تم إغلاق التصويت’); renderVoting(); }
catch (e) { toast(‘فشل’, ‘error’); }
}

async function deletePollF(id) {
confirm2(‘حذف التصويت؟’, async () => {
try { await AdminPoll.delete(id); toast(‘تم الحذف’); renderVoting(); }
catch (e) { toast(‘فشل’, ‘error’); }
});
}

// –– الاستيراد والتصدير ––
async function exportData() {
const dataStr  = JSON.stringify(DB, null, 2);
const dataBlob = new Blob([dataStr], { type: ‘application/json’ });
const url      = URL.createObjectURL(dataBlob);
const a        = document.createElement(‘a’);
a.href         = url;
a.download     = `awami-data-${new Date().toISOString().split('T')[0]}.json`;
a.click();
URL.revokeObjectURL(url);
toast(‘تم تصدير البيانات 📥’);
}

// –– تحميل بيانات الإعدادات في الـ UI ––
function renderSettings() {
document.getElementById(‘stats-members’).textContent = DB.members.length;
document.getElementById(‘stats-events’).textContent  = DB.events.length;
document.getElementById(‘stats-tx’).textContent      = DB.transactions.length;
document.getElementById(‘stats-size’).textContent    = (new Blob([JSON.stringify(DB)]).size / 1024).toFixed(1);

```
if (DB.nextMeeting) {
    const dt = (DB.nextMeeting.date ?? '').split('T');
    document.getElementById('meeting-date').value  = dt[0] ?? '';
    document.getElementById('meeting-time').value  = dt[1] ? dt[1].slice(0, 5) : '10:00';
    document.getElementById('meeting-title').value = DB.nextMeeting.title ?? 'الجلسة العمومية للمجلس';
}
```

}

// –– countdown reads from DB.nextMeeting ––
function updateCountdown() {
const widget  = document.getElementById(‘countdown-widget’);
const display = document.getElementById(‘countdown-display’);
const dateEl  = document.getElementById(‘countdown-date’);

```
if (!DB.nextMeeting || DB.nextMeeting.visible === false) {
    if (widget) widget.style.display = 'none';
    return;
}
if (widget) widget.style.display = 'block';

const target = new Date(DB.nextMeeting.date);
const diff   = target - new Date();

if (diff < 0) { display.textContent = 'انتهت'; dateEl.textContent = ''; return; }

const days = Math.floor(diff / 864e5);
const hrs  = Math.floor((diff % 864e5) / 36e5);
const mins = Math.floor((diff % 36e5) / 6e4);
display.textContent = `${days} يوم ${hrs} س ${mins} د`;
dateEl.textContent  = target.toLocaleDateString('ar-SA', { weekday:'short', year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
```

}
setInterval(updateCountdown, 60000);
