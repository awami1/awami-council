// ═══════════════════════════════════════════════════════════════
// admin-stories.js — إدارة سِيَر وقصص أبناء العائلة
// ═══════════════════════════════════════════════════════════════

var _storiesData = [];
var _storiesLoaded = false;

var STORY_CAT_LABELS = {
  'biography':  'سيرة ذاتية',
  'self_made':  'قصة عصامية',
  'eulogy':     'رثاء',
  'tribute':    'مقال تكريمي',
  'other':      'أخرى'
};

var STORY_CAT_BADGES = {
  'biography':  'badge-success',
  'self_made':  'badge-gold',
  'eulogy':     'badge-info',
  'tribute':    'badge-warning',
  'other':      'badge-gray'
};

// ─── تحميل البيانات ───
async function loadStoriesData() {
  try {
    var res = await StoriesAPI.getAll();
    _storiesData = res.data || [];
    _storiesLoaded = true;
  } catch (e) {
    console.error('Failed to load stories:', e);
    _storiesData = [];
  }
}

function getStories() { return _storiesData; }

// ─── عرض القائمة ───
function renderStories(page) {
  var search = (document.getElementById('stories-search') || {}).value || '';
  var fltStatus = (document.getElementById('stories-flt-status') || {}).value || '';
  var fltCat = (document.getElementById('stories-flt-cat') || {}).value || '';

  var all = getStories().filter(function(s) {
    if (search && s.title.indexOf(search) === -1 && (s.person_name || '').indexOf(search) === -1 && (s.excerpt || '').indexOf(search) === -1) return false;
    if (fltStatus && s.status !== fltStatus) return false;
    if (fltCat && s.category !== fltCat) return false;
    return true;
  });

  if (!_pageState.stories) _pageState.stories = 1;
  var info = paginate(all, 'stories', page, 10);
  var tbody = document.getElementById('stories-tbody');
  if (!tbody) return;

  tbody.innerHTML = info.data.length ? info.data.map(function(s, i) {
    var idx = (info.page - 1) * 10 + i + 1;
    var catLabel = STORY_CAT_LABELS[s.category] || 'أخرى';
    var catBadge = STORY_CAT_BADGES[s.category] || 'badge-gray';
    return '<tr>' +
      '<td data-label="#">' + idx + '</td>' +
      '<td data-label="العنوان"><strong>' + escAdmin(s.title) + '</strong>' + (s.excerpt ? '<br><small style="color:var(--text-muted)">' + escAdmin(s.excerpt.substring(0, 60)) + '...</small>' : '') + '</td>' +
      '<td data-label="التصنيف"><span class="badge ' + catBadge + '">' + catLabel + '</span></td>' +
      '<td data-label="الشخصية">' + (s.person_name ? escAdmin(s.person_name) : '—') + '</td>' +
      '<td data-label="الحالة"><span class="badge ' + (s.status === 'published' ? 'badge-success' : 'badge-warning') + '">' + (s.status === 'published' ? 'منشور' : 'مسودة') + '</span></td>' +
      '<td data-label="تثبيت"><button class="btn btn-outline btn-sm" onclick="toggleStoryPin(\'' + s.id + '\')" title="' + (s.is_pinned ? 'إلغاء التثبيت' : 'تثبيت') + '">' + (s.is_pinned ? '📌' : '○') + '</button></td>' +
      '<td data-label="التاريخ">' + (s.published_at || s.created_at || '').substring(0, 10) + '</td>' +
      '<td data-label="إجراءات">' +
        '<button class="btn btn-primary btn-sm" onclick="editStory(\'' + s.id + '\')">تعديل</button> ' +
        '<button class="btn btn-outline btn-sm" onclick="toggleStoryStatus(\'' + s.id + '\')">' + (s.status === 'published' ? 'إلغاء النشر' : 'نشر') + '</button> ' +
        '<button class="btn btn-danger btn-sm" onclick="deleteStory(\'' + s.id + '\')">حذف</button>' +
      '</td></tr>';
  }).join('') : '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)">لا توجد مواضيع</td></tr>';

  document.getElementById('stories-count').textContent = all.length + ' موضوع';
  document.getElementById('stories-pagination').innerHTML = renderPaginationHTML(info, 'renderStories');
}
var debouncedRenderStories = debounce(function() { _pageState.stories = 1; renderStories(); }, 300);

// ─── HTML escaping ───
function escAdmin(str) {
  if (!str) return '';
  var div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

// ─── إضافة موضوع جديد ───
function openAddStory() {
  document.getElementById('story-modal-title').textContent = 'إضافة موضوع جديد';
  document.getElementById('story-edit-id').value = '';
  document.getElementById('story-title').value = '';
  document.getElementById('story-category').value = 'biography';
  document.getElementById('story-status').value = 'draft';
  document.getElementById('story-person-name').value = '';
  document.getElementById('story-person-image').value = '';
  document.getElementById('story-person-bio').value = '';
  document.getElementById('story-cover-image').value = '';
  document.getElementById('story-cover-preview').innerHTML = '';
  document.getElementById('story-excerpt').value = '';
  document.getElementById('story-content-editor').innerHTML = '';
  document.getElementById('story-author').value = '';
  document.getElementById('story-published-at').value = '';
  document.getElementById('story-is-pinned').checked = false;
  clearValidation();
  openModal('modal-story');
}

// ─── تعديل موضوع ───
async function editStory(id) {
  var s = getStories().find(function(x) { return x.id === id; });
  if (!s) return;
  document.getElementById('story-modal-title').textContent = 'تعديل الموضوع';
  document.getElementById('story-edit-id').value = id;
  document.getElementById('story-title').value = s.title || '';
  document.getElementById('story-category').value = s.category || 'biography';
  document.getElementById('story-status').value = s.status || 'draft';
  document.getElementById('story-person-name').value = s.person_name || '';
  document.getElementById('story-person-image').value = s.person_image || '';
  document.getElementById('story-person-bio').value = s.person_bio || '';
  document.getElementById('story-cover-image').value = s.cover_image || '';
  document.getElementById('story-cover-preview').innerHTML = s.cover_image ? '<img src="' + s.cover_image + '" style="max-height:120px;border-radius:8px">' : '';
  document.getElementById('story-excerpt').value = s.excerpt || '';
  document.getElementById('story-content-editor').innerHTML = s.content || '';
  document.getElementById('story-author').value = s.author_name || '';
  document.getElementById('story-is-pinned').checked = !!s.is_pinned;

  // تاريخ النشر
  if (s.published_at) {
    var dt = s.published_at.replace(' ', 'T').substring(0, 16);
    document.getElementById('story-published-at').value = dt;
  } else {
    document.getElementById('story-published-at').value = '';
  }

  clearValidation();
  openModal('modal-story');
}

// ─── حفظ موضوع ───
async function saveStory(statusOverride) {
  clearValidation();
  if (!validateRequired('story-title', 'العنوان')) return;

  var id = document.getElementById('story-edit-id').value;
  var status = statusOverride || document.getElementById('story-status').value;
  var publishedAt = document.getElementById('story-published-at').value;

  var data = {
    title:         document.getElementById('story-title').value.trim(),
    category:      document.getElementById('story-category').value,
    status:        status,
    person_name:   document.getElementById('story-person-name').value.trim(),
    person_image:  document.getElementById('story-person-image').value.trim(),
    person_bio:    document.getElementById('story-person-bio').value.trim(),
    cover_image:   document.getElementById('story-cover-image').value.trim(),
    excerpt:       document.getElementById('story-excerpt').value.trim(),
    content:       document.getElementById('story-content-editor').innerHTML,
    author_name:   document.getElementById('story-author').value.trim(),
    is_pinned:     document.getElementById('story-is-pinned').checked ? 1 : 0,
    published_at:  publishedAt ? publishedAt.replace('T', ' ') + ':00' : null,
  };

  try {
    if (id) {
      var r = await StoriesAPI.update(id, data);
      var idx = _storiesData.findIndex(function(s) { return s.id === id; });
      if (idx >= 0) _storiesData[idx] = r.data;
      toast('تم تحديث الموضوع ✅');
    } else {
      var r = await StoriesAPI.create(data);
      _storiesData.unshift(r.data);
      toast('تم إضافة الموضوع ✅');
    }
    closeModal('modal-story');
    renderStories();
  } catch (e) {
    toast(e.message || 'حدث خطأ', 'error');
  }
}

// ─── حذف موضوع ───
function deleteStory(id) {
  confirm2('هل تريد حذف هذا الموضوع؟', async function() {
    try {
      await StoriesAPI.delete(id);
      _storiesData = _storiesData.filter(function(s) { return s.id !== id; });
      toast('تم حذف الموضوع');
      renderStories();
    } catch (e) {
      toast(e.message || 'حدث خطأ', 'error');
    }
  });
}

// ─── تبديل التثبيت ───
async function toggleStoryPin(id) {
  try {
    var r = await StoriesAPI.togglePin(id);
    var idx = _storiesData.findIndex(function(s) { return s.id === id; });
    if (idx >= 0) _storiesData[idx].is_pinned = r.data.is_pinned;
    toast(r.data.is_pinned ? 'تم تثبيت الموضوع 📌' : 'تم إلغاء التثبيت');
    renderStories();
  } catch (e) {
    toast(e.message || 'حدث خطأ', 'error');
  }
}

// ─── تبديل حالة النشر ───
async function toggleStoryStatus(id) {
  try {
    var r = await StoriesAPI.toggleStatus(id);
    var idx = _storiesData.findIndex(function(s) { return s.id === id; });
    if (idx >= 0) _storiesData[idx].status = r.data.status;
    toast(r.data.status === 'published' ? 'تم نشر الموضوع ✅' : 'تم إلغاء النشر');
    renderStories();
  } catch (e) {
    toast(e.message || 'حدث خطأ', 'error');
  }
}

// ─── رفع صورة ───
async function uploadStoryImage(input, targetId) {
  if (!input.files || !input.files[0]) return;
  var file = input.files[0];

  if (file.size > 5 * 1024 * 1024) {
    toast('حجم الصورة يتجاوز 5 ميجابايت', 'error');
    return;
  }

  var formData = new FormData();
  formData.append('image', file);
  formData.append('csrf_token', CSRF_TOKEN);

  try {
    var r = await StoriesAPI.uploadImage(formData);
    document.getElementById(targetId).value = r.url;

    // معاينة الغلاف
    if (targetId === 'story-cover-image') {
      document.getElementById('story-cover-preview').innerHTML = '<img src="' + r.url + '" style="max-height:120px;border-radius:8px">';
    }

    toast('تم رفع الصورة ✅');
  } catch (e) {
    toast(e.message || 'فشل رفع الصورة', 'error');
  }

  input.value = '';
}

// ─── إدراج صورة في المحرر ───
async function insertEditorImage(input) {
  if (!input.files || !input.files[0]) return;
  var file = input.files[0];

  if (file.size > 5 * 1024 * 1024) {
    toast('حجم الصورة يتجاوز 5 ميجابايت', 'error');
    return;
  }

  var formData = new FormData();
  formData.append('image', file);
  formData.append('csrf_token', CSRF_TOKEN);

  try {
    var r = await StoriesAPI.uploadImage(formData);
    document.getElementById('story-content-editor').focus();
    document.execCommand('insertImage', false, r.url);
    toast('تم إدراج الصورة ✅');
  } catch (e) {
    toast(e.message || 'فشل رفع الصورة', 'error');
  }

  input.value = '';
}

// ─── أوامر المحرر ───
function storyEditorCmd(cmd, val) {
  document.getElementById('story-content-editor').focus();
  if (cmd === 'formatBlock' && val) {
    document.execCommand('formatBlock', false, '<' + val + '>');
  } else {
    document.execCommand(cmd, false, val || null);
  }
}

// ─── معاينة الموضوع ───
function previewStory() {
  var title = document.getElementById('story-title').value.trim() || 'بدون عنوان';
  var category = document.getElementById('story-category').value;
  var catLabel = STORY_CAT_LABELS[category] || 'أخرى';
  var personName = document.getElementById('story-person-name').value.trim();
  var personBio = document.getElementById('story-person-bio').value.trim();
  var personImage = document.getElementById('story-person-image').value.trim();
  var coverImage = document.getElementById('story-cover-image').value.trim();
  var content = document.getElementById('story-content-editor').innerHTML;
  var author = document.getElementById('story-author').value.trim();

  var html = '';

  // غلاف
  if (coverImage) {
    html += '<div style="border-radius:12px;overflow:hidden;margin-bottom:20px;position:relative;max-height:300px">';
    html += '<img src="' + coverImage + '" style="width:100%;max-height:300px;object-fit:cover">';
    html += '<div style="position:absolute;bottom:0;left:0;right:0;padding:20px;background:linear-gradient(to top,rgba(0,0,0,.7),transparent);color:#fff">';
    html += '<span style="background:rgba(26,92,50,.88);padding:3px 12px;border-radius:16px;font-size:12px;font-weight:700">' + catLabel + '</span>';
    html += '<h2 style="font-size:24px;margin:8px 0 0;line-height:1.5">' + escAdmin(title) + '</h2>';
    html += '</div></div>';
  } else {
    html += '<span style="background:var(--green-light);padding:3px 12px;border-radius:16px;font-size:12px;font-weight:700;color:var(--green-dark)">' + catLabel + '</span>';
    html += '<h2 style="font-size:24px;margin:8px 0 16px;line-height:1.5">' + escAdmin(title) + '</h2>';
  }

  // بطاقة الشخصية
  if (personName) {
    html += '<div style="display:flex;align-items:center;gap:16px;padding:16px;border:1px solid var(--border);border-radius:12px;margin-bottom:24px;background:var(--bg-alt)">';
    if (personImage) {
      html += '<img src="' + personImage + '" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--green)">';
    } else {
      html += '<div style="width:60px;height:60px;border-radius:50%;background:var(--green-light);display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--green)">&#128100;</div>';
    }
    html += '<div>';
    html += '<div style="font-weight:700;font-size:16px">' + escAdmin(personName) + '</div>';
    if (personBio) html += '<div style="font-size:13px;color:var(--text-muted);margin-top:4px">' + escAdmin(personBio) + '</div>';
    html += '</div></div>';
  }

  // المحتوى
  html += '<div style="font-size:16px;line-height:1.9">' + content + '</div>';

  // الكاتب
  if (author) {
    html += '<div style="margin-top:20px;padding:12px;background:var(--bg-alt);border-radius:8px;font-size:14px"><strong>&#9998; ' + escAdmin(author) + '</strong></div>';
  }

  document.getElementById('story-preview-body').innerHTML = html;
  openModal('modal-story-preview');
}

// ─── Placeholder للمحرر ───
(function() {
  function setupEditorPlaceholder() {
    var editor = document.getElementById('story-content-editor');
    if (!editor) return;

    function togglePlaceholder() {
      var text = editor.textContent.trim();
      if (text === '' || text === editor.dataset.placeholder) {
        editor.classList.add('editor-empty');
      } else {
        editor.classList.remove('editor-empty');
      }
    }

    editor.addEventListener('focus', togglePlaceholder);
    editor.addEventListener('blur', togglePlaceholder);
    editor.addEventListener('input', togglePlaceholder);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupEditorPlaceholder);
  } else {
    setupEditorPlaceholder();
  }
})();
