// Eid card generator — يستخدم خط Readex Pro (الخط السعودي)
// ينتظر تحميل الخطوط قبل الرسم على Canvas

function loadEidImage() {
return new Promise((resolve, reject) => {
const img = new Image();
img.onload = () => resolve(img);
img.onerror = reject;
img.src = '/assets/eid-template.png';
});
}

async function ensureFonts() {
// تأكد من تحميل الخطوط المطلوبة قبل الرسم
try {
  await Promise.all([
    document.fonts.load('700 60px "Readex Pro"'),
    document.fonts.load('400 40px "Readex Pro"'),
    document.fonts.load('700 80px Amiri'),
    document.fonts.load('700 50px Cairo'),
  ]);
} catch (e) {
  // انتظر document.fonts.ready كاحتياطي
  await document.fonts.ready;
}
}

async function generateEidCard() {
const name = document.getElementById('eid-name').value.trim();
if (!name) { alert('الرجاء كتابة اسمك'); return; }

const btn = document.getElementById('eid-gen');
btn.disabled = true;
btn.textContent = 'جاري الإنشاء...';

try {
// انتظر تحميل الخطوط أولاً
await ensureFonts();

const canvas = document.getElementById('eid-canvas');
const ctx = canvas.getContext('2d');

let useTemplate = false;
try {
  const img = await loadEidImage();
  canvas.width = img.width;
  canvas.height = img.height;
  ctx.drawImage(img, 0, 0);
  useTemplate = true;
} catch (e) {
  // Fallback: بطاقة مُولَّدة
  drawFallbackCard(ctx, canvas);
}

stampEidName(ctx, canvas, name, useTemplate);
showEidCard();

} catch (e) {
alert('حدث خطأ أثناء إنشاء البطاقة. يرجى المحاولة مرة أخرى.');
} finally {
btn.disabled = false;
btn.textContent = '\u2728 إنشاء البطاقة';
}
}

function drawFallbackCard(ctx, canvas) {
canvas.width = 1920;
canvas.height = 1080;

// خلفية متدرجة
const g = ctx.createLinearGradient(0, 0, 1920, 1080);
g.addColorStop(0, '#0d4a2a');
g.addColorStop(0.4, '#1a6b3c');
g.addColorStop(0.7, '#164060');
g.addColorStop(1, '#1B3456');
ctx.fillStyle = g;
ctx.fillRect(0, 0, 1920, 1080);

// دوائر زخرفية
ctx.globalAlpha = 0.05;
ctx.fillStyle = '#fff';
ctx.beginPath(); ctx.arc(300, 200, 280, 0, Math.PI * 2); ctx.fill();
ctx.beginPath(); ctx.arc(1650, 850, 320, 0, Math.PI * 2); ctx.fill();
ctx.globalAlpha = 1;

// نجوم زخرفية
ctx.fillStyle = 'rgba(200,168,75,.25)';
ctx.font = '50px serif';
ctx.fillText('\u2726', 180, 130);
ctx.fillText('\u2726', 1720, 180);
ctx.fillText('\u2726', 350, 920);
ctx.fillText('\u2726', 1550, 750);
ctx.font = '70px serif';
ctx.fillText('\u2726', 960, 120);

ctx.textAlign = 'center';
ctx.textBaseline = 'middle';

// كل عام وأنتم بخير
ctx.shadowColor = 'rgba(0,0,0,.4)';
ctx.shadowBlur = 18;
ctx.fillStyle = '#fff';
ctx.font = 'bold 90px "Readex Pro", Amiri, serif';
ctx.fillText('\u0643\u0644 \u0639\u0627\u0645 \u0648\u0623\u0646\u062a\u0645 \u0628\u062e\u064a\u0631', 960, 380);

// عيد مبارك
ctx.fillStyle = '#c8a84b';
ctx.font = 'bold 55px "Readex Pro", Cairo, sans-serif';
ctx.fillText('\u0639\u064a\u062f \u0645\u0628\u0627\u0631\u0643', 960, 490);

// خط زخرفي
ctx.shadowBlur = 0;
ctx.strokeStyle = 'rgba(200,168,75,.4)';
ctx.lineWidth = 2;
ctx.beginPath(); ctx.moveTo(700, 560); ctx.lineTo(1220, 560); ctx.stroke();

// مجلس عائلة العوامي
ctx.fillStyle = 'rgba(255,255,255,.7)';
ctx.font = '32px "Readex Pro", Cairo, sans-serif';
ctx.shadowColor = 'rgba(0,0,0,.3)';
ctx.shadowBlur = 8;
ctx.fillText('\u0645\u062c\u0644\u0633 \u0639\u0627\u0626\u0644\u0629 \u0627\u0644\u0639\u0648\u0627\u0645\u064a', 960, 630);
ctx.shadowBlur = 0;
}

function stampEidName(ctx, canvas, name, useTemplate) {
const w = canvas.width;
const h = canvas.height;

// حجم الخط يتناسب مع عرض البطاقة
let fontSize = Math.round(w * 0.065);
if (useTemplate && fontSize > 55) fontSize = 55;
if (!useTemplate && fontSize > 85) fontSize = 85;
if (fontSize < 36) fontSize = 36;

// تقليص الخط للأسماء الطويلة
ctx.font = 'bold ' + fontSize + 'px "Readex Pro", Tajawal, Cairo, sans-serif';
let measured = ctx.measureText(name).width;
while (measured > w * 0.85 && fontSize > 24) {
  fontSize -= 4;
  ctx.font = 'bold ' + fontSize + 'px "Readex Pro", Tajawal, Cairo, sans-serif';
  measured = ctx.measureText(name).width;
}

// موقع الاسم — في الشريط الأخضر فوق الكرات الزخرفية (82% من الارتفاع)
const nameY = useTemplate ? Math.round(h * 0.82) : h - 180;

ctx.textAlign = 'center';
ctx.textBaseline = 'middle';

// شريط ذهبي عرض كامل خلف الاسم
if (useTemplate) {
  ctx.save();
  const bannerH = Math.round(fontSize * 1.6);
  const bannerY = nameY - Math.round(bannerH / 2);
  ctx.fillStyle = 'rgba(180, 130, 10, 0.82)';
  ctx.fillRect(0, bannerY, w, bannerH);
  ctx.restore();
}

// رسم الاسم
ctx.fillStyle = '#fff';
ctx.shadowColor = 'rgba(0,0,0,0.55)';
ctx.shadowBlur = 10;
ctx.shadowOffsetX = 1;
ctx.shadowOffsetY = 2;
ctx.fillText(name, w / 2, nameY);

// إعادة تعيين الظل
ctx.shadowColor = 'transparent';
ctx.shadowBlur = 0;
ctx.shadowOffsetX = 0;
ctx.shadowOffsetY = 0;
}

function showEidCard() {
const p = document.getElementById('eid-preview');
p.style.display = 'block';
p.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function initEid() {
document.getElementById('eid-gen').addEventListener('click', generateEidCard);

document.getElementById('eid-download').addEventListener('click', () => {
const c = document.getElementById('eid-canvas');
const a = document.createElement('a');
a.download = 'eid-awami-' + Date.now() + '.png';
a.href = c.toDataURL('image/png', 1.0);
a.click();
});

document.getElementById('eid-share').addEventListener('click', () => {
const c = document.getElementById('eid-canvas');
c.toBlob(blob => {
const file = new File([blob], 'eid-awami.png', { type: 'image/png' });
if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
  navigator.share({
    files: [file],
    title: '\u062a\u0647\u0646\u0626\u0629 \u0627\u0644\u0639\u064a\u062f',
    text: '\u0643\u0644 \u0639\u0627\u0645 \u0648\u0623\u0646\u062a\u0645 \u0628\u062e\u064a\u0631 - \u0645\u062c\u0644\u0633 \u0639\u0627\u0626\u0644\u0629 \u0627\u0644\u0639\u0648\u0627\u0645\u064a'
  }).catch(() => {});
} else {
  // Fallback: تحميل مباشر
  const a = document.createElement('a');
  a.download = 'eid-awami.png';
  a.href = c.toDataURL('image/png', 1.0);
  a.click();
}
});
});
}
