// Countdown timer - loads next meeting from API
async function initCountdown() {
let tDate = null;
try {
const data = await loadEvents();
if (data && data.nextMeeting && data.nextMeeting.visible !== false) {
tDate = new Date(data.nextMeeting.date);
}
} catch (e) {}

if (!tDate) {
tDate = new Date();
tDate.setMonth(tDate.getMonth() + 1);
tDate.setDate(15);
tDate.setHours(10, 0, 0, 0);
}

function update() {
let d = tDate - new Date();
if (d < 0) d = 0;
document.getElementById(‘cd-d’).textContent = Math.floor(d / 864e5);
document.getElementById(‘cd-h’).textContent = Math.floor(d % 864e5 / 36e5);
document.getElementById(‘cd-m’).textContent = Math.floor(d % 36e5 / 6e4);
document.getElementById(‘cd-date’).textContent = tDate.toLocaleDateString(‘ar-SA’, {
weekday: ‘long’, year: ‘numeric’, month: ‘long’,
day: ‘numeric’, hour: ‘2-digit’, minute: ‘2-digit’
});
}

update();
setInterval(update, 60000);
}
