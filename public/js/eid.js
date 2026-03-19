// Eid card generator v3 — حل جذري: حذف الفولباك + retry مع حماية من السباق

// ─── Shared state ───
let _templateImg   = null;
let _loadingPromise = null;
let _rafPending    = false;

const _TEMPLATE_SRCS = ['/assets/eid-template-new.png'];
const _FULL_W = 1003;
const _FULL_H = 1144;
const _NAME_Y_OFFSET = 166;

async function ensureEidFonts() {
  try {
    await Promise.all([
      document.fonts.load('normal 70px Jarood'),
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
  if (_loadingPromise) return _loadingPromise;
  _loadingPromise = (async () => {
    for (let attempt = 0; attempt < 3; attempt++) {
      for (const src of _TEMPLATE_SRCS) {
        try {
          _templateImg = await _loadImage(src);
          return _templateImg;
        } catch (_) {}
      }
      if (attempt < 2) await new Promise(r => setTimeout(r, 1000));
    }
    return null;
  })();
  return _loadingPromise;
}

// ─── Core drawing (shared by live preview & final generation) ───
function _drawCard(ctx, canvas, img, name, weight, fontSize) {
  ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

  if (!name) return;

  const scale = canvas.width / _FULL_W;
  let scaledSize = fontSize * scale;

  ctx.font         = `${weight} ${scaledSize}px Jarood, Saudi, Cairo, sans-serif`;
  ctx.textAlign    = 'right';
  ctx.textBaseline = 'alphabetic';

  const rightPad = 110 * scale;
  let measured = ctx.measureText(name).width;
  while (measured > canvas.width * 0.88 && scaledSize > 28 * scale) {
    scaledSize -= 4 * scale;
    ctx.font = `${weight} ${scaledSize}px Jarood, Saudi, Cairo, sans-serif`;
    measured = ctx.measureText(name).width;
  }

  ctx.fillStyle     = '#1A5C32';
  ctx.shadowColor   = 'rgba(0,0,0,0.08)';
  ctx.shadowBlur    = 4 * scale;
  ctx.shadowOffsetX = 1 * scale;
  ctx.shadowOffsetY = 1 * scale;
  ctx.fillText(name, canvas.width - rightPad, canvas.height - (_NAME_Y_OFFSET * scale));

  ctx.shadowColor   = 'transparent';
  ctx.shadowBlur    = 0;
  ctx.shadowOffsetX = 0;
  ctx.shadowOffsetY = 0;
}

// ─── Live preview (debounced with rAF) ───
function _renderLivePreview() {
  const canvas = document.getElementById('eid-preview-canvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  canvas.width  = _FULL_W;
  canvas.height = _FULL_H;

  if (!_templateImg) {
    ctx.fillStyle = '#e8f5ec';
    ctx.fillRect(0, 0, _FULL_W, _FULL_H);
    ctx.fillStyle = '#1A5C32';
    ctx.font = 'bold 40px Jarood, Saudi, Cairo, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('جاري تحميل القالب...', _FULL_W / 2, _FULL_H / 2);
    return;
  }

  const name     = document.getElementById('eid-name').value.trim() || 'اكتب اسمك هنا';
  const weight   = document.getElementById('eid-font-weight')?.value || 'bold';
  const fontSize = parseInt(document.getElementById('eid-font-size')?.value || '70');

  _drawCard(ctx, canvas, _templateImg, name, weight, fontSize);
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
    await ensureEidFonts();
    await _loadTemplateOnce();

    if (!_templateImg) {
      alert('تعذّر تحميل قالب البطاقة. يرجى تحديث الصفحة والمحاولة مرة أخرى.');
      return;
    }

    const canvas = document.getElementById('eid-canvas');
    const ctx    = canvas.getContext('2d');
    canvas.width  = _templateImg.width;
    canvas.height = _templateImg.height;

    _drawCard(ctx, canvas, _templateImg, name, weight, fontSize);

    const preview = document.getElementById('eid-preview');
    preview.style.display = 'block';
    preview.scrollIntoView({ behavior: 'smooth', block: 'center' });

  } catch (e) {
    console.error('Eid card error:', e);
    alert('حدث خطأ أثناء إنشاء البطاقة. يرجى المحاولة مرة أخرى.');
  } finally {
    btn.disabled    = false;
    btn.textContent = '✨ إنشاء البطاقة';
  }
}

function _fallbackShare(canvas, name) {
  const text = encodeURIComponent('كل عام وأنتم بخير - أيامكم سعيدة يا رب');
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
          text:  'كل عام وأنتم بخير - أيامكم سعيدة يا رب',
        }).catch(() => _fallbackShare(c, name));
      } else {
        _fallbackShare(c, name);
      }
    });
  });

  // رسالة انتظار فورية
  _renderLivePreview();

  // تحميل الخط والقالب ثم إعادة الرسم
  ensureEidFonts()
    .then(() => _loadTemplateOnce())
    .then(() => _renderLivePreview())
    .catch(() => {
      // إعادة المحاولة بعد 1.5 ثانية
      _loadingPromise = null;
      setTimeout(() => _loadTemplateOnce().then(() => _renderLivePreview()), 1500);
    });
}
