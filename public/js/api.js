// ============================================================
// api.js — كل الاتصالات مع قاعدة البيانات
// لا يوجد localStorage — كل شيء من الـ API
// ============================================================

const API_BASE = '/api';

async function apiFetch(url, options = {}) {
const res = await fetch(url, {
headers: { 'Content-Type': 'application/json' },
...options,
});
const json = await res.json().catch(() => ({}));
if (!res.ok) throw new Error(json.error || `HTTP ${res.status}`);
return json;
}

const api = {
get:    (path)       => apiFetch(`${API_BASE}/${path}`),
post:   (path, body) => apiFetch(`${API_BASE}/${path}`, { method: 'POST',   body: JSON.stringify(body) }),
put:    (path, body, id) => apiFetch(`${API_BASE}/${path}?id=${id}`, { method: 'PUT',    body: JSON.stringify(body) }),
del:    (path, id)   => apiFetch(`${API_BASE}/${path}?id=${id}`, { method: 'DELETE' }),
/** Catch errors and return { data, error } instead of throwing */
safeCall: async (promise, fallback = 'حدث خطأ في تحميل البيانات.') => {
  try { return { data: await promise, error: null }; }
  catch (e) { return { data: null, error: e.message || fallback }; }
},
};

// –– Settings ––
const SettingsAPI = {
get: ()                      => api.get('settings.php'),
save: (data)                 => api.post('settings.php', data),
saveSection: (section, data) => api.post('settings.php', { section, data }),
};

// –– Meeting ––
const MeetingAPI = {
get:    ()     => api.get('meeting.php'),
save:   (data) => api.post('meeting.php', data),
delete: ()     => apiFetch(`${API_BASE}/meeting.php`, { method: 'DELETE' }),
};

// –– Members ––
const MembersAPI = {
getAll: ()          => api.get('members.php'),
create: (data)      => api.post('members.php', data),
update: (id, data)  => api.put('members.php', data, id),
delete: (id)        => api.del('members.php', id),
addToCommittee:      (committeeId, memberId) => api.post('members.php?action=add_committee',    { committeeId, memberId }),
removeFromCommittee: (committeeId, memberId) => api.post('members.php?action=remove_committee', { committeeId, memberId }),
};

// –– Payments ––
const PaymentsAPI = {
getAll: ()          => api.get('payments.php'),
save:   (data)      => api.post('payments.php', data),
update: (id, data)  => api.put('payments.php', data, id),
};

// –– Periods ––
const PeriodsAPI = {
getAll: ()     => api.get('periods.php'),
create: (data) => api.post('periods.php', data),
delete: (id)   => api.del('periods.php', id),
};

// –– Transactions ––
const TransactionsAPI = {
getAll: ()     => api.get('transactions.php'),
create: (data) => api.post('transactions.php', data),
delete: (id)   => api.del('transactions.php', id),
};

// –– Events ––
const EventsAPI = {
getAll: ()          => api.get('events.php'),
create: (data)      => api.post('events.php', data),
update: (id, data)  => api.put('events.php', data, id),
delete: (id)        => api.del('events.php', id),
};

// –– Polls ––
const PollsAPI = {
getAll: ()           => api.get('polls.php'),
create: (data)       => api.post('polls.php', data),
vote:   (id, optIdx) => api.put('polls.php', { vote: optIdx }, id),
close:  (id)         => api.put('polls.php', { close: true  }, id),
delete: (id)         => api.del('polls.php', id),
};

// –– Branches ––
const BranchesAPI = {
getAll: ()     => api.get('branches.php'),
save:   (data) => api.post('branches.php', data),
delete: (id)   => api.del('branches.php', id),
};

// –– Family Tree (hierarchical) ––
const FamilyTreeAPI = {
getAll: ()          => api.get('family-tree.php'),
create: (data)      => api.post('family-tree.php', data),
update: (id, data)  => api.put('family-tree.php', data, id),
delete: (id)        => api.del('family-tree.php', id),
};

// –– Media ––
const MediaAPI = {
getAll: ()          => api.get('media.php'),
create: (data)      => api.post('media.php', data),
update: (id, data)  => api.put('media.php', data, id),
delete: (id)        => api.del('media.php', id),
};

// –– Committees (DB-backed CRUD) ––
const CommitteesAPI = {
getAll: ()          => api.get('committees.php'),
get:    (id)        => apiFetch(`${API_BASE}/committees.php?id=${id}`),
create: (data)      => api.post('committees.php', data),
update: (id, data)  => api.put('committees.php', data, id),
delete: (id)        => api.del('committees.php', id),
};

// –– News ––
const NewsAPI = {
getAll: (params = {})  => { const qs = new URLSearchParams(params).toString(); return api.get('news.php' + (qs ? '?' + qs : '')); },
get:    (id)           => apiFetch(`${API_BASE}/news.php?id=${id}`),
create: (data)         => api.post('news.php', data),
update: (id, data)     => api.put('news.php', data, id),
delete: (id)           => api.del('news.php', id),
};

// –– Messages ––
const MessagesAPI = {
getAll: (params = {})  => { const qs = new URLSearchParams(params).toString(); return api.get('messages.php' + (qs ? '?' + qs : '')); },
send:   (data)         => api.post('messages.php', data),
markRead: (id, isRead) => api.put('messages.php', { is_read: isRead ? 1 : 0 }, id),
delete: (id)           => api.del('messages.php', id),
};

// –– Reminders ––
const RemindersAPI = {
getUnpaid: (periodId) => {
    const qs = periodId ? '?period_id=' + periodId : '';
    return api.get('reminders.php' + qs);
},
getHistory: (params = {}) => {
    const qs = new URLSearchParams({ history: 1, ...params }).toString();
    return api.get('reminders.php?' + qs);
},
logSingle: (memberId, periodId, channel) =>
    api.post('reminders.php', { member_id: memberId, period_id: periodId, channel }),
logBulk: (periodId, channel) =>
    api.post('reminders.php', { period_id: periodId, channel, bulk: true }),
};

// –– Audit Log ––
const AuditAPI = {
getAll: (params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return api.get('audit.php' + (qs ? '?' + qs : ''));
},
};

// –– Saved Reports ––
const ReportsAPI = {
getAll: (params = {})  => { const qs = new URLSearchParams(params).toString(); return api.get('reports.php' + (qs ? '?' + qs : '')); },
getOne: (id)           => apiFetch(`${API_BASE}/reports.php?id=${id}`),
save:   (data)         => api.post('reports.php', data),
update: (id, data)     => api.put('reports.php', data, id),
archive:(id)           => apiFetch(`${API_BASE}/reports.php?id=${id}&archive=1`, { method: 'PUT', body: JSON.stringify({ status: 'archived' }) }),
restore:(id)           => apiFetch(`${API_BASE}/reports.php?id=${id}&archive=1`, { method: 'PUT', body: JSON.stringify({ status: 'active' }) }),
remove: (id)           => api.del('reports.php', id),
};

// –– Export (returns CSV download URL) ––
const ExportAPI = {
url: (type, params = {}) => {
    const qs = new URLSearchParams({ type, ...params }).toString();
    return `${API_BASE}/export.php?${qs}`;
},
};

// –– Stories (سِيَر وقصص) ––
const StoriesAPI = {
getAll: (params = {})  => { const qs = new URLSearchParams(params).toString(); return api.get('stories.php' + (qs ? '?' + qs : '')); },
get:    (id)           => apiFetch(`${API_BASE}/stories.php?id=${id}`),
create: (data)         => api.post('stories.php', data),
update: (id, data)     => api.put('stories.php', data, id),
delete: (id)           => api.del('stories.php', id),
togglePin: (id)        => apiFetch(`${API_BASE}/stories.php?id=${id}&action=pin`, { method: 'PATCH' }),
toggleStatus: (id)     => apiFetch(`${API_BASE}/stories.php?id=${id}&action=status`, { method: 'PATCH' }),
uploadImage: (formData) => fetch(`${API_BASE}/stories.php?upload=1`, { method: 'POST', body: formData }).then(r => r.json()).then(j => { if (j.error) throw new Error(j.error); return j; }),
};

// –– Gallery Stories (الرِّوَاق) ––
const GalleryStoriesAPI = {
getAll: (params = {})  => { const qs = new URLSearchParams(params).toString(); return api.get('gallery-stories.php' + (qs ? '?' + qs : '')); },
get:    (id)           => apiFetch(`${API_BASE}/gallery-stories.php?id=${id}`),
create: (data)         => api.post('gallery-stories.php', data),
update: (id, data)     => api.put('gallery-stories.php', data, id),
delete: (id)           => api.del('gallery-stories.php', id),
};

// –– Public site helpers (used in index.php) ––
async function loadSettings()  { const r = await SettingsAPI.get();     return r.settings; }
async function loadEvents()    { return await EventsAPI.getAll(); }
async function loadBranches()  { const r = await BranchesAPI.getAll();  return r.branches; }
async function loadMedia()     { const r = await MediaAPI.getAll();      return r.media; }
async function loadMeeting()   { const r = await MeetingAPI.get();       return r.nextMeeting; }
async function loadMembers()   { const r = await MembersAPI.getAll();    return r.data ?? []; }
async function loadNews()      { const r = await NewsAPI.getAll();       return r.data ?? []; }
async function loadStories()   { const r = await StoriesAPI.getAll();    return r.data ?? []; }
