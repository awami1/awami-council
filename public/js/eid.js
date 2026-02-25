// Eid card generator v2 — خط Saudi الرسمي من وزارة الثقافة السعودية

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

function updateEidPreview() {
  const name   = document.getElementById('eid-name').value.trim() || 'مثال على الخط';
  const weight = document.getElementById('eid-font-weight')?.value || 'bold';
  const el     = document.getElementById('eid-name-preview');
  if (!el) return;
  el.textContent      = name;
  el.style.fontWeight = weight;
}

function updateFontSize() {
  const size  = document.getElementById('eid-font-size').value;
  const label = document.getElementById('eid-font-size-label');
  if (label) label.textContent = size;
  updateEidPreview();
}

function _loadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload  = () => resolve(img);
    img.onerror = reject;
    img.src = src;
  });
}

async function generateEidCard() {
  const name = document.getElementById('eid-name').value.trim();
  if (!name) { alert('الرجاء كتابة اسمك'); return; }

  const weight   = document.getElementById('eid-font-weight')?.value || 'bold';
  let   fontSize = parseInt(document.getElementById('eid-font-size')?.value || '70');

  const btn = document.getElementById('eid-gen');
  btn.disabled    = true;
  btn.textContent = 'جاري الإنشاء...';

  try {
    await ensureSaudiFont();

    const canvas = document.getElementById('eid-canvas');
    const ctx    = canvas.getContext('2d');

    // Try JPEG first, then PNG, then fallback
    let templateLoaded = false;
    for (const src of ['/assets/eid-template.jpg', '/assets/eid-template.png']) {
      try {
        const img = await _loadImage(src);
        canvas.width  = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
        templateLoaded = true;
        break;
      } catch (_) {}
    }
    if (!templateLoaded) _drawFallback(ctx, canvas);

    // Set font and shrink if name is too long
    ctx.font = `${weight} ${fontSize}px Saudi, 'Readex Pro', Cairo, sans-serif`;
    ctx.textAlign    = 'center';
    ctx.textBaseline = 'alphabetic';
    let measured = ctx.measureText(name).width;
    while (measured > canvas.width * 0.88 && fontSize > 28) {
      fontSize -= 4;
      ctx.font = `${weight} ${fontSize}px Saudi, 'Readex Pro', Cairo, sans-serif`;
      measured = ctx.measureText(name).width;
    }

    // Draw name — 200px from bottom (matches EidSample.html)
    ctx.fillStyle     = '#ffffff';
    ctx.shadowColor   = 'rgba(0,0,0,0.7)';
    ctx.shadowBlur    = 15;
    ctx.shadowOffsetX = 3;
    ctx.shadowOffsetY = 3;
    ctx.fillText(name, canvas.width / 2, canvas.height - 200);

    ctx.shadowColor   = 'transparent';
    ctx.shadowBlur    = 0;
    ctx.shadowOffsetX = 0;
    ctx.shadowOffsetY = 0;

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

function _drawFallback(ctx, canvas) {
  canvas.width  = 1003;
  canvas.height = 1144;
  const g = ctx.createLinearGradient(0, 0, 0, 1144);
  g.addColorStop(0, '#0d4a2a');
  g.addColorStop(1, '#1B3456');
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  ctx.textAlign    = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillStyle    = '#fff';
  ctx.font         = 'bold 80px Saudi, Cairo, serif';
  ctx.fillText('كل عام وأنتم بخير', canvas.width / 2, 430);
  ctx.fillStyle = '#c8a84b';
  ctx.font      = 'bold 50px Saudi, Cairo, sans-serif';
  ctx.fillText('عيد مبارك', canvas.width / 2, 540);
  ctx.fillStyle = 'rgba(255,255,255,.65)';
  ctx.font      = '30px Saudi, Cairo, sans-serif';
  ctx.fillText('مجلس عائلة العوامي', canvas.width / 2, 640);
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

  updateEidPreview();
}
