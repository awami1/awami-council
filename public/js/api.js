// API helpers - read-only
async function apiFetch(url) {
const res = await fetch(url);
if (!res.ok) throw new Error(’API error: ’ + url);
return res.json();
}

async function loadSettings()   { return apiFetch(’/api/settings.php’); }
async function loadBranches()   { return apiFetch(’/api/branches.php’); }
async function loadMedia()      { return apiFetch(’/api/media.php’); }
async function loadEvents()     { return apiFetch(’/api/events.php’); }
async function loadCommittees() { return apiFetch(’/api/committees.php’); }
