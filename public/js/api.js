// ============================================================
// api.js — كل الاتصالات مع قاعدة البيانات
// لا يوجد localStorage — كل شيء من الـ API
// ============================================================

const API_BASE = ‘/api’;

async function apiFetch(url, options = {}) {
const res = await fetch(url, {
headers: { ‘Content-Type’: ‘application/json’ },
…options,
});
const json = await res.json().catch(() => ({}));
if (!res.ok) throw new Error(json.error || `HTTP ${res.status}`);
return json;
}

const api = {
get:    (path)       => apiFetch(`${API_BASE}/${path}`),
post:   (path, body) => apiFetch(`${API_BASE}/${path}`, { method: ‘POST’,   body: JSON.stringify(body) }),
put:    (path, body, id) => apiFetch(`${API_BASE}/${path}?id=${id}`, { method: ‘PUT’,    body: JSON.stringify(body) }),
del:    (path, id)   => apiFetch(`${API_BASE}/${path}?id=${id}`, { method: ‘DELETE’ }),
};

// –– Settings ––
const SettingsAPI = {
get: ()                      => api.get(‘settings.php’),
save: (data)                 => api.post(‘settings.php’, data),
saveSection: (section, data) => api.post(‘settings.php’, { section, data }),
};

// –– Meeting ––
const MeetingAPI = {
get:    ()     => api.get(‘meeting.php’),
save:   (data) => api.post(‘meeting.php’, data),
delete: ()     => apiFetch(`${API_BASE}/meeting.php`, { method: ‘DELETE’ }),
};

// –– Members ––
const MembersAPI = {
getAll: ()          => api.get(‘members.php’),
create: (data)      => api.post(‘members.php’, data),
update: (id, data)  => api.put(‘members.php’, data, id),
delete: (id)        => api.del(‘members.php’, id),
addToCommittee:      (committeeId, memberId) => api.post(‘members.php?action=add_committee’,    { committeeId, memberId }),
removeFromCommittee: (committeeId, memberId) => api.post(‘members.php?action=remove_committee’, { committeeId, memberId }),
};

// –– Payments ––
const PaymentsAPI = {
getAll: ()          => api.get(‘payments.php’),
save:   (data)      => api.post(‘payments.php’, data),
update: (id, data)  => api.put(‘payments.php’, data, id),
};

// –– Periods ––
const PeriodsAPI = {
getAll: ()     => api.get(‘periods.php’),
create: (data) => api.post(‘periods.php’, data),
delete: (id)   => api.del(‘periods.php’, id),
};

// –– Transactions ––
const TransactionsAPI = {
getAll: ()     => api.get(‘transactions.php’),
create: (data) => api.post(‘transactions.php’, data),
delete: (id)   => api.del(‘transactions.php’, id),
};

// –– Events ––
const EventsAPI = {
getAll: ()          => api.get(‘events.php’),
create: (data)      => api.post(‘events.php’, data),
update: (id, data)  => api.put(‘events.php’, data, id),
delete: (id)        => api.del(‘events.php’, id),
};

// –– Polls ––
const PollsAPI = {
getAll: ()           => api.get(‘polls.php’),
create: (data)       => api.post(‘polls.php’, data),
vote:   (id, optIdx) => api.put(‘polls.php’, { vote: optIdx }, id),
close:  (id)         => api.put(‘polls.php’, { close: true  }, id),
delete: (id)         => api.del(‘polls.php’, id),
};

// –– Branches ––
const BranchesAPI = {
getAll: ()     => api.get(‘branches.php’),
save:   (data) => api.post(‘branches.php’, data),
delete: (id)   => api.del(‘branches.php’, id),
};

// –– Media ––
const MediaAPI = {
getAll: ()     => api.get(‘media.php’),
create: (data) => api.post(‘media.php’, data),
delete: (id)   => api.del(‘media.php’, id),
};

// –– Public site helpers (used in index.php) ––
async function loadSettings()  { const r = await SettingsAPI.get();     return r.settings; }
async function loadEvents()    { return await EventsAPI.getAll(); }
async function loadBranches()  { const r = await BranchesAPI.getAll();  return r.branches; }
async function loadMedia()     { const r = await MediaAPI.getAll();      return r.media; }
async function loadMeeting()   { const r = await MeetingAPI.get();       return r.nextMeeting; }
async function loadMembers()   { const r = await MembersAPI.getAll();    return r.data ?? []; }
