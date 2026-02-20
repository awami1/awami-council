// Countdown — يقرأ من API مباشرة
async function initCountdown() {
let tDate = null;
let visible = true;

try {
const data = await loadMeeting(); // /api/meeting.php
if (data && data.visible !== false && data.date) {
tDate   = new Date(data.date);
visible = true;
}
} catch (e) {}

const section = document.getElementById(‘countdown-section’);
if (!tDate || !visible) {
if (section) section.style.display = ‘none’;
return;
}
if (section) section.style.display = ‘’;

function update() {
let d = tDate - new Date();
if (d < 0) d = 0;
document.getElementById(‘cd-d’).textContent = Math.floor(d / 864e5);
document.getElementById(‘cd-h’).textContent = Math.floor(d % 864e5 / 36e5);
document.getElementById(‘cd-m’).textContent = Math.floor(d % 36e5 / 6e4);
const dateEl = document.getElementById(‘cd-date’);
if (dateEl) dateEl.textContent = tDate.toLocaleDateString(‘ar-SA’, {
weekday: ‘long’, year: ‘numeric’, month: ‘long’,
day: ‘numeric’, hour: ‘2-digit’, minute: ‘2-digit’
});
}

update();
setInterval(update, 60000);
}
