// ============================================================
// occasion-admin.js — إدارة صفحة المناسبات من لوحة التحكم
// ============================================================

var _occasionData = null;     // { settings, images }
var _occasionLoaded = false;
var _occDragState = null;     // حالة السحب لتحديد موقع الاسم

// ─── تحميل البيانات ───────────────────────────────────────

async function loadOccasionData() {
  try {
    var res = await OccasionAPI.getAdmin();
    _occasionData = res;
    _occasionLoaded = true;
    return res;
  } catch (e) {
    toast('خطأ في تحميل بيانات المناسبة: ' + e.message, 'error');
    return null;
  }
}

// ─── عرض الصفحة ───────────────────────────────────────────

async function renderOccasion() {
  if (!_occasionLoaded) {
    await loadOccasionData();
  }
  if (!_occasionData) return;

  var s = _occasionData.settings;

  // إعدادات الصفحة
  document.getElementById('occ-page-title').value = s.page_title || '';
  document.getElementById('occ-greeting-message').value = s.greeting_message || '';
  document.getElementById('occ-is-active').checked = !!s.is_active;
  _updateActiveLabel();

  // إعدادات الاسم
  document.getElementById('occ-name-enabled').checked = !!s.name_input_enabled;
  document.getElementById('occ-font-color').value = s.name_font_color || '#FFFFFF';
  document.getElementById('occ-font-color-text').value = s.name_font_color || '#FFFFFF';
  document.getElementById('occ-font-size').value = s.name_font_size || 'large';
  document.getElementById('occ-font-family').value = s.name_font_family || 'Cairo';
  document.getElementById('occ-text-shadow').checked = !!s.name_text_shadow;
  document.getElementById('occ-pos-x-display').textContent = (s.name_position_x || 50).toFixed(1) + '%';
  document.getElementById('occ-pos-y-display').textContent = (s.name_position_y || 80).toFixed(1) + '%';

  // حالة التفعيل
  _toggleNameSettingsVisibility(!!s.name_input_enabled);

  // عرض الصور
  _renderOccasionImages();

  // عرض أداة تحديد الموقع
  _renderPositionTool();
}

function _updateActiveLabel() {
  var active = document.getElementById('occ-is-active').checked;
  var badge = document.getElementById('occ-status-badge');
  var toggleText = document.getElementById('occ-is-active').closest('.occ-toggle-label').querySelector('.occ-toggle-text');
  if (active) {
    badge.textContent = 'ظاهرة';
    badge.className = 'badge badge-info';
    if (toggleText) toggleText.textContent = 'ظاهرة';
  } else {
    badge.textContent = 'مخفية';
    badge.className = 'badge badge-gray';
    if (toggleText) toggleText.textContent = 'مخفية';
  }
}

function _toggleNameSettingsVisibility(enabled) {
  var body = document.getElementById('occ-name-settings-body');
  if (body) body.style.opacity = enabled ? '1' : '0.5';
}

// ─── عرض الصور ────────────────────────────────────────────

function _renderOccasionImages() {
  var container = document.getElementById('occ-images-grid');
  var images = _occasionData ? _occasionData.images || [] : [];

  if (!images.length) {
    container.innerHTML = '<div class="empty-state"><div class="empty-icon">🖼️</div><p>لا توجد صور — ارفع صورة المناسبة</p></div>';
    return;
  }

  var html = '';
  images.forEach(function(img, idx) {
    var isPrimary = img.is_primary;
    html += '<div class="occ-image-card' + (isPrimary ? ' primary' : '') + '" data-id="' + img.id + '">';
    html += '<img src="' + img.image_path + '" alt="صورة المناسبة" loading="lazy">';
    if (isPrimary) {
      html += '<div class="occ-image-badge">الرئيسية</div>';
    }
    html += '<div class="occ-image-actions">';
    if (!isPrimary) {
      html += '<button class="btn btn-primary btn-xs" onclick="occasionSetPrimary(\'' + img.id + '\')">جعلها رئيسية</button>';
    }
    html += '<button class="btn btn-danger btn-xs" onclick="occasionDeleteImage(\'' + img.id + '\')">حذف</button>';
    html += '</div>';
    html += '</div>';
  });

  container.innerHTML = html;
}

// ─── أداة تحديد موقع الاسم ─────────────────────────────

function _renderPositionTool() {
  var container = document.getElementById('occ-position-container');
  var images = _occasionData ? _occasionData.images || [] : [];
  var primaryImg = images.find(function(img) { return img.is_primary; });

  if (!primaryImg) {
    container.innerHTML = '<div class="occ-position-placeholder">ارفع صورة رئيسية أولاً</div>';
    return;
  }

  var s = _occasionData.settings;
  var posX = s.name_position_x || 50;
  var posY = s.name_position_y || 80;

  container.innerHTML = '<div class="occ-position-wrap">' +
    '<img src="' + primaryImg.image_path + '" id="occ-position-img" crossorigin="anonymous">' +
    '<div class="occ-position-marker" id="occ-position-marker" style="left:' + posX + '%;top:' + posY + '%">+</div>' +
    '<div class="occ-name-overlay" id="occ-name-overlay" style="left:' + posX + '%;top:' + posY + '%"></div>' +
    '</div>';

  // أحداث النقر والسحب
  setTimeout(function() {
    var wrap = container.querySelector('.occ-position-wrap');
    if (!wrap) return;

    wrap.addEventListener('click', function(e) {
      if (_occDragState) return; // تجاهل النقر أثناء السحب
      _setMarkerPosition(e, wrap);
    });

    var marker = document.getElementById('occ-position-marker');
    if (marker) {
      marker.addEventListener('mousedown', _startDrag);
      marker.addEventListener('touchstart', _startDrag, { passive: false });
    }
  }, 100);

  occasionUpdatePreview();
}

function _setMarkerPosition(e, wrap) {
  var rect = wrap.getBoundingClientRect();
  var x = ((e.clientX - rect.left) / rect.width) * 100;
  var y = ((e.clientY - rect.top) / rect.height) * 100;
  x = Math.max(0, Math.min(100, x));
  y = Math.max(0, Math.min(100, y));

  var marker = document.getElementById('occ-position-marker');
  var overlay = document.getElementById('occ-name-overlay');
  if (marker) { marker.style.left = x + '%'; marker.style.top = y + '%'; }
  if (overlay) { overlay.style.left = x + '%'; overlay.style.top = y + '%'; }

  document.getElementById('occ-pos-x-display').textContent = x.toFixed(1) + '%';
  document.getElementById('occ-pos-y-display').textContent = y.toFixed(1) + '%';

  if (_occasionData) {
    _occasionData.settings.name_position_x = x;
    _occasionData.settings.name_position_y = y;
  }

  occasionUpdatePreview();
}

function _startDrag(e) {
  e.preventDefault();
  e.stopPropagation();
  _occDragState = true;

  var wrap = document.querySelector('.occ-position-wrap');
  if (!wrap) return;

  function onMove(ev) {
    var clientX, clientY;
    if (ev.touches) {
      clientX = ev.touches[0].clientX;
      clientY = ev.touches[0].clientY;
    } else {
      clientX = ev.clientX;
      clientY = ev.clientY;
    }
    _setMarkerPosition({ clientX: clientX, clientY: clientY }, wrap);
  }

  function onEnd() {
    document.removeEventListener('mousemove', onMove);
    document.removeEventListener('mouseup', onEnd);
    document.removeEventListener('touchmove', onMove);
    document.removeEventListener('touchend', onEnd);
    setTimeout(function() { _occDragState = false; }, 50);
  }

  document.addEventListener('mousemove', onMove);
  document.addEventListener('mouseup', onEnd);
  document.addEventListener('touchmove', onMove, { passive: false });
  document.addEventListener('touchend', onEnd);
}

// ─── معاينة الاسم على الصورة ───────────────────────────

function occasionUpdatePreview() {
  var overlay = document.getElementById('occ-name-overlay');
  if (!overlay) return;

  var name = (document.getElementById('occ-preview-name').value || '').trim() || 'اسم تجريبي';
  var color = document.getElementById('occ-font-color').value || '#FFFFFF';
  var size = document.getElementById('occ-font-size').value || 'large';
  var family = document.getElementById('occ-font-family').value || 'Cairo';
  var shadow = document.getElementById('occ-text-shadow').checked;

  var sizeMap = { small: '14px', medium: '18px', large: '24px', xlarge: '32px' };
  var fontSize = sizeMap[size] || '24px';

  overlay.textContent = name;
  overlay.style.color = color;
  overlay.style.fontSize = fontSize;
  overlay.style.fontFamily = "'" + family + "', sans-serif";
  overlay.style.textShadow = shadow ? '2px 2px 6px rgba(0,0,0,0.7)' : 'none';
}

function occasionSyncColorFromText() {
  var text = document.getElementById('occ-font-color-text').value.trim();
  if (/^#[0-9A-Fa-f]{6}$/.test(text)) {
    document.getElementById('occ-font-color').value = text;
    occasionUpdatePreview();
  }
}

// ─── إجراءات الحفظ ────────────────────────────────────────

async function occasionToggleActive() {
  _updateActiveLabel();
  var active = document.getElementById('occ-is-active').checked;
  try {
    var res = await OccasionAPI.update({ is_active: active ? 1 : 0 });
    _occasionData.settings = res.settings;
    toast(active ? 'تم تفعيل الصفحة' : 'تم إخفاء الصفحة');
  } catch (e) {
    toast('خطأ: ' + e.message, 'error');
    // التراجع
    document.getElementById('occ-is-active').checked = !active;
    _updateActiveLabel();
  }
}

function occasionToggleNameInput() {
  var enabled = document.getElementById('occ-name-enabled').checked;
  _toggleNameSettingsVisibility(enabled);
}

async function occasionSaveSettings() {
  var title = document.getElementById('occ-page-title').value.trim();
  if (!title) {
    showFieldError('occ-page-title', 'عنوان المناسبة مطلوب');
    return;
  }
  clearFieldError('occ-page-title');

  var data = {
    page_title: title,
    greeting_message: document.getElementById('occ-greeting-message').value.trim(),
    is_active: document.getElementById('occ-is-active').checked ? 1 : 0,
  };

  var btn = document.getElementById('occ-save-settings-btn');
  await withLoading(btn, async function() {
    try {
      var res = await OccasionAPI.update(data);
      _occasionData.settings = res.settings;
      _occasionData.images = res.images;
      toast('تم حفظ الإعدادات');
    } catch (e) {
      toast('خطأ: ' + e.message, 'error');
    }
  });
}

async function occasionSaveNameSettings() {
  var data = {
    name_input_enabled: document.getElementById('occ-name-enabled').checked ? 1 : 0,
    name_position_x: _occasionData.settings.name_position_x || 50,
    name_position_y: _occasionData.settings.name_position_y || 80,
    name_font_color: document.getElementById('occ-font-color').value,
    name_font_size: document.getElementById('occ-font-size').value,
    name_font_family: document.getElementById('occ-font-family').value,
    name_text_shadow: document.getElementById('occ-text-shadow').checked ? 1 : 0,
  };

  var btn = document.getElementById('occ-save-name-btn');
  await withLoading(btn, async function() {
    try {
      var res = await OccasionAPI.update(data);
      _occasionData.settings = res.settings;
      _occasionData.images = res.images;
      toast('تم حفظ إعدادات الاسم');
    } catch (e) {
      toast('خطأ: ' + e.message, 'error');
    }
  });
}

// ─── رفع وحذف الصور ──────────────────────────────────────

async function occasionUploadImage(input) {
  if (!input.files || !input.files[0]) return;
  var file = input.files[0];

  // التحقق من الحجم
  if (file.size > 5 * 1024 * 1024) {
    toast('حجم الصورة يتجاوز 5MB', 'error');
    input.value = '';
    return;
  }

  // التحقق من الصيغة
  var ext = file.name.split('.').pop().toLowerCase();
  if (!['jpg', 'jpeg', 'png', 'webp'].includes(ext)) {
    toast('صيغة غير مدعومة. الصيغ المسموحة: JPG, PNG, WEBP', 'error');
    input.value = '';
    return;
  }

  var formData = new FormData();
  formData.append('image', file);

  toast('جاري رفع الصورة...', 'info');

  try {
    // إضافة CSRF token يدوياً لطلب multipart
    var res = await fetch('/api/occasions.php?action=upload', {
      method: 'POST',
      headers: { 'X-CSRF-Token': CSRF_TOKEN },
      body: formData,
    }).then(function(r) { return r.json(); });

    if (res.error) throw new Error(res.error);

    _occasionData.images = res.images;
    _renderOccasionImages();
    _renderPositionTool();
    toast('تم رفع الصورة بنجاح');
  } catch (e) {
    toast('خطأ في رفع الصورة: ' + e.message, 'error');
  }

  input.value = '';
}

async function occasionSetPrimary(id) {
  try {
    // إضافة CSRF token
    var res = await fetch('/api/occasions.php?action=primary&id=' + id, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': CSRF_TOKEN
      },
    }).then(function(r) { return r.json(); });

    if (res.error) throw new Error(res.error);

    _occasionData.images = res.images;
    _renderOccasionImages();
    _renderPositionTool();
    toast('تم تعيين الصورة الرئيسية');
  } catch (e) {
    toast('خطأ: ' + e.message, 'error');
  }
}

async function occasionDeleteImage(id) {
  confirm2('هل تريد حذف هذه الصورة؟', async function() {
    try {
      var res = await OccasionAPI.deleteImage(id);
      _occasionData.images = res.images;
      _renderOccasionImages();
      _renderPositionTool();
      toast('تم حذف الصورة');
    } catch (e) {
      toast('خطأ: ' + e.message, 'error');
    }
  });
}
