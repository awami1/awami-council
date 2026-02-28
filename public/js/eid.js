// Eid card generator v2 — خط Saudi الرسمي من وزارة الثقافة السعودية

// ─── Shared state ───
let _templateImg = null;
let _rafPending  = false;

const _TEMPLATE_SRCS = ['/assets/eid-template.jpg', '/assets/eid-template.png'];
const _FULL_W = 1003;
const _FULL_H = 1144;
const _NAME_Y_OFFSET = 200;

async function ensureSaudiFont() {
  try {
    await Promise.all([
      document.fonts.load('normal 70px Saudi'),
      document.fonts.load('bold 70px Saudi'),
    ]);
  } catch (_) {
    await document.fonts.ready;
  }
}

function _loadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload  = () => resolve(img);
    img.onerror = reject;
    img.src = src;
  });
}

async function _loadTemplateOnce() {
  if (_templateImg) return _templateImg;
  for (const src of _TEMPLATE_SRCS) {
    try {
      _templateImg = await _loadImage(src);
      return _templateImg;
    } catch (_) {}
  }
  return null;
}

// ─── Core drawing (shared by live preview & final generation) ───
function _drawCard(ctx, canvas, img, name, weight, fontSize) {
  if (img) {
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
  } else {
    _drawFallback(ctx, canvas);
  }

  if (!name) return;

  const scale = canvas.width / _FULL_W;
  let scaledSize = fontSize * scale;

  ctx.font         = `${weight} ${scaledSize}px Saudi, 'Readex Pro', Cairo, sans-serif`;
  ctx.textAlign    = 'center';
  ctx.textBaseline = 'alphabetic';

  let measured = ctx.measureText(name).width;
  while (measured > canvas.width * 0.88 && scaledSize > 28 * scale) {
    scaledSize -= 4 * scale;
    ctx.font = `${weight} ${scaledSize}px Saudi, 'Readex Pro', Cairo, sans-serif`;
    measured = ctx.measureText(name).width;
  }

  ctx.fillStyle     = '#ffffff';
  ctx.shadowColor   = 'rgba(0,0,0,0.7)';
  ctx.shadowBlur    = 15 * scale;
  ctx.shadowOffsetX = 3 * scale;
  ctx.shadowOffsetY = 3 * scale;
  ctx.fillText(name, canvas.width / 2, canvas.height - (_NAME_Y_OFFSET * scale));

  ctx.shadowColor   = 'transparent';
  ctx.shadowBlur    = 0;
  ctx.shadowOffsetX = 0;
  ctx.shadowOffsetY = 0;
}

function _drawFallback(ctx, canvas) {
  const s = canvas.width / _FULL_W;
  const g = ctx.createLinearGradient(0, 0, 0, canvas.height);
  g.addColorStop(0, '#0d4a2a');
  g.addColorStop(1, '#1B3456');
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  ctx.textAlign    = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillStyle    = '#fff';
  ctx.font         = `bold ${80 * s}px Saudi, Cairo, serif`;
  ctx.fillText('كل عام وأنتم بخير', canvas.width / 2, 430 * s);
  ctx.fillStyle = '#c8a84b';
  ctx.font      = `bold ${50 * s}px Saudi, Cairo, sans-serif`;
  ctx.fillText('عيد مبارك', canvas.width / 2, 540 * s);
  ctx.fillStyle = 'rgba(255,255,255,.65)';
  ctx.font      = `${30 * s}px Saudi, Cairo, sans-serif`;
  ctx.fillText('مجلس عائلة العوامي', canvas.width / 2, 640 * s);
}

// ─── Live preview (debounced with rAF) ───
function _renderLivePreview() {
  const canvas = document.getElementById('eid-preview-canvas');
  const wrap   = document.getElementById('eid-live-preview');
  if (!canvas || !wrap) return;

  const ctx  = canvas.getContext('2d');
  const rect = wrap.getBoundingClientRect();
  const dpr  = Math.min(window.devicePixelRatio || 1, 2);
  canvas.width  = Math.round(rect.width * dpr);
  canvas.height = Math.round(rect.height * dpr);

  const name     = document.getElementById('eid-name').value.trim() || 'اكتب اسمك هنا';
  const weight   = document.getElementById('eid-font-weight')?.value || 'bold';
  const fontSize = parseInt(document.getElementById('eid-font-size')?.value || '70');

  _drawCard(ctx, canvas, _templateImg, name, weight, fontSize);

  const hint = document.getElementById('eid-live-hint');
  if (hint) hint.classList.add('hidden');
}

function updateEidPreview() {
  if (_rafPending) return;
  _rafPending = true;
  requestAnimationFrame(() => {
    _rafPending = false;
    _renderLivePreview();
  });
}

function updateFontSize() {
  const size  = document.getElementById('eid-font-size').value;
  const label = document.getElementById('eid-font-size-label');
  if (label) label.textContent = size;
  updateEidPreview();
}

// ─── Final high-res card generation ───
async function generateEidCard() {
  const name = document.getElementById('eid-name').value.trim();
  if (!name) { alert('الرجاء كتابة اسمك'); return; }

  const weight   = document.getElementById('eid-font-weight')?.value || 'bold';
  const fontSize = parseInt(document.getElementById('eid-font-size')?.value || '70');

  const btn = document.getElementById('eid-gen');
  btn.disabled    = true;
  btn.textContent = 'جاري الإنشاء...';

  try {
    await ensureSaudiFont();
    await _loadTemplateOnce();

    const canvas = document.getElementById('eid-canvas');
    const ctx    = canvas.getContext('2d');
    canvas.width  = _templateImg ? _templateImg.width : _FULL_W;
    canvas.height = _templateImg ? _templateImg.height : _FULL_H;

    _drawCard(ctx, canvas, _templateImg, name, weight, fontSize);

    const preview = document.getElementById('eid-preview');
    preview.style.display = 'block';
    preview.scrollIntoView({ behavior: 'smooth', block: 'center' });

  } catch (e) {
    alert('حدث خطأ أثناء إنشاء البطاقة. يرجى المحاولة مرة أخرى.');
  } finally {
    btn.disabled    = false;
    btn.textContent = '✨ إنشاء البطاقة';
  }
}

function _fallbackShare(canvas, name) {
  const text = encodeURIComponent('كل عام وأنتم بخير - مجلس عائلة العوامي');
  if (confirm('سيتم فتح واتساب. حمّل الصورة أولاً ثم أرسلها.')) {
    const a    = document.createElement('a');
    a.download = name + '.png';
    a.href     = canvas.toDataURL('image/png', 1.0);
    a.click();
    setTimeout(() => window.open('https://wa.me/?text=' + text, '_blank'), 500);
  }
}

// ─── Init ───
function initEid() {
  document.getElementById('eid-name')?.addEventListener('input',  updateEidPreview);
  document.getElementById('eid-font-weight')?.addEventListener('change', updateEidPreview);
  document.getElementById('eid-font-size')?.addEventListener('input',  updateFontSize);

  document.getElementById('eid-gen').addEventListener('click', generateEidCard);

  document.getElementById('eid-download').addEventListener('click', () => {
    const c    = document.getElementById('eid-canvas');
    const name = document.getElementById('eid-name').value.trim() || 'تهنئة-العيد';
    const a    = document.createElement('a');
    a.download = name + '-' + Date.now() + '.png';
    a.href     = c.toDataURL('image/png', 1.0);
    a.click();
  });

  document.getElementById('eid-share').addEventListener('click', () => {
    const c    = document.getElementById('eid-canvas');
    const name = document.getElementById('eid-name').value.trim() || 'تهنئة-العيد';
    c.toBlob(blob => {
      const file = new File([blob], name + '.png', { type: 'image/png' });
      if (navigator.share && navigator.canShare?.({ files: [file] })) {
        navigator.share({
          files: [file],
          title: 'تهنئة العيد',
          text:  'كل عام وأنتم بخير - مجلس عائلة العوامي',
        }).catch(() => _fallbackShare(c, name));
      } else {
        _fallbackShare(c, name);
      }
    });
  });

  // Preload template + font, then render initial preview
  ensureSaudiFont().then(() => _loadTemplateOnce()).then(() => _renderLivePreview());

  // Re-render on resize
  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(_renderLivePreview, 150);
  });
}
