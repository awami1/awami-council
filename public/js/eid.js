// Eid card generator
function loadEidImage() {
return new Promise((resolve, reject) => {
const img = new Image();
img.onload = () => resolve(img);
img.onerror = reject;
img.src = ‘eid-template.png’;
});
}

async function generateEidCard() {
const name = document.getElementById(‘eid-name’).value.trim();
if (!name) { alert(‘الرجاء كتابة اسمك’); return; }

const canvas = document.getElementById(‘eid-canvas’);
const ctx = canvas.getContext(‘2d’);

try {
const img = await loadEidImage();
canvas.width = img.width;
canvas.height = img.height;
ctx.drawImage(img, 0, 0);
stampEidName(ctx, canvas, name);
} catch (e) {
// Fallback generated card
canvas.width = 1920;
canvas.height = 1080;
const g = ctx.createLinearGradient(0, 0, 1920, 1080);
g.addColorStop(0, ‘#0d4a2a’);
g.addColorStop(0.4, ‘#1a6b3c’);
g.addColorStop(0.7, ‘#164060’);
g.addColorStop(1, ‘#1B3456’);
ctx.fillStyle = g;
ctx.fillRect(0, 0, 1920, 1080);

```
ctx.globalAlpha = 0.05; ctx.fillStyle = '#fff';
ctx.beginPath(); ctx.arc(300, 200, 280, 0, Math.PI * 2); ctx.fill();
ctx.beginPath(); ctx.arc(1650, 850, 320, 0, Math.PI * 2); ctx.fill();
ctx.globalAlpha = 1;

ctx.fillStyle = 'rgba(200,168,75,.25)'; ctx.font = '50px serif';
ctx.fillText('\u2726', 180, 130); ctx.fillText('\u2726', 1720, 180);
ctx.fillText('\u2726', 350, 920); ctx.fillText('\u2726', 1550, 750);
ctx.font = '70px serif'; ctx.fillText('\u2726', 960, 120);

ctx.textAlign = 'center'; ctx.shadowColor = 'rgba(0,0,0,.4)'; ctx.shadowBlur = 18;
ctx.fillStyle = '#fff'; ctx.font = 'bold 95px Amiri, serif';
ctx.fillText('\u0643\u0644 \u0639\u0627\u0645 \u0648\u0623\u0646\u062A\u0645 \u0628\u062E\u064A\u0631', 960, 420);
ctx.fillStyle = '#c8a84b'; ctx.font = 'bold 55px Cairo, sans-serif';
ctx.fillText('\u0639\u064A\u062F \u0645\u0628\u0627\u0631\u0643', 960, 530);
ctx.shadowBlur = 0; ctx.strokeStyle = 'rgba(200,168,75,.4)'; ctx.lineWidth = 2;
ctx.beginPath(); ctx.moveTo(700, 600); ctx.lineTo(1220, 600); ctx.stroke();
ctx.fillStyle = 'rgba(255,255,255,.7)'; ctx.font = '32px Cairo, sans-serif';
ctx.shadowBlur = 8;
ctx.fillText('\u0645\u062C\u0644\u0633 \u0639\u0627\u0626\u0644\u0629 \u0627\u0644\u0639\u0648\u0627\u0645\u064A', 960, 660);
stampEidName(ctx, canvas, name);
```

}

showEidCard();
}

function stampEidName(ctx, c, name) {
ctx.fillStyle = ‘#fff’;
ctx.font = ‘bold 85px Tajawal, Cairo, Arial, sans-serif’;
ctx.textAlign = ‘center’;
ctx.shadowColor = ‘rgba(0,0,0,0.7)’;
ctx.shadowBlur = 15;
ctx.shadowOffsetX = 3;
ctx.shadowOffsetY = 3;
ctx.fillText(name, c.width / 2, c.height - 180);
}

function showEidCard() {
const p = document.getElementById(‘eid-preview’);
p.style.display = ‘block’;
p.scrollIntoView({ behavior: ‘smooth’, block: ‘center’ });
}

function initEid() {
document.getElementById(‘eid-gen’).addEventListener(‘click’, generateEidCard);

document.getElementById(‘eid-download’).addEventListener(‘click’, () => {
const c = document.getElementById(‘eid-canvas’);
const a = document.createElement(‘a’);
a.download = ‘eid-greeting-’ + Date.now() + ‘.png’;
a.href = c.toDataURL(‘image/png’, 1.0);
a.click();
});

document.getElementById(‘eid-share’).addEventListener(‘click’, () => {
const c = document.getElementById(‘eid-canvas’);
c.toBlob(blob => {
const file = new File([blob], ‘eid-greeting.png’, { type: ‘image/png’ });
if (navigator.share) {
navigator.share({
files: [file],
title: ‘\u062A\u0647\u0646\u0626\u0629 \u0627\u0644\u0639\u064A\u062F’,
text: ‘\u0643\u0644 \u0639\u0627\u0645 \u0648\u0623\u0646\u062A\u0645 \u0628\u062E\u064A\u0631 - \u0645\u062C\u0644\u0633 \u0639\u0627\u0626\u0644\u0629 \u0627\u0644\u0639\u0648\u0627\u0645\u064A’
}).catch(() => alert(’\u064A\u0645\u0643\u0646\u0643 \u062A\u062D\u0645\u064A\u0644 \u0627\u0644\u0635\u0648\u0631\u0629 \u0648\u0645\u0634\u0627\u0631\u0643\u062A\u0647\u0627 \u064A\u062F\u0648\u064A\u0627\u064B’));
} else {
alert(’\u0627\u0644\u0645\u0634\u0627\u0631\u0643\u0629 \u063A\u064A\u0631 \u0645\u062F\u0639\u0648\u0645\u0629. \u064A\u0631\u062C\u0649 \u062A\u062D\u0645\u064A\u0644 \u0627\u0644\u0635\u0648\u0631\u0629’);
}
});
});
}
