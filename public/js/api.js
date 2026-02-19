// API helpers - read-only
const API_BASE = ‘/api’;

async function apiFetch(url) {
const res = await fetch(url);
if (!res.ok) throw new Error(’API error: ’ + url);
return res.json();
}

async function loadSettings()   { return apiFetch(API_BASE + ‘/settings.php’); }
async function loadBranches()   { return apiFetch(API_BASE + ‘/branches.php’); }
async function loadMedia()      { return apiFetch(API_BASE + ‘/media.php’); }
async function loadEvents()     { return apiFetch(API_BASE + ‘/events.php’); }
async function loadCommittees() { return apiFetch(API_BASE + ‘/committees.php’); }
