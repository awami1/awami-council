# المرحلة 5: أرشيف التقارير

# tasks.md for Claude Code

-----

## السياق

هذه المرحلة تبني تبويب “الأرشيف” — واجهة كاملة لرفع وتصنيف وعرض التقارير والمستندات المالية.

**المتطلب المسبق:** المراحل 1-4 مكتملة.

**ما هو موجود فعلاً:**

- جدول `report_archive` أُنشئ في المرحلة 2
- `api/report-archive.php` يحتوي `handlePost()` فقط (أُنشئ في المرحلة 2 للأرشفة التلقائية بعد الاستيراد)
- سجلات الاستيراد من Excel موجودة في الجدول (source = ‘import’)

**المطلوب في هذه المرحلة:**

1. إكمال CRUD الـ API (GET, PUT, DELETE)
1. بناء واجهة الأرشيف كاملة (عرض + إضافة + تعديل + حذف + بحث + فلترة)

-----

## المهمة 1: إكمال API الأرشيف

### الملف: `api/report-archive.php`

أضف الـ handlers الناقصة. الملف يحتوي حالياً `ensureTable()` + `handlePost()` + router. أضف:

### 1.1 handleGetAll()

```php
function handleGetAll(): void {
    $pdo = getPDO();

    $where = 'WHERE 1=1';
    $params = [];

    // فلتر النوع
    $type = $_GET['type'] ?? '';
    if ($type && in_array($type, ['مالي','إداري','محضر اجتماع','كشف حساب','أخرى'], true)) {
        $where .= ' AND report_type = :type';
        $params[':type'] = $type;
    }

    // فلتر المصدر
    $source = $_GET['source'] ?? '';
    if ($source && in_array($source, ['manual','import','generated'], true)) {
        $where .= ' AND source = :source';
        $params[':source'] = $source;
    }

    // فلتر التاريخ
    if (!empty($_GET['date_from'])) {
        $where .= ' AND report_date >= :df';
        $params[':df'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where .= ' AND report_date <= :dt';
        $params[':dt'] = $_GET['date_to'];
    }

    // فلتر اللجنة
    if (!empty($_GET['committee_id'])) {
        $where .= ' AND committee_id = :cid';
        $params[':cid'] = $_GET['committee_id'];
    }

    // فلتر الفترة
    if (!empty($_GET['period_id'])) {
        $where .= ' AND period_id = :pid';
        $params[':pid'] = $_GET['period_id'];
    }

    // بحث نصي
    $search = trim($_GET['search'] ?? '');
    if ($search) {
        $where .= ' AND (title LIKE :search OR description LIKE :search2)';
        $params[':search'] = "%{$search}%";
        $params[':search2'] = "%{$search}%";
    }

    // الحالة (افتراضي: active)
    $status = $_GET['status'] ?? 'active';
    if (in_array($status, ['active', 'archived'], true)) {
        $where .= ' AND status = :status';
        $params[':status'] = $status;
    }

    $sql = "SELECT id, title, report_type, source, description, file_url, file_type,
                   report_date, period_id, committee_id, import_stats, status,
                   created_by, created_at
            FROM report_archive
            {$where}
            ORDER BY report_date DESC, created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $data = $stmt->fetchAll();

    // فك JSON لـ import_stats
    foreach ($data as &$row) {
        if ($row['import_stats']) {
            $row['import_stats'] = json_decode($row['import_stats'], true);
        }
    }

    respond(200, ['data' => $data, 'total' => count($data)]);
}
```

### 1.2 handleGetOne(string $id)

```php
function handleGetOne(string $id): void {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM report_archive WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) respond(404, ['error' => 'التقرير غير موجود']);

    if ($row['import_stats']) {
        $row['import_stats'] = json_decode($row['import_stats'], true);
    }

    respond(200, ['data' => $row]);
}
```

### 1.3 handlePut(string $id)

```php
function handlePut(string $id): void {
    $pdo = getPDO();
    $body = bodyJson();

    $stmt = $pdo->prepare('SELECT id, title FROM report_archive WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $existing = $stmt->fetch();
    if (!$existing) respond(404, ['error' => 'التقرير غير موجود']);

    $allowed = ['title', 'report_type', 'description', 'file_url', 'file_type',
                'report_date', 'period_id', 'committee_id', 'status'];
    $fields = [];
    $params = [':id' => $id];

    foreach ($body as $key => $value) {
        if (!in_array($key, $allowed, true)) continue;
        $fields[] = "{$key} = :{$key}";
        $params[":{$key}"] = $value;
    }

    if (isSQLite()) {
        $fields[] = "updated_at = datetime('now')";
    }

    if (empty($fields)) respond(422, ['error' => 'لا توجد بيانات للتحديث']);

    $sql = 'UPDATE report_archive SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $pdo->prepare($sql)->execute($params);

    logAudit('تعديل', 'أرشيف تقرير', $id, $params[':title'] ?? $existing['title']);
    respond(200, ['message' => 'تم تحديث التقرير']);
}
```

### 1.4 handleDelete(string $id)

```php
function handleDelete(string $id): void {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, title FROM report_archive WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) respond(404, ['error' => 'التقرير غير موجود']);

    $pdo->prepare('DELETE FROM report_archive WHERE id = :id')->execute([':id' => $id]);
    logAudit('حذف', 'أرشيف تقرير', $id, $row['title']);
    respond(200, ['message' => 'تم حذف التقرير']);
}
```

### 1.5 تحديث Router

```php
$method = $_SERVER['REQUEST_METHOD'];
$id = parseId();

try {
    match (true) {
        $method === 'GET'    && $id === null  => handleGetAll(),
        $method === 'GET'    && $id !== null  => handleGetOne($id),
        $method === 'POST'                    => handlePost(),
        $method === 'PUT'    && $id !== null  => handlePut($id),
        $method === 'DELETE' && $id !== null  => handleDelete($id),
        default                               => respond(405, ['error' => 'Method not allowed']),
    };
} catch (\Throwable $e) {
    error_log('Report Archive API error: ' . $e->getMessage());
    respond(500, ['error' => 'حدث خطأ داخلي']);
}
```

### 1.6 إضافة API client كامل

في `admin/js/admin-core.js` (أو حيث كائنات API)، استبدل `ReportArchiveAPI` المختصر بالكامل:

```javascript
var ReportArchiveAPI = {
  getAll: function(params) {
    var qs = '';
    if (params) {
      var parts = [];
      for (var key in params) {
        if (params[key]) parts.push(key + '=' + encodeURIComponent(params[key]));
      }
      if (parts.length) qs = '?' + parts.join('&');
    }
    return apiFetch('/api/report-archive.php' + qs);
  },
  getOne: function(id) { return apiFetch('/api/report-archive.php?id=' + id); },
  save: function(data) {
    return apiFetch('/api/report-archive.php', { method: 'POST', body: JSON.stringify(data) });
  },
  update: function(id, data) {
    return apiFetch('/api/report-archive.php?id=' + id, { method: 'PUT', body: JSON.stringify(data) });
  },
  remove: function(id) {
    return apiFetch('/api/report-archive.php?id=' + id, { method: 'DELETE' });
  }
};
```

-----

## المهمة 2: بناء واجهة الأرشيف

### الملف: `admin/js/admin-app.js`

استبدل دالة `renderArchiveTab()` (الـ placeholder) بالكود الفعلي.

### 2.1 حالة التبويب

```javascript
var archiveList = [];
var archiveLoading = false;
var archiveSearch = '';
var archiveFilterType = '';
var archiveFilterCommittee = '';
```

### 2.2 renderArchiveTab()

```javascript
function renderArchiveTab() {
  var container = document.getElementById('rt-archive');
  if (!container) return;

  // شريط الإجراءات
  var html = '<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px">';
  html += '<button class="btn btn-primary" onclick="openArchiveModal()">+ رفع تقرير جديد</button>';
  html += '<input type="text" id="archive-search" placeholder="🔍 بحث في العنوان والوصف..." value="' + archiveSearch + '" ';
  html += 'oninput="archiveSearch=this.value; debouncedLoadArchive()" class="form-control" style="flex:1;min-width:200px">';
  html += '</div>';

  // فلاتر
  html += '<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">';
  html += '<select id="archive-filter-type" onchange="archiveFilterType=this.value; loadArchiveList()" class="form-control" style="min-width:130px">';
  html += '<option value="">كل الأنواع</option>';
  html += '<option value="مالي"' + (archiveFilterType === 'مالي' ? ' selected' : '') + '>💰 مالي</option>';
  html += '<option value="إداري"' + (archiveFilterType === 'إداري' ? ' selected' : '') + '>📋 إداري</option>';
  html += '<option value="محضر اجتماع"' + (archiveFilterType === 'محضر اجتماع' ? ' selected' : '') + '>📝 محضر اجتماع</option>';
  html += '<option value="كشف حساب"' + (archiveFilterType === 'كشف حساب' ? ' selected' : '') + '>💳 كشف حساب</option>';
  html += '<option value="أخرى"' + (archiveFilterType === 'أخرى' ? ' selected' : '') + '>📄 أخرى</option>';
  html += '</select>';

  html += '<select id="archive-filter-committee" onchange="archiveFilterCommittee=this.value; loadArchiveList()" class="form-control" style="min-width:130px">';
  html += '<option value="">كل اللجان</option>';
  State.getCommittees().forEach(function(c) {
    html += '<option value="' + c.id + '"' + (archiveFilterCommittee === c.id ? ' selected' : '') + '>' + c.name + '</option>';
  });
  html += '</select>';
  html += '</div>';

  // قائمة التقارير
  html += '<div id="archive-list-container">';
  if (archiveLoading) {
    html += '<div style="text-align:center;padding:40px;color:var(--text-muted)">⏳ جاري التحميل...</div>';
  } else if (!archiveList.length) {
    html += '<div class="empty-state"><div class="empty-icon">📁</div><p>لا توجد تقارير في الأرشيف</p></div>';
  } else {
    html += archiveList.map(renderArchiveCard).join('');
  }
  html += '</div>';

  container.innerHTML = html;

  // تحميل البيانات
  if (!archiveList.length && !archiveLoading) loadArchiveList();
}
```

### 2.3 loadArchiveList()

```javascript
function loadArchiveList() {
  archiveLoading = true;
  var listEl = document.getElementById('archive-list-container');
  if (listEl) listEl.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)">⏳ جاري التحميل...</div>';

  var params = { status: 'active' };
  if (archiveFilterType) params.type = archiveFilterType;
  if (archiveFilterCommittee) params.committee_id = archiveFilterCommittee;
  if (archiveSearch) params.search = archiveSearch;

  ReportArchiveAPI.getAll(params)
    .then(function(res) {
      archiveList = res.data || [];
      archiveLoading = false;
      var listEl = document.getElementById('archive-list-container');
      if (listEl) {
        if (!archiveList.length) {
          listEl.innerHTML = '<div class="empty-state"><div class="empty-icon">📁</div><p>لا توجد تقارير</p></div>';
        } else {
          listEl.innerHTML = archiveList.map(renderArchiveCard).join('');
        }
      }
    })
    .catch(function(err) {
      archiveLoading = false;
      toast('خطأ في تحميل الأرشيف: ' + err.message, 'error');
    });
}

var debouncedLoadArchive = debounce(loadArchiveList, 400);
```

### 2.4 renderArchiveCard(item)

```javascript
function renderArchiveCard(item) {
  var typeBadges = {
    'مالي': { bg: '#dcfce7', color: '#166534', icon: '💰' },
    'إداري': { bg: '#dbeafe', color: '#1e40af', icon: '📋' },
    'محضر اجتماع': { bg: '#fef3c7', color: '#92400e', icon: '📝' },
    'كشف حساب': { bg: '#f3e8ff', color: '#6b21a8', icon: '💳' },
    'أخرى': { bg: '#f1f5f9', color: '#475569', icon: '📄' },
  };
  var badge = typeBadges[item.report_type] || typeBadges['أخرى'];

  var sourceBadge = '';
  if (item.source === 'import') {
    sourceBadge = '<span style="font-size:10px;background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px">📥 مُستورد من Excel</span>';
    if (item.import_stats) {
      sourceBadge += ' <span style="font-size:10px;color:var(--text-muted)">' + (item.import_stats.imported || 0) + ' معاملة</span>';
    }
  } else if (item.source === 'generated') {
    sourceBadge = '<span style="font-size:10px;background:#dcfce7;color:#166534;padding:2px 8px;border-radius:10px">📈 مُولَّد من النظام</span>';
  }

  var committeeName = '';
  if (item.committee_id) {
    var comm = State.getCommittees().find(function(c) { return c.id === item.committee_id; });
    if (comm) committeeName = ' • ' + comm.icon + ' ' + comm.name;
  }

  var dateStr = item.report_date || '';
  try { dateStr = new Date(item.report_date).toLocaleDateString('ar-SA'); } catch(e) {}

  return '<div class="archive-card">' +
    '<div class="archive-card-header">' +
      '<div class="archive-card-title">' + item.title + '</div>' +
      '<div class="archive-card-meta">' +
        '<span class="archive-badge" style="background:' + badge.bg + ';color:' + badge.color + '">' + badge.icon + ' ' + item.report_type + '</span>' +
        '<span>📅 ' + dateStr + '</span>' +
        committeeName +
      '</div>' +
      (sourceBadge ? '<div style="margin-top:4px">' + sourceBadge + '</div>' : '') +
      (item.description ? '<div class="archive-card-desc">' + item.description + '</div>' : '') +
    '</div>' +
    '<div class="archive-card-actions">' +
      (item.file_url ? '<a href="' + item.file_url + '" target="_blank" class="btn btn-xs btn-primary">🔗 فتح</a>' : '') +
      '<button class="btn btn-xs btn-outline" onclick="openArchiveModal(\'' + item.id + '\')">✏️ تعديل</button>' +
      '<button class="btn btn-xs" style="color:var(--danger)" onclick="deleteArchiveItem(\'' + item.id + '\')">🗑️</button>' +
    '</div>' +
  '</div>';
}
```

-----

## المهمة 3: مودال الإضافة/التعديل

### 3.1 HTML المودال

أضف المودال في `admin/index.php` (أو في `admin/pages/modals.php` إذا كان الموداللات منفصلة):

```html
<!-- مودال إضافة/تعديل تقرير أرشيف -->
<div class="modal-overlay" id="modal-archive">
  <div class="modal" style="max-width:500px">
    <div class="modal-header">
      <h3 id="archive-modal-title">إضافة تقرير</h3>
      <button class="modal-close" onclick="closeModal('modal-archive')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="archive-edit-id">

      <label class="form-label">العنوان *</label>
      <input type="text" id="archive-title" class="form-control" placeholder="مثال: التقرير المالي — الربع الأول 2026" maxlength="300">

      <label class="form-label" style="margin-top:12px">النوع *</label>
      <select id="archive-type" class="form-control">
        <option value="مالي">💰 مالي</option>
        <option value="إداري">📋 إداري</option>
        <option value="محضر اجتماع">📝 محضر اجتماع</option>
        <option value="كشف حساب">💳 كشف حساب</option>
        <option value="أخرى">📄 أخرى</option>
      </select>

      <label class="form-label" style="margin-top:12px">تاريخ الإصدار *</label>
      <input type="date" id="archive-date" class="form-control">

      <label class="form-label" style="margin-top:12px">رابط الملف *</label>
      <input type="url" id="archive-url" class="form-control" placeholder="https://drive.google.com/..." dir="ltr">
      <span style="font-size:11px;color:var(--text-muted)">رابط Google Drive أو أي رابط مباشر للملف</span>

      <label class="form-label" style="margin-top:12px">الوصف</label>
      <textarea id="archive-desc" class="form-control" rows="2" placeholder="وصف مختصر (اختياري)"></textarea>

      <div style="display:flex;gap:10px;margin-top:12px">
        <div style="flex:1">
          <label class="form-label">اللجنة</label>
          <select id="archive-committee" class="form-control">
            <option value="">— بدون —</option>
            <!-- يُملأ برمجياً -->
          </select>
        </div>
        <div style="flex:1">
          <label class="form-label">الفترة المالية</label>
          <select id="archive-period" class="form-control">
            <option value="">— بدون —</option>
            <!-- يُملأ برمجياً -->
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-archive')">إلغاء</button>
      <button class="btn btn-primary" id="archive-save-btn" onclick="saveArchiveItem()">💾 حفظ</button>
    </div>
  </div>
</div>
```

### 3.2 دوال المودال

```javascript
function openArchiveModal(editId) {
  // إعادة تعيين الحقول
  document.getElementById('archive-edit-id').value = '';
  document.getElementById('archive-title').value = '';
  document.getElementById('archive-type').value = 'أخرى';
  document.getElementById('archive-date').value = new Date().toISOString().split('T')[0];
  document.getElementById('archive-url').value = '';
  document.getElementById('archive-desc').value = '';
  document.getElementById('archive-committee').value = '';
  document.getElementById('archive-period').value = '';

  // ملء dropdown اللجان
  var commSelect = document.getElementById('archive-committee');
  commSelect.innerHTML = '<option value="">— بدون —</option>';
  State.getCommittees().forEach(function(c) {
    commSelect.innerHTML += '<option value="' + c.id + '">' + c.name + '</option>';
  });

  // ملء dropdown الفترات — من API أو State
  // (حسب ما هو متاح)

  if (editId) {
    // وضع التعديل
    document.getElementById('archive-modal-title').textContent = 'تعديل التقرير';
    var item = archiveList.find(function(r) { return r.id === editId; });
    if (item) {
      document.getElementById('archive-edit-id').value = item.id;
      document.getElementById('archive-title').value = item.title;
      document.getElementById('archive-type').value = item.report_type;
      document.getElementById('archive-date').value = item.report_date;
      document.getElementById('archive-url').value = item.file_url || '';
      document.getElementById('archive-desc').value = item.description || '';
      document.getElementById('archive-committee').value = item.committee_id || '';
      document.getElementById('archive-period').value = item.period_id || '';
    }
  } else {
    document.getElementById('archive-modal-title').textContent = 'إضافة تقرير جديد';
  }

  openModal('modal-archive');
}

function saveArchiveItem() {
  var title = document.getElementById('archive-title').value.trim();
  var reportDate = document.getElementById('archive-date').value;
  var fileUrl = document.getElementById('archive-url').value.trim();

  if (!title) { toast('العنوان مطلوب', 'error'); return; }
  if (!reportDate) { toast('التاريخ مطلوب', 'error'); return; }
  if (!fileUrl) { toast('رابط الملف مطلوب', 'error'); return; }

  var data = {
    title: title,
    report_type: document.getElementById('archive-type').value,
    report_date: reportDate,
    file_url: fileUrl,
    file_type: guessFileType(fileUrl),
    description: document.getElementById('archive-desc').value.trim(),
    committee_id: document.getElementById('archive-committee').value || null,
    period_id: document.getElementById('archive-period').value || null,
  };

  var editId = document.getElementById('archive-edit-id').value;
  var btn = document.getElementById('archive-save-btn');
  btn.disabled = true;
  btn.textContent = 'جاري الحفظ...';

  var promise = editId
    ? ReportArchiveAPI.update(editId, data)
    : ReportArchiveAPI.save(data);

  promise.then(function() {
    toast(editId ? 'تم تحديث التقرير' : 'تم إضافة التقرير', 'success');
    closeModal('modal-archive');
    loadArchiveList();
  }).catch(function(err) {
    toast('خطأ: ' + err.message, 'error');
  }).finally(function() {
    btn.disabled = false;
    btn.textContent = '💾 حفظ';
  });
}

function guessFileType(url) {
  var lower = url.toLowerCase();
  if (lower.includes('.pdf')) return 'pdf';
  if (lower.includes('.xlsx') || lower.includes('.xls')) return 'xlsx';
  if (lower.includes('.csv')) return 'csv';
  if (lower.includes('.jpg') || lower.includes('.jpeg')) return 'jpg';
  if (lower.includes('.png')) return 'png';
  if (lower.includes('.docx') || lower.includes('.doc')) return 'docx';
  if (lower.includes('drive.google.com')) return 'gdrive';
  return 'link';
}

function deleteArchiveItem(id) {
  if (!confirm('حذف هذا التقرير نهائياً؟')) return;
  ReportArchiveAPI.remove(id)
    .then(function() {
      toast('تم الحذف', 'success');
      loadArchiveList();
    })
    .catch(function(err) { toast('خطأ: ' + err.message, 'error'); });
}
```

-----

## المهمة 4: CSS الأرشيف

### الملف: `admin/css/admin.css`

```css
/* ═══ Archive Cards ═══ */
.archive-card {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 12px;
  margin-bottom: 10px;
  transition: border-color 0.2s;
}
.archive-card:hover {
  border-color: var(--green);
}
.archive-card-header {
  flex: 1;
  min-width: 0;
}
.archive-card-title {
  font-weight: 700;
  font-size: 15px;
  margin-bottom: 6px;
}
.archive-card-meta {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
  font-size: 12px;
  color: var(--text-muted);
}
.archive-badge {
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
}
.archive-card-desc {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 6px;
  line-height: 1.5;
  max-height: 40px;
  overflow: hidden;
}
.archive-card-actions {
  display: flex;
  gap: 6px;
  align-items: center;
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .archive-card {
    flex-direction: column;
  }
  .archive-card-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
```

-----

## المهمة 5: الاختبار

### 5.1 اختبار API

```bash
# جلب كل التقارير
curl -s "https://alawami.site/api/report-archive.php" \
  -H "X-CSRF-Token: ..." --cookie "..." | jq '.data | length'

# إضافة تقرير
curl -s -X POST "https://alawami.site/api/report-archive.php" \
  -H "Content-Type: application/json" -H "X-CSRF-Token: ..." --cookie "..." \
  -d '{"title":"تقرير تجريبي","report_type":"مالي","report_date":"2026-03-21","file_url":"https://example.com/test.pdf"}' | jq .

# بحث
curl -s "https://alawami.site/api/report-archive.php?search=تجريبي" \
  -H "X-CSRF-Token: ..." --cookie "..." | jq '.data[0].title'

# فلترة بالنوع
curl -s "https://alawami.site/api/report-archive.php?type=كشف+حساب" \
  -H "X-CSRF-Token: ..." --cookie "..." | jq '.data | length'

# حذف
curl -s -X DELETE "https://alawami.site/api/report-archive.php?id=UUID" \
  -H "X-CSRF-Token: ..." --cookie "..." | jq .
```

### 5.2 اختبار الواجهة

1. افتح تبويب “الأرشيف” → يعرض أي سجلات موجودة (من الاستيراد السابق)
1. انقر “رفع تقرير جديد” → يفتح المودال
1. املأ الحقول + رابط → احفظ → يظهر في القائمة
1. انقر “تعديل” → يفتح المودال بالبيانات → عدّل → احفظ → يتحدث
1. انقر “حذف” → تأكيد → يُحذف
1. ابحث بعنوان → القائمة تتفلتر
1. فلتر بالنوع → يعمل
1. فلتر باللجنة → يعمل
1. تقرير مُستورد من Excel (source = import) → يعرض badge “مُستورد من Excel” + عدد المعاملات
1. الوضع المظلم يعمل
1. الموبايل: البطاقات تتكدس عمودياً

### 5.3 حالات حدية

- لا توجد تقارير → رسالة “لا توجد تقارير في الأرشيف”
- بحث بدون نتائج → نفس الرسالة
- رابط غير صالح → يُحفظ كـ URL عادي (لا validation صارم على الرابط)
- عنوان فارغ → رسالة خطأ من الـ API
