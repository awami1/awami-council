// ============================================================
// occasion.js — صفحة المناسبات العامة (ديناميكية)
// يجلب البيانات من API ويعرض الصورة مع إمكانية كتابة الاسم
// ============================================================

var _occSettings = null;
var _occImages = [];
var _occPrimaryImg = null;
var _occTemplateImg = null;
var _occRafPending = false;

// ─── خريطة أحجام الخط (بالبكسل لرسم Canvas) ───
var _fontSizeMap = { small: 40, medium: 55, large: 70, xlarge: 90 };

// ─── تحميل صورة ───
function _occLoadImage(src) {
  return new Promise(function(resolve, reject) {
    var img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function() { resolve(img); };
    img.onerror = reject;
    img.src = src;
  });
}

// ─── تهيئة الصفحة ───
async function initOccasionPage() {
  var loadingEl = document.getElementById('occasion-loading');
  var inactiveEl = document.getElementById('occasion-inactive');
  var contentEl = document.getElementById('occasion-content');

  try {
    var res = await OccasionAPI.getPublic();

    if (!res.active) {
      if (loadingEl) loadingEl.style.display = 'none';
      if (inactiveEl) inactiveEl.style.display = 'block';
      return;
    }

    _occSettings = res.settings;
    _occImages = res.images || [];
    _occPrimaryImg = _occImages.find(function(img) { return img.is_primary; }) || _occImages[0] || null;

    // تحديث العنوان والرسالة
    var titleEl = document.getElementById('occasion-title');
    var msgEl = document.getElementById('occasion-message');
    if (titleEl) titleEl.textContent = '🎉 ' + (_occSettings.page_title || 'تهنئة المناسبة');
    if (msgEl) {
      if (_occSettings.greeting_message) {
        msgEl.textContent = _occSettings.greeting_message;
      } else {
        msgEl.textContent = 'اصنع بطاقة تهنئة شخصية بإسمك';
      }
    }

    // عرض الصور
    _renderOccasionPublicImages();

    // إظهار قسم الاسم إذا كان مفعّلاً وهناك صورة رئيسية
    if (_occSettings.name_input_enabled && _occPrimaryImg) {
      var nameSection = document.getElementById('occasion-name-section');
      if (nameSection) nameSection.style.display = 'block';

      // تحميل الصورة الرئيسية
      try {
        _occTemplateImg = await _occLoadImage(_occPrimaryImg.image_path);
      } catch (e) {
        // محاولة ثانية
        try {
          await new Promise(function(r) { setTimeout(r, 1000); });
          _occTemplateImg = await _occLoadImage(_occPrimaryImg.image_path);
        } catch (e2) {
          console.error('فشل تحميل صورة المناسبة:', e2);
        }
      }

      // تحميل الخطوط
      try {
        await Promise.all([
          document.fonts.load('normal 70px ' + _occSettings.name_font_family),
          document.fonts.load('bold 70px ' + _occSettings.name_font_family),
        ]);
      } catch (_) {
        await document.fonts.ready;
      }

      // رسم المعاينة الأولية
      _occasionDrawPreview();

      // ربط الأحداث
      _setupOccasionEvents();
    }

    // إظهار المحتوى
    if (loadingEl) loadingEl.style.display = 'none';
    if (contentEl) contentEl.style.display = 'block';

  } catch (e) {
    console.error('خطأ في تحميل صفحة المناسبة:', e);
    if (loadingEl) loadingEl.style.display = 'none';
    if (inactiveEl) inactiveEl.style.display = 'block';
  }
}

// ─── عرض الصور العامة ───
function _renderOccasionPublicImages() {
  var container = document.getElementById('occasion-images-display');
  if (!container || !_occImages.length) return;

  // إذا الاسم مفعّل، لا نعرض الصورة الرئيسية هنا (ستظهر في Canvas)
  var imagesToShow = _occImages;
  if (_occSettings.name_input_enabled) {
    imagesToShow = _occImages.filter(function(img) { return !img.is_primary; });
  }

  if (!imagesToShow.length) return;

  var html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:24px">';
  imagesToShow.forEach(function(img) {
    html += '<div style="border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-md)">';
    html += '<img src="' + img.image_path + '" alt="صورة المناسبة" style="width:100%;display:block" loading="lazy">';
    html += '</div>';
  });
  html += '</div>';
  container.innerHTML = html;
}

// ─── رسم المعاينة الحية ───
function _occasionDrawPreview() {
  var canvas = document.getElementById('occasion-canvas-preview');
  if (!canvas || !_occTemplateImg) return;

  var ctx = canvas.getContext('2d');
  canvas.width = _occTemplateImg.width;
  canvas.height = _occTemplateImg.height;

  // رسم الصورة
  ctx.drawImage(_occTemplateImg, 0, 0, canvas.width, canvas.height);

  // رسم الاسم
  var nameInput = document.getElementById('occasion-name-input');
  var name = nameInput ? nameInput.value.trim() : '';
  if (!name) name = 'اكتب اسمك هنا';

  _drawNameOnCanvas(ctx, canvas, name);
}

function _drawNameOnCanvas(ctx, canvas, name) {
  if (!_occSettings || !name) return;

  var s = _occSettings;
  var fontSize = _fontSizeMap[s.name_font_size] || 70;
  var fontFamily = s.name_font_family || 'Cairo';
  var color = s.name_font_color || '#FFFFFF';
  var shadow = s.name_text_shadow;
  var posX = (s.name_position_x || 50) / 100;
  var posY = (s.name_position_y || 80) / 100;

  var scaledSize = fontSize;

  ctx.font = 'bold ' + scaledSize + 'px ' + fontFamily + ", 'Readex Pro', Cairo, sans-serif";
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';

  // تقليص الحجم إذا كان الاسم أعرض من الصورة
  var measured = ctx.measureText(name).width;
  while (measured > canvas.width * 0.88 && scaledSize > 20) {
    scaledSize -= 4;
    ctx.font = 'bold ' + scaledSize + 'px ' + fontFamily + ", 'Readex Pro', Cairo, sans-serif";
    measured = ctx.measureText(name).width;
  }

  // الظل
  if (shadow) {
    ctx.shadowColor = 'rgba(0,0,0,0.7)';
    ctx.shadowBlur = 15;
    ctx.shadowOffsetX = 3;
    ctx.shadowOffsetY = 3;
  }

  ctx.fillStyle = color;
  ctx.fillText(name, canvas.width * posX, canvas.height * posY);

  // إعادة تعيين الظل
  ctx.shadowColor = 'transparent';
  ctx.shadowBlur = 0;
  ctx.shadowOffsetX = 0;
  ctx.shadowOffsetY = 0;
}

// ─── معاينة حية مع rAF ───
function occasionLivePreview() {
  if (_occRafPending) return;
  _occRafPending = true;
  requestAnimationFrame(function() {
    _occRafPending = false;
    _occasionDrawPreview();
  });
}

// ─── إنشاء البطاقة النهائية ───
async function _occasionGenerate() {
  var nameInput = document.getElementById('occasion-name-input');
  var name = nameInput ? nameInput.value.trim() : '';
  if (!name) {
    alert('الرجاء كتابة اسمك');
    return;
  }

  var btn = document.getElementById('occasion-gen-btn');
  if (btn) { btn.disabled = true; btn.textContent = 'جاري الإنشاء...'; }

  try {
    // تأكد من تحميل الخط
    try {
      await document.fonts.load('bold 70px ' + (_occSettings.name_font_family || 'Cairo'));
    } catch (_) {}

    var canvas = document.getElementById('occasion-canvas-final');
    if (!canvas || !_occTemplateImg) {
      alert('تعذّر تحميل الصورة. يرجى تحديث الصفحة.');
      return;
    }

    var ctx = canvas.getContext('2d');
    canvas.width = _occTemplateImg.width;
    canvas.height = _occTemplateImg.height;

    ctx.drawImage(_occTemplateImg, 0, 0, canvas.width, canvas.height);
    _drawNameOnCanvas(ctx, canvas, name);

    var result = document.getElementById('occasion-result');
    if (result) {
      result.style.display = 'block';
      result.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  } catch (e) {
    console.error('Occasion card error:', e);
    alert('حدث خطأ أثناء إنشاء البطاقة.');
  } finally {
    if (btn) { btn.disabled = false; btn.textContent = '✨ إنشاء البطاقة'; }
  }
}

// ─── تحميل البطاقة ───
function _occasionDownload() {
  var canvas = document.getElementById('occasion-canvas-final');
  var nameInput = document.getElementById('occasion-name-input');
  var name = nameInput ? nameInput.value.trim() : 'تهنئة';

  var a = document.createElement('a');
  a.download = name + '-' + Date.now() + '.png';
  a.href = canvas.toDataURL('image/png', 1.0);
  a.click();
}

// ─── مشاركة البطاقة ───
function _occasionShare() {
  var canvas = document.getElementById('occasion-canvas-final');
  var nameInput = document.getElementById('occasion-name-input');
  var name = nameInput ? nameInput.value.trim() : 'تهنئة';
  var title = _occSettings ? _occSettings.page_title : 'تهنئة المناسبة';

  canvas.toBlob(function(blob) {
    var file = new File([blob], name + '.png', { type: 'image/png' });
    if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
      navigator.share({
        files: [file],
        title: title,
        text: title + ' - مجلس عائلة العوامي',
      }).catch(function() { _occasionFallbackShare(canvas, name); });
    } else {
      _occasionFallbackShare(canvas, name);
    }
  });
}

function _occasionFallbackShare(canvas, name) {
  var text = encodeURIComponent((_occSettings ? _occSettings.page_title : 'كل عام وأنتم بخير') + ' - مجلس عائلة العوامي');
  if (confirm('سيتم فتح واتساب. حمّل الصورة أولاً ثم أرسلها.')) {
    var a = document.createElement('a');
    a.download = name + '.png';
    a.href = canvas.toDataURL('image/png', 1.0);
    a.click();
    setTimeout(function() { window.open('https://wa.me/?text=' + text, '_blank'); }, 500);
  }
}

// ─── ربط الأحداث ───
function _setupOccasionEvents() {
  var genBtn = document.getElementById('occasion-gen-btn');
  if (genBtn) genBtn.addEventListener('click', _occasionGenerate);

  var dlBtn = document.getElementById('occasion-download-btn');
  if (dlBtn) dlBtn.addEventListener('click', _occasionDownload);

  var shareBtn = document.getElementById('occasion-share-btn');
  if (shareBtn) shareBtn.addEventListener('click', _occasionShare);

  var nameInput = document.getElementById('occasion-name-input');
  if (nameInput) nameInput.addEventListener('input', occasionLivePreview);
}
