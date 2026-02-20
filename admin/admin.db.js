// ============================================================
// admin-db.js — طبقة البيانات للأدمن
// يستبدل كل localStorage بـ API calls حقيقية
// ============================================================

// حالة التطبيق في الذاكرة (cache فقط، المصدر الحقيقي هو قاعدة البيانات)
let DB = {
settings:    {},
members:     [],
periods:     [],
payments:    [],
transactions:[],
events:      [],
polls:       [],
branches:    [],
media:       [],
committeeMembersMap: {}, // { committeeId: [memberId, …] }
nextMeeting: null,
};

// ============================================================
// تحميل كل البيانات عند بدء التشغيل
// ============================================================
async function loadAllData() {
showGlobalLoading(true);
try {
const [
settingsRes,
membersRes,
periodsRes,
paymentsRes,
transactionsRes,
eventsRes,
pollsRes,
branchesRes,
meetingRes,
] = await Promise.all([
SettingsAPI.get(),
MembersAPI.getAll(),
PeriodsAPI.getAll(),
PaymentsAPI.getAll(),
TransactionsAPI.getAll(),
EventsAPI.getAll(),
PollsAPI.getAll(),
BranchesAPI.getAll(),
MeetingAPI.get(),
]);

```
    DB.settings     = settingsRes.settings  ?? {};
    DB.members      = membersRes.data        ?? [];
    DB.periods      = periodsRes.data        ?? [];
    DB.payments     = paymentsRes.data       ?? [];
    DB.transactions = transactionsRes.data   ?? [];
    DB.events       = eventsRes.data         ?? [];
    DB.polls        = pollsRes.data          ?? [];
    DB.branches     = branchesRes.branches   ?? [];
    DB.nextMeeting  = meetingRes.nextMeeting ?? null;

    // بناء خريطة أعضاء اللجان
    buildCommitteeMembersMap();

    // تحويل بيانات الأعضاء للصيغة الداخلية
    DB.members = DB.members.map(normalizeMemeber);
    DB.payments = DB.payments.map(normalizePayment);
    DB.transactions = DB.transactions.map(normalizeTx);
    DB.events = DB.events.map(normalizeEvent);
    DB.polls = DB.polls.map(normalizePoll);

} catch (e) {
    console.error('فشل تحميل البيانات:', e);
    showError('فشل الاتصال بقاعدة البيانات: ' + e.message);
} finally {
    showGlobalLoading(false);
}
```

}

// –– Normalizers: تحويل بيانات API للصيغة الداخلية ––
function normalizeMemeber(m) {
return {
id:       m.id,
name:     m.name,
family:   m.family ?? ‘’,
phone:    m.phone  ?? ‘’,
idNum:    m.id_num ?? m.idNum ?? ‘’,
joinDate: m.join_date ?? m.joinDate ?? ‘’,
status:   m.status ?? ‘نشط’,
notes:    m.notes  ?? ‘’,
branchId: m.branch_id ?? ‘’,
};
}
function normalizePayment(p) {
return {
id:       p.id,
memberId: p.member_id ?? p.memberId,
periodId: p.period_id ?? p.periodId,
amount:   parseFloat(p.amount ?? 0),
required: parseFloat(p.required ?? 0),
date:     p.pay_date ?? p.date ?? ‘’,
method:   p.method   ?? ‘’,
status:   p.status   ?? ‘لم يدفع’,
notes:    p.notes    ?? ‘’,
};
}
function normalizeTx(t) {
return {
id:          t.id,
type:        t.type,
amount:      parseFloat(t.amount ?? 0),
category:    t.category    ?? ‘’,
committee:   t.committee_id ?? t.committee ?? ‘’,
desc:        t.description ?? t.desc ?? ‘’,
date:        t.tx_date    ?? t.date ?? ‘’,
};
}
function normalizeEvent(e) {
return {
id:           e.id,
name:         e.name,
committeeId:  e.committeeId ?? e.committee_id ?? ‘’,
status:       e.status,
date:         e.date ?? e.event_date ?? ‘’,
budget:       parseFloat(e.budget ?? 0),
participants: parseInt(e.participants ?? 0),
lead:         e.lead ?? ‘’,
notes:        e.notes ?? ‘’,
icon:         e.icon ?? ‘🎉’,
images:       e.images ?? [],
};
}
function normalizePoll(p) {
return {
id:          p.id,
title:       p.title,
options:     p.options ?? [],
committee:   p.committee_id ?? p.committee ?? ‘’,
end:         p.end_date ?? p.end ?? ‘’,
active:      p.is_active !== undefined ? Boolean(p.is_active) : (p.active ?? true),
created:     p.created_date ?? p.created ?? ‘’,
};
}

// بناء خريطة اللجان من بيانات الأعضاء
function buildCommitteeMembersMap() {
DB.committeeMembersMap = {};
// تُبنى من حقل committees في كل عضو (إن وُجد)
DB.members.forEach(m => {
(m.committees ?? []).forEach(cid => {
if (!DB.committeeMembersMap[cid]) DB.committeeMembersMap[cid] = [];
DB.committeeMembersMap[cid].push(m.id);
});
});
}

// ============================================================
// MEMBERS
// ============================================================
const AdminMember = {
async save(id, data) {
const payload = {
name:      data.name,
family:    data.family   ?? ‘’,
phone:     data.phone    ?? ‘’,
id_num:    data.idNum    ?? ‘’,
join_date: data.joinDate ?? null,
status:    data.status   ?? ‘نشط’,
notes:     data.notes    ?? ‘’,
};
let result;
if (id) {
result = await MembersAPI.update(id, payload);
const idx = DB.members.findIndex(m => m.id === id);
if (idx >= 0) DB.members[idx] = normalizeMemeber(result.data ?? { id, …payload });
} else {
result = await MembersAPI.create(payload);
DB.members.push(normalizeMemeber(result.data));
}
return result;
},

```
async delete(id) {
    await MembersAPI.delete(id);
    DB.members = DB.members.filter(m => m.id !== id);
    DB.payments = DB.payments.filter(p => p.memberId !== id);
},

async addToCommittee(committeeId, memberId) {
    await MembersAPI.addToCommittee(committeeId, memberId);
    if (!DB.committeeMembersMap[committeeId]) DB.committeeMembersMap[committeeId] = [];
    if (!DB.committeeMembersMap[committeeId].includes(memberId))
        DB.committeeMembersMap[committeeId].push(memberId);
},

async removeFromCommittee(committeeId, memberId) {
    await MembersAPI.removeFromCommittee(committeeId, memberId);
    DB.committeeMembersMap[committeeId] = (DB.committeeMembersMap[committeeId] ?? []).filter(id => id !== memberId);
},
```

};

// ============================================================
// PAYMENTS
// ============================================================
const AdminPayment = {
async save(memberId, data) {
const p = curPeriod();
if (!p) return;
const existing = DB.payments.find(x => x.memberId === memberId && x.periodId === p.id);

```
    const payload = {
        member_id:  memberId,
        period_id:  p.id,
        amount:     data.amount  ?? 0,
        required:   p.feeAmount,
        pay_date:   data.date    ?? null,
        method:     data.method  ?? '',
        status:     data.status  ?? 'لم يدفع',
        notes:      data.notes   ?? '',
    };

    if (existing) {
        const r = await PaymentsAPI.update(existing.id, payload);
        const idx = DB.payments.findIndex(x => x.id === existing.id);
        DB.payments[idx] = normalizePayment({ ...payload, id: existing.id, member_id: memberId, period_id: p.id });
    } else {
        const r = await PaymentsAPI.save(payload);
        DB.payments.push(normalizePayment(r.data ?? { ...payload, id: uid() }));
    }

    // تلقائياً أضف معاملة إيراد إن كان الدفع مكتملاً
    if ((data.status === 'مدفوع') && data.amount > 0) {
        const m = DB.members.find(x => x.id === memberId);
        const hasTx = DB.transactions.find(t =>
            t.type === 'إيراد' &&
            t.desc.includes(m?.name ?? '') &&
            t.date === (data.date ?? today())
        );
        if (!hasTx) {
            await AdminTransaction.create({
                type:      'إيراد',
                amount:    data.amount,
                category:  'رسوم الأعضاء',
                committee: '',
                desc:      `رسوم ${m?.name ?? 'عضو'} - ${p.name}`,
                date:      data.date ?? today(),
            });
        }
    }
},
```

};

// ============================================================
// PERIODS
// ============================================================
const AdminPeriod = {
async create(data) {
const payload = {
name:       data.name,
fee_amount: data.feeAmount,
start_date: data.start ?? null,
end_date:   data.end   ?? null,
};
const r = await PeriodsAPI.create(payload);
const period = { id: r.data?.id ?? uid(), name: data.name, feeAmount: data.feeAmount, start: data.start, end: data.end };
DB.periods.push(period);

```
    // أنشئ سجلات دفع لكل الأعضاء النشطين
    const activeMembers = DB.members.filter(m => m.status === 'نشط');
    for (const m of activeMembers) {
        await PaymentsAPI.save({
            member_id:  m.id,
            period_id:  period.id,
            amount:     0,
            required:   data.feeAmount,
            pay_date:   null,
            method:     '',
            status:     'لم يدفع',
            notes:      '',
        });
    }

    // أعد تحميل المدفوعات
    const pr = await PaymentsAPI.getAll();
    DB.payments = (pr.data ?? []).map(normalizePayment);

    return period;
},
```

};

// ============================================================
// TRANSACTIONS
// ============================================================
const AdminTransaction = {
async create(data) {
const payload = {
type:         data.type,
amount:       data.amount,
category:     data.category   ?? ‘’,
committee_id: data.committee  ?? ‘’,
description:  data.desc,
tx_date:      data.date ?? today(),
};
const r = await TransactionsAPI.create(payload);
DB.transactions.push(normalizeTx(r.data ?? { …payload, id: uid() }));
},

```
async delete(id) {
    await TransactionsAPI.delete(id);
    DB.transactions = DB.transactions.filter(t => t.id !== id);
},
```

};

// ============================================================
// EVENTS
// ============================================================
const AdminEvent = {
async create(data) {
const payload = {
name:         data.name,
committee_id: data.committeeId ?? ‘’,
status:       data.status,
event_date:   data.date || null,
budget:       data.budget       ?? 0,
participants: data.participants ?? 0,
lead:         data.lead         ?? ‘’,
notes:        data.notes        ?? ‘’,
icon:         data.icon         ?? ‘🎉’,
images:       data.images       ?? [],
};
const r = await EventsAPI.create(payload);
DB.events.push(normalizeEvent(r.data ?? { …payload, id: uid() }));
},

```
async delete(id) {
    await EventsAPI.delete(id);
    DB.events = DB.events.filter(e => e.id !== id);
},
```

};

// ============================================================
// POLLS
// ============================================================
const AdminPoll = {
async create(data) {
const payload = {
title:        data.title,
options:      data.options,
committee_id: data.committee ?? ‘’,
end_date:     data.end || null,
};
const r = await PollsAPI.create(payload);
DB.polls.unshift(normalizePoll(r.data ?? { …payload, id: uid(), is_active: true }));
},

```
async vote(pollId, optIdx) {
    const r = await PollsAPI.vote(pollId, optIdx);
    const idx = DB.polls.findIndex(p => p.id === pollId);
    if (idx >= 0 && r.data) DB.polls[idx] = normalizePoll(r.data);
},

async close(pollId) {
    await PollsAPI.close(pollId);
    const p = DB.polls.find(x => x.id === pollId);
    if (p) p.active = false;
},

async delete(pollId) {
    await PollsAPI.delete(pollId);
    DB.polls = DB.polls.filter(p => p.id !== pollId);
},
```

};

// ============================================================
// BRANCHES
// ============================================================
const AdminBranch = {
async save(data) {
const r = await BranchesAPI.save(data);
const branch = r.branch;
const idx = DB.branches.findIndex(b => b.id === branch.id);
if (idx >= 0) DB.branches[idx] = branch;
else DB.branches.push(branch);
return branch;
},

```
async delete(id) {
    await BranchesAPI.delete(id);
    DB.branches = DB.branches.filter(b => b.id !== id);
},
```

};

// ============================================================
// MEETING
// ============================================================
const AdminMeeting = {
async save(date, time, title) {
const datetime = `${date}T${time ?? '10:00'}`;
const r = await MeetingAPI.save({ date: datetime, title, visible: true });
DB.nextMeeting = r.nextMeeting;
updateCountdown();
return r.nextMeeting;
},

```
async hide() {
    if (!DB.nextMeeting) return;
    const newVis = !DB.nextMeeting.visible;
    const r = await MeetingAPI.save({ ...DB.nextMeeting, visible: newVis });
    DB.nextMeeting = r.nextMeeting;
    updateCountdown();
},

async delete() {
    await MeetingAPI.delete();
    DB.nextMeeting = null;
    updateCountdown();
},
```

};

// ============================================================
// SETTINGS (website)
// ============================================================
const AdminSettings = {
async saveHeader(title, subtitle) {
const r = await SettingsAPI.saveSection(‘header’, { title, subtitle });
DB.settings.header = r.settings.header;
},
async saveHero(title, description) {
const r = await SettingsAPI.saveSection(‘hero’, { title, description });
DB.settings.hero = r.settings.hero;
},
async saveStats(years, committees, members) {
const r = await SettingsAPI.saveSection(‘stats’, { years, committees, members });
DB.settings.stats = r.settings.stats;
},
async saveAbout(mission, vision) {
const r = await SettingsAPI.saveSection(‘about’, { mission, vision });
DB.settings.about = r.settings.about;
},
async savePositions(positions) {
const r = await SettingsAPI.saveSection(‘councilPositions’, positions);
DB.settings.councilPositions = positions;
},
async saveValues(values) {
const r = await SettingsAPI.saveSection(‘values’, values);
DB.settings.values = values;
},
async saveLogo(logoData) {
const r = await SettingsAPI.saveSection(‘logo’, logoData);
DB.settings.logo = logoData;
},
};

// ============================================================
// MEDIA
// ============================================================
const AdminMedia = {
async create(data) {
const r = await MediaAPI.create(data);
if (!DB.media) DB.media = [];
DB.media.push(r.media);
return r.media;
},
async delete(id) {
await MediaAPI.delete(id);
DB.media = (DB.media ?? []).filter(m => m.id !== id);
},
};

// ============================================================
// Helpers لقراءة البيانات المحلية (من cache)
// ============================================================
function getMembers()      { return DB.members; }
function getPeriods()      { return DB.periods; }
function getPayments()     { return DB.payments; }
function getTransactions() { return DB.transactions; }
function getEvents()       { return DB.events; }
function getPolls()        { return DB.polls; }
function getBranches()     { return DB.branches; }
function getMedia()        { return DB.media ?? []; }
function getSettings()     { return DB.settings; }
function getNextMeeting()  { return DB.nextMeeting; }
function getCommitteeMembersMap() { return DB.committeeMembersMap; }

function curPeriod() { return DB.periods[DB.periods.length - 1] ?? null; }

// ============================================================
// UI helpers
// ============================================================
function showGlobalLoading(show) {
let el = document.getElementById(‘global-loading’);
if (!el) {
el = document.createElement(‘div’);
el.id = ‘global-loading’;
el.style.cssText = ‘position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:9999;display:flex;align-items:center;justify-content:center;’;
el.innerHTML = ‘<div style="background:#fff;padding:24px 36px;border-radius:16px;font-family:Cairo;font-size:15px;font-weight:700;color:#2d6b40">⏳ جاري التحميل…</div>’;
document.body.appendChild(el);
}
el.style.display = show ? ‘flex’ : ‘none’;
}

function showError(msg) {
const el = document.getElementById(‘global-loading’);
if (el) el.innerHTML = `<div style="background:#fff;padding:24px 36px;border-radius:16px;font-family:Cairo;font-size:13px;color:#c0392b;max-width:400px;text-align:center">${msg}<br><br><button onclick="location.reload()" style="background:#c0392b;color:#fff;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;font-family:Cairo;font-weight:700">إعادة المحاولة</button></div>`;
}

// ============================================================
// Compatibility: State-like interface so existing code works
// ============================================================
const State = {
getMembers:     getMembers,
getPeriods:     getPeriods,
getPayments:    getPayments,
getTransactions:getTransactions,
getEvents:      getEvents,
getPolls:       getPolls,
getFamilyBranches: getBranches,
getMedia:       getMedia,
getWebsiteSettings: getSettings,
getNextMeeting: getNextMeeting,
getCommitteeMembers: getCommitteeMembersMap,
getActivity:    () => [],
getDB:          () => DB,
};

// Services compatibility
const MemberService = {
saveMember: (id, data) => AdminMember.save(id, data).then(() => { closeModal(‘modal-member’); toast(‘تم الحفظ ✅’); renderMembers(); updateSidebar(); }),
deleteMember: (id) => { confirm2(‘هل تريد حذف هذا العضو؟’, async () => { await AdminMember.delete(id); toast(‘تم الحذف’); renderMembers(); updateSidebar(); }); },
addMemberToCommittee: (cid, mid) => AdminMember.addToCommittee(cid, mid).then(() => { toast(‘تمت الإضافة ✅’); showCommitteeDetail(cid); }),
removeMemberFromCommittee: (cid, mid) => AdminMember.removeFromCommittee(cid, mid).then(() => { toast(‘تم الإزالة’); showCommitteeDetail(cid); }),
syncMembersFromAPI: async () => { /* already loaded in loadAllData */ },
};

const FinanceService = {
createPeriod: async (data) => { await AdminPeriod.create(data); closeModal(‘modal-period’); toast(‘تم إنشاء الدورة ✅’); renderFees(); renderDashboard(); updateSidebar(); },
savePayment:  async (memberId, data) => { await AdminPayment.save(memberId, data); closeModal(‘modal-pay’); toast(‘تم التسجيل ✅’); renderFees(); renderDashboard(); },
addTransaction: async (data) => { await AdminTransaction.create(data); closeModal(‘modal-tx’); toast(‘تمت الإضافة ✅’); renderBudget(); renderDashboard(); updateSidebar(); },
deleteTx:     async (id) => { confirm2(‘حذف المعاملة؟’, async () => { await AdminTransaction.delete(id); toast(‘تم الحذف’); renderBudget(); renderDashboard(); }); },
};

const PollService = {
createPoll: async (data) => { await AdminPoll.create(data); toast(‘تم إنشاء التصويت ✅’); renderVoting(); },
vote:       async (pollId, optIdx) => { await AdminPoll.vote(pollId, optIdx); renderVoting(); },
closePoll:  async (id) => { await AdminPoll.close(id); toast(‘تم إغلاق التصويت’); renderVoting(); },
deletePoll: async (id) => { confirm2(‘حذف التصويت؟’, async () => { await AdminPoll.delete(id); toast(‘تم الحذف’); renderVoting(); }); },
};
