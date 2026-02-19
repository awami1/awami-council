<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مجلس عائلة العوامي</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;800&family=Amiri:wght@700&display=swap" rel="stylesheet">

<style>
:root{
--primary:#1B3456;
--green:#47915C;
--bg:#f6f9f7;
--text:#1a2a1e;
--border:#dde5df;
}

body{margin:0;font-family:'Cairo',sans-serif;background:var(--bg);color:var(--text)}

header{
background:linear-gradient(135deg,#1B3456,#2d5a85);
color:#fff;
padding:20px 30px;
}

.container{max-width:1200px;margin:auto;padding:60px 20px}

.hero{text-align:center;margin-bottom:60px}
.hero h2{font-size:36px;margin-bottom:10px;font-family:'Amiri',serif}
.hero p{color:#6b7c6e;font-size:16px}

.stats{display:flex;justify-content:center;gap:40px;margin-top:20px}
.stat{font-size:18px}

.section{margin-bottom:70px}
.section h2{margin-bottom:20px;font-size:28px}

.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px}

.card{
background:#fff;
border-radius:14px;
padding:20px;
border:1px solid var(--border);
box-shadow:0 4px 14px rgba(0,0,0,.05);
transition:.3s;
}
.card:hover{transform:translateY(-4px)}

.media-item img,.media-item video{
width:100%;
height:200px;
object-fit:cover;
border-radius:10px;
}

.countdown{
background:linear-gradient(135deg,var(--green),#2d6b40);
color:#fff;
padding:25px;
border-radius:14px;
text-align:center;
margin-top:30px;
}

footer{
background:#1B3456;
color:#fff;
text-align:center;
padding:30px;
}
</style>
</head>
<body>

<header>
<h1 id="site-title">مجلس عائلة العوامي</h1>
<p id="site-subtitle"></p>
</header>

<div class="container">

<div class="hero">
<h2 id="hero-title"></h2>
<p id="hero-desc"></p>

<div class="stats">
<div class="stat">الأعضاء: <strong id="stat-members">0</strong></div>
<div class="stat">اللجان: <strong id="stat-committees">0</strong></div>
<div class="stat">الفعاليات: <strong id="stat-events">0</strong></div>
</div>

<div class="countdown" id="countdown-box" style="display:none">
<h3>الجلسة القادمة</h3>
<div id="countdown-timer"></div>
</div>

</div>

<!-- BRANCHES -->
<div class="section">
<h2>شجرة العائلة</h2>
<div class="grid" id="branches-grid"></div>
</div>

<!-- COMMITTEES -->
<div class="section">
<h2>اللجان</h2>
<div class="grid" id="committees-grid"></div>
</div>

<!-- EVENTS -->
<div class="section">
<h2>الفعاليات القادمة</h2>
<div class="grid" id="events-grid"></div>
</div>

<!-- MEDIA -->
<div class="section">
<h2>الميديا</h2>
<div class="grid" id="media-grid"></div>
</div>

</div>

<footer>
© 2025 مجلس عائلة العوامي
</footer>

<script>
const API = {
async get(url){
const res = await fetch(url);
if(!res.ok) throw new Error("API error");
return res.json();
}
};

// ================= SETTINGS =================
async function loadSettings(){
const res = await API.get('/api/settings.php');
const s = res.data;

if(!s) return;

if(s.header){
document.getElementById('site-title').textContent = s.header.title || '';
document.getElementById('site-subtitle').textContent = s.header.subtitle || '';
}
if(s.hero){
document.getElementById('hero-title').textContent = s.hero.title || '';
document.getElementById('hero-desc').textContent = s.hero.description || '';
}
if(s.stats){
document.getElementById('stat-members').textContent = s.stats.members || 0;
document.getElementById('stat-committees').textContent = s.stats.committees || 0;
}
if(s.nextMeeting && s.nextMeeting.date){
initCountdown(s.nextMeeting.date);
}
}

// ================= COUNTDOWN =================
function initCountdown(dateStr){
const box=document.getElementById('countdown-box');
box.style.display='block';
const target=new Date(dateStr);

function update(){
const diff=target-new Date();
if(diff<=0){document.getElementById('countdown-timer').textContent='تم انعقاد الجلسة';return;}
const d=Math.floor(diff/86400000);
const h=Math.floor(diff%86400000/3600000);
const m=Math.floor(diff%3600000/60000);
document.getElementById('countdown-timer').textContent=`${d} يوم - ${h} ساعة - ${m} دقيقة`;
}
update();
setInterval(update,60000);
}

// ================= BRANCHES =================
async function loadBranches(){
const res=await API.get('/api/branches.php');
const data=res.data||[];
document.getElementById('branches-grid').innerHTML=data.map(b=>`
<div class="card">
<h3>${b.name}</h3>
<p>${b.head||''}</p>
<strong>${b.count||0} فرد</strong>
</div>`).join('');
}

// ================= COMMITTEES =================
async function loadCommittees(){
const res=await API.get('/api/committees.php');
const data=res.data||[];
document.getElementById('committees-grid').innerHTML=data.map(c=>`
<div class="card">
<h3>${c.name}</h3>
<p>${c.description||''}</p>
</div>`).join('');
document.getElementById('stat-committees').textContent=data.length;
}

// ================= EVENTS =================
async function loadEvents(){
const res=await API.get('/api/events.php');
const data=res.data||[];
document.getElementById('events-grid').innerHTML=data.map(e=>`
<div class="card">
<h3>${e.name}</h3>
<p>${e.event_date||''}</p>
</div>`).join('');
document.getElementById('stat-events').textContent=data.length;
}

// ================= MEDIA =================
async function loadMedia(){
const res=await API.get('/api/media.php');
const data=res.data||[];
document.getElementById('media-grid').innerHTML=data.map(m=>`
<div class="card media-item">
${m.type==='videos'?
`<video controls><source src="${m.url}"></video>`:
`<img src="${m.url}" alt="">`
}
</div>`).join('');
}

// ================= MEMBERS COUNT =================
async function loadMembersCount(){
const res=await API.get('/api/members.php');
document.getElementById('stat-members').textContent=res.total||0;
}

// ================= INIT =================
document.addEventListener('DOMContentLoaded',async()=>{
await loadSettings();
await loadMembersCount();
await loadBranches();
await loadCommittees();
await loadEvents();
await loadMedia();
});
</script>

</body>
</html>
