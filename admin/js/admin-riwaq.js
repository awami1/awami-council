// ═══════════════════════════════════════════════════════════════
// admin-riwaq.js — إدارة الرِّوَاق (معرض الحكايات السينمائي)
// ═══════════════════════════════════════════════════════════════

var _riwaqData = [];
var _riwaqLoaded = false;

var RIWAQ_TYPE_BADGES = {
  'سيرة ذاتية': 'badge-success',
  'رثاء':       'badge-info',
  'قصة نجاح':   'badge-gold',
  'ذكريات':     'badge-warning',
  'وصايا':      'badge-gray'
};

// ─── تحميل البيانات ───
async function loadRiwaqData() {
  try {
    var res = await GalleryStoriesAPI.getAll();
    _riwaqData = res.data || [];
    _riwaqLoaded = true;
  } catch (e) {
    console.error('Failed to load riwaq data:', e);
    _riwaqData = [];
  }
}

// ─── HTML escaping ───
function _escRiwaq(str) {
  if (!str) return '';
  var div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

// ─── عرض القائمة ───
function renderRiwaq(page) {
  var search  = (document.getElementById('riwaq-search') || {}).value || '';
  var fltType = (document.getElementById('riwaq-flt-type') || {}).value || '';

  var all = _riwaqData.filter(function(s) {
    if (search && s.title.indexOf(search) === -1 && (s.author_name || '').indexOf(search) === -1 && (s.subtitle || '').indexOf(search) === -1) return false;
    if (fltType && s.type !== fltType) return false;
    return true;
  });

  if (!_pageState.riwaq) _pageState.riwaq = 1;
  var info = paginate(all, 'riwaq', page, 10);
  var tbody = document.getElementById('riwaq-tbody');
  if (!tbody) return;

  tbody.innerHTML = info.data.length ? info.data.map(function(s, i) {
    var idx = (info.page - 1) * 10 + i + 1;
    var typeBadge = RIWAQ_TYPE_BADGES[s.type] || 'badge-gray';
    return '<tr>' +
      '<td data-label="#">' + idx + '</td>' +
      '<td data-label="الترتيب"><span style="font-weight:700;color:var(--text-muted)">' + s.display_order + '</span></td>' +
      '<td data-label="العنوان"><strong>' + _escRiwaq(s.title) + '</strong>' + (s.subtitle ? '<br><small style="color:var(--text-muted)">' + _escRiwaq(s.subtitle.substring(0, 60)) + '</small>' : '') + '</td>' +
      '<td data-label="النوع"><span class="badge ' + typeBadge + '">' + _escRiwaq(s.type) + '</span></td>' +
      '<td data-label="السنة">' + _escRiwaq(s.year_range || '—') + '</td>' +
      '<td data-label="مفعّل"><button class="btn btn-outline btn-sm" onclick="toggleGalleryStoryActive(\'' + s.id + '\')" title="' + (s.is_active ? 'تعطيل' : 'تفعيل') + '">' + (s.is_active ? '✅' : '❌') + '</button></td>' +
      '<td data-label="إجراءات">' +
        '<button class="btn btn-primary btn-sm" onclick="editGalleryStory(\'' + s.id + '\')">تعديل</button> ' +
        '<button class="btn btn-danger btn-sm" onclick="deleteGalleryStory(\'' + s.id + '\')">حذف</button>' +
      '</td></tr>';
  }).join('') : '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">لا توجد قصص</td></tr>';

  document.getElementById('riwaq-count').textContent = all.length + ' قصة';
  document.getElementById('riwaq-pagination').innerHTML = renderPaginationHTML(info, 'renderRiwaq');
}

var debouncedRenderRiwaq = debounce(function() { _pageState.riwaq = 1; renderRiwaq(); }, 300);

// ─── إضافة قصة جديدة ───
function openAddGalleryStory() {
  document.getElementById('riwaq-modal-title').textContent = 'إضافة قصة جديدة';
  document.getElementById('gs-edit-id').value = '';
  document.getElementById('gs-title').value = '';
  document.getElementById('gs-subtitle').value = '';
  document.getElementById('gs-type').value = 'سيرة ذاتية';
  document.getElementById('gs-year-range').value = '';
  document.getElementById('gs-quote').value = '';
  document.getElementById('gs-content-editor').innerHTML = '';
  document.getElementById('gs-author').value = '';
  document.getElementById('gs-read-time').value = '5';
  document.getElementById('gs-order').value = '0';
  document.getElementById('gs-color-primary').value = '#0B3D2E';
  document.getElementById('gs-color-secondary').value = '#1A6B4A';
  document.getElementById('gs-color-accent').value = '#D4AF37';
  document.getElementById('gs-is-active').checked = true;
  clearValidation();
  openModal('modal-riwaq');
}

// ─── تعديل قصة ───
function editGalleryStory(id) {
  var s = _riwaqData.find(function(x) { return x.id === id; });
  if (!s) return;
  document.getElementById('riwaq-modal-title').textContent = 'تعديل القصة';
  document.getElementById('gs-edit-id').value = id;
  document.getElementById('gs-title').value = s.title || '';
  document.getElementById('gs-subtitle').value = s.subtitle || '';
  document.getElementById('gs-type').value = s.type || 'سيرة ذاتية';
  document.getElementById('gs-year-range').value = s.year_range || '';
  document.getElementById('gs-quote').value = s.quote || '';
  document.getElementById('gs-content-editor').innerHTML = s.full_text || '';
  document.getElementById('gs-author').value = s.author_name || '';
  document.getElementById('gs-read-time').value = s.read_time || 5;
  document.getElementById('gs-order').value = s.display_order || 0;
  document.getElementById('gs-color-primary').value = s.color_primary || '#0B3D2E';
  document.getElementById('gs-color-secondary').value = s.color_secondary || '#1A6B4A';
  document.getElementById('gs-color-accent').value = s.color_accent || '#D4AF37';
  document.getElementById('gs-is-active').checked = !!s.is_active;
  clearValidation();
  openModal('modal-riwaq');
}

// ─── حفظ قصة ───
async function saveGalleryStory() {
  clearValidation();
  if (!validateRequired('gs-title', 'العنوان')) return;

  var id = document.getElementById('gs-edit-id').value;
  var data = {
    title:           document.getElementById('gs-title').value.trim(),
    subtitle:        document.getElementById('gs-subtitle').value.trim(),
    type:            document.getElementById('gs-type').value,
    year_range:      document.getElementById('gs-year-range').value.trim(),
    quote:           document.getElementById('gs-quote').value.trim(),
    full_text:       document.getElementById('gs-content-editor').innerHTML,
    author_name:     document.getElementById('gs-author').value.trim(),
    read_time:       parseInt(document.getElementById('gs-read-time').value) || 5,
    display_order:   parseInt(document.getElementById('gs-order').value) || 0,
    color_primary:   document.getElementById('gs-color-primary').value,
    color_secondary: document.getElementById('gs-color-secondary').value,
    color_accent:    document.getElementById('gs-color-accent').value,
    is_active:       document.getElementById('gs-is-active').checked ? 1 : 0,
  };

  try {
    if (id) {
      var r = await GalleryStoriesAPI.update(id, data);
      var idx = _riwaqData.findIndex(function(s) { return s.id === id; });
      if (idx >= 0) _riwaqData[idx] = r.data;
      toast('تم تحديث القصة ✅');
    } else {
      var r = await GalleryStoriesAPI.create(data);
      _riwaqData.push(r.data);
      toast('تم إضافة القصة ✅');
    }
    closeModal('modal-riwaq');
    renderRiwaq();
  } catch (e) {
    toast(e.message || 'حدث خطأ', 'error');
  }
}

// ─── حذف قصة ───
function deleteGalleryStory(id) {
  confirm2('هل تريد حذف هذه القصة؟', async function() {
    try {
      await GalleryStoriesAPI.delete(id);
      _riwaqData = _riwaqData.filter(function(s) { return s.id !== id; });
      toast('تم حذف القصة');
      renderRiwaq();
    } catch (e) {
      toast(e.message || 'حدث خطأ', 'error');
    }
  });
}

// ─── تبديل التفعيل ───
async function toggleGalleryStoryActive(id) {
  var s = _riwaqData.find(function(x) { return x.id === id; });
  if (!s) return;
  try {
    var newVal = s.is_active ? 0 : 1;
    var r = await GalleryStoriesAPI.update(id, { is_active: newVal });
    var idx = _riwaqData.findIndex(function(x) { return x.id === id; });
    if (idx >= 0) _riwaqData[idx] = r.data;
    toast(newVal ? 'تم تفعيل القصة ✅' : 'تم تعطيل القصة');
    renderRiwaq();
  } catch (e) {
    toast(e.message || 'حدث خطأ', 'error');
  }
}

// ─── ألوان preset ───
function setGSColors(primary, secondary, accent) {
  document.getElementById('gs-color-primary').value = primary;
  document.getElementById('gs-color-secondary').value = secondary;
  document.getElementById('gs-color-accent').value = accent;
}

// ─── أوامر المحرر ───
function gsEditorCmd(cmd, val) {
  document.getElementById('gs-content-editor').focus();
  if (cmd === 'formatBlock' && val) {
    document.execCommand('formatBlock', false, '<' + val + '>');
  } else {
    document.execCommand(cmd, false, val || null);
  }
}

// ─── Placeholder للمحرر ───
(function() {
  function setupGSEditorPlaceholder() {
    var editor = document.getElementById('gs-content-editor');
    if (!editor) return;
    function toggle() {
      var text = editor.textContent.trim();
      if (text === '' || text === editor.dataset.placeholder) {
        editor.classList.add('editor-empty');
      } else {
        editor.classList.remove('editor-empty');
      }
    }
    editor.addEventListener('focus', toggle);
    editor.addEventListener('blur', toggle);
    editor.addEventListener('input', toggle);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupGSEditorPlaceholder);
  } else {
    setupGSEditorPlaceholder();
  }
})();
