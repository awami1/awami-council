// ============================================================
// admin-ai.js — وكيل الذكاء الاصطناعي
// الوظيفة 1: استيراد أعضاء ذكي
// الوظيفة 2: تحليل التقارير والأرشفة والميزانية
// ============================================================

// ── AI API Client ──
const AIAPI = {
  check:          ()     => apiFetch(API_BASE + '/ai.php?action=check'),
  analyzeMembers: (data) => apiFetch(API_BASE + '/ai.php?action=analyze-members', { method: 'POST', body: JSON.stringify(data), headers: { 'Content-Type': 'application/json' } }),
  analyzeReport:  (data) => apiFetch(API_BASE + '/ai.php?action=analyze-report',  { method: 'POST', body: JSON.stringify(data), headers: { 'Content-Type': 'application/json' } }),
  saveReport:     (data) => apiFetch(API_BASE + '/ai.php?action=save-report',     { method: 'POST', body: JSON.stringify(data), headers: { 'Content-Type': 'application/json' } }),
  budgetItems:    (data) => apiFetch(API_BASE + '/ai.php?action=budget-items',    { method: 'POST', body: JSON.stringify(data), headers: { 'Content-Type': 'application/json' } }),
  searchReports:  (q)    => apiFetch(API_BASE + '/ai.php?action=search-reports',  { method: 'POST', body: JSON.stringify({ query: q }), headers: { 'Content-Type': 'application/json' } }),
  getReports:     ()     => apiFetch(API_BASE + '/ai.php?action=reports'),
  getReport:      (id)   => apiFetch(API_BASE + '/ai.php?action=report&id=' + id),
  deleteReport:   (id)   => apiFetch(API_BASE + '/ai.php?action=report&id=' + id, { method: 'DELETE' }),
  quarterlyDraft: (q, y) => apiFetch(API_BASE + '/ai.php?action=quarterly-draft&quarter=' + q + '&year=' + y),
};

var _aiAvailable = null;
var _importData = null;
var _reportAnalysis = null;

// ── Check AI availability on page load ──
async function checkAIStatus() {
  try {
    var res = await AIAPI.check();
    _aiAvailable = res.available;
    var banner = document.getElementById('ai-status-banner');
    if (banner) {
      if (_aiAvailable) {
        banner.style.display = 'flex';
        banner.style.background = 'linear-gradient(135deg,#dcfce7,#d1fae5)';
        banner.style.border = '1.5px solid #86efac';
        banner.innerHTML = '<span style="font-size:18px">🤖</span><span style="color:#166534;font-weight:600">الذكاء الاصطناعي متصل ونشط (Claude AI)</span>';
      } else {
        banner.style.display = 'flex';
        banner.style.background = 'linear-gradient(135deg,#fef9c3,#fef3c7)';
        banner.style.border = '1.5px solid #fde047';
        banner.innerHTML = '<span style="font-size:18px">⚡</span><span style="color:#854d0e;font-weight:600">الوضع المحلي — أضف ANTHROPIC_API_KEY في .env لتفعيل AI</span>';
      }
    }
  } catch (e) {
    _aiAvailable = false;
  }
}

// ── Tab Switching ──
function switchAITab(tabName, el) {
  document.querySelectorAll('.ai-tab-content').forEach(function(t) { t.style.display = 'none'; });
  document.querySelectorAll('.ai-tab').forEach(function(t) {
    t.style.borderBottomColor = 'transparent';
    t.style.color = 'var(--text-muted)';
    t.style.fontWeight = '600';
  });
  var target = document.getElementById('ai-tab-' + tabName);
  if (target) target.style.display = 'block';
  if (el) {
    el.style.borderBottomColor = 'var(--green)';
    el.style.color = 'var(--green-dark)';
    el.style.fontWeight = '700';
  }
  if (tabName === 'archive') loadArchivedReports();
}

// ── Render AI Page (called from admin-app.js showPage) ──
function renderAIPage() {
  checkAIStatus();
}

// ============================================================
// FEATURE 1: SMART MEMBER IMPORT
// ============================================================

function handleAIFileUpload(event) {
  var file = event.target.files[0];
  if (!file) return;

  var validExts = /\.(xlsx|xls|csv)$/i;
  if (!validExts.test(file.name)) {
    toast('نوع الملف غير مدعوم. يرجى رفع ملف Excel أو CSV', 'error');
    return;
  }

  if (file.size > 10 * 1024 * 1024) {
    toast('حجم الملف يتجاوز 10 ميجابايت', 'error');
    return;
  }

  // Show processing
  document.getElementById('upload-area').style.display = 'none';
  document.getElementById('ai-import-processing').style.display = 'block';
  document.getElementById('ai-import-results').style.display = 'none';
  setImportProgress('جاري قراءة الملف...', 15);

  var reader = new FileReader();
  reader.onload = function(e) {
    try {
      var data = new Uint8Array(e.target.result);
      var workbook = XLSX.read(data, { type: 'array', cellDates: true });

      // Extract all cells from all sheets
      var allCells = [];
      workbook.SheetNames.forEach(function(sheetName) {
        var sheet = workbook.Sheets[sheetName];
        var json = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '' });
        json.forEach(function(row, rowIdx) {
          if (Array.isArray(row)) {
            row.forEach(function(cell) {
              var val = String(cell || '').trim();
              if (val !== '') allCells.push({ value: val, sheet: sheetName, row: rowIdx + 1 });
            });
          }
        });
      });

      if (allCells.length === 0) {
        toast('الملف فارغ أو لا يحتوي على بيانات', 'error');
        resetImport();
        return;
      }

      setImportProgress('جاري التحليل الذكي... (' + allCells.length + ' خلية)', 40);
      analyzeMembers(allCells);

    } catch (err) {
      console.error('خطأ في قراءة الملف:', err);
      toast('حدث خطأ في قراءة الملف: ' + err.message, 'error');
      resetImport();
    }
  };

  reader.onerror = function() {
    toast('فشل قراءة الملف', 'error');
    resetImport();
  };

  reader.readAsArrayBuffer(file);
}

async function analyzeMembers(cells) {
  setImportProgress('جاري إرسال البيانات للتحليل...', 55);

  var existingMembers = DB.members.map(function(m) {
    return { id: m.id, name: m.name, family: m.family, phone: m.phone };
  });

  var branches = DB.branches.map(function(b) {
    return { id: b.id, name: b.name };
  });

  var familyTree = (DB.familyTreeMembers || []).map(function(f) {
    return { id: f.id, name: f.name };
  });

  try {
    setImportProgress(_aiAvailable ? 'تحليل بالذكاء الاصطناعي...' : 'تحليل محلي...', 70);

    var result = await AIAPI.analyzeMembers({
      cells: cells,
      existingMembers: existingMembers,
      familyTree: familyTree,
      branches: branches,
    });

    _importData = result.data;
    setImportProgress('اكتمل التحليل!', 100);

    setTimeout(function() {
      document.getElementById('ai-import-processing').style.display = 'none';
      displayImportResults(result.data, result.mode, result.warning);
    }, 500);

  } catch (err) {
    console.error('فشل التحليل:', err);
    toast('فشل التحليل: ' + err.message, 'error');
    resetImport();
  }
}

function displayImportResults(data, mode, warning) {
  var container = document.getElementById('ai-import-results');
  container.style.display = 'block';

  var stats = data.stats || {};
  var modeLabel = mode === 'ai' ? '🤖 Claude AI' : '⚡ تحليل محلي';
  var html = '';

  // Warning
  if (warning) {
    html += '<div style="background:#fef3c7;border:1.5px solid #fde047;border-radius:10px;padding:12px;margin-bottom:16px;font-size:13px;color:#854d0e">' +
      '⚠️ ' + warning + '</div>';
  }

  // Stats Summary
  html += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px">';
  html += statMini('📊', 'إجمالي الخلايا', stats.totalCells || 0, '#075985');
  html += statMini('✅', 'أسماء مؤكدة', stats.confirmedNames || 0, '#166534');
  html += statMini('⚠️', 'تحتاج مراجعة', stats.reviewNames || 0, '#854d0e');
  html += statMini('❓', 'بيانات أخرى', stats.unknownCells || 0, '#6b7280');
  html += statMini('🔄', 'مكررة', stats.duplicateCount || 0, '#991b1b');
  html += statMini('🔧', 'وضع التحليل', modeLabel, '#4338ca');
  html += '</div>';

  // Section 1: Confirmed Names
  var confirmed = data.confirmed || [];
  if (confirmed.length > 0) {
    html += '<div class="card ai-section-confirmed" style="margin-bottom:16px;border-color:#86efac">';
    html += '<div class="card-header" style="background:#dcfce7"><div class="card-title" style="color:#166534">✅ أسماء مؤكدة (' + confirmed.length + ') — جاهزة للإضافة</div>';
    html += '<label style="font-size:12px;color:#166534;cursor:pointer;display:flex;align-items:center;gap:6px"><input type="checkbox" onchange="toggleAllImport(this,\'confirmed\')" checked> تحديد الكل</label></div>';
    html += '<div class="card-body" style="padding:0;max-height:400px;overflow-y:auto">';
    confirmed.forEach(function(item, idx) {
      html += memberImportRow(item, 'confirmed', idx);
    });
    html += '</div></div>';
  }

  // Section 2: Needs Review
  var review = data.needsReview || [];
  if (review.length > 0) {
    html += '<div class="card ai-section-review" style="margin-bottom:16px;border-color:#fde047">';
    html += '<div class="card-header" style="background:#fef9c3"><div class="card-title" style="color:#854d0e">⚠️ تحتاج مراجعة (' + review.length + ')</div>';
    html += '<label style="font-size:12px;color:#854d0e;cursor:pointer;display:flex;align-items:center;gap:6px"><input type="checkbox" onchange="toggleAllImport(this,\'review\')"> تحديد الكل</label></div>';
    html += '<div class="card-body" style="padding:0;max-height:400px;overflow-y:auto">';
    review.forEach(function(item, idx) {
      html += memberImportRow(item, 'review', idx);
    });
    html += '</div></div>';
  }

  // Section 3: Duplicates
  var duplicates = data.duplicates || [];
  if (duplicates.length > 0) {
    html += '<div class="card" style="margin-bottom:16px;border-color:#fca5a5">';
    html += '<div class="card-header" style="background:#fef2f2"><div class="card-title" style="color:#991b1b">🔄 أسماء مكررة (' + duplicates.length + ')</div></div>';
    html += '<div class="card-body" style="padding:0;max-height:300px;overflow-y:auto">';
    duplicates.forEach(function(dup) {
      html += '<div style="display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid #fecaca">';
      html += '<span style="font-size:14px">🔄</span>';
      html += '<div style="flex:1"><div style="font-weight:600;font-size:13px">' + esc(dup.name) + '</div>';
      html += '<div style="font-size:11px;color:#991b1b">مكرر مع: ' + esc(dup.duplicateOf) + ' (' + (dup.type === 'في_النظام' ? 'موجود في النظام' : 'مكرر في الملف') + ')</div></div></div>';
    });
    html += '</div></div>';
  }

  // Section 4: Unknown Data
  var unknown = data.unknownData || [];
  if (unknown.length > 0) {
    html += '<details style="margin-bottom:16px">';
    html += '<summary style="cursor:pointer;padding:12px 16px;background:var(--bg);border:1px solid var(--border);border-radius:10px;font-weight:600;font-size:13px;color:var(--text-muted)">❓ بيانات غير معروفة (' + unknown.length + ') — اضغط للعرض</summary>';
    html += '<div style="border:1px solid var(--border);border-top:none;border-radius:0 0 10px 10px;max-height:300px;overflow-y:auto">';
    unknown.forEach(function(item) {
      var typeLabel = { 'رأس_جدول': '🏷️ رأس جدول', 'رقم_هاتف': '📱 رقم هاتف', 'رقم_هوية': '🪪 رقم هوية', 'خلية_فارغة': '⬜ فارغة', 'بيانات_أخرى': '📝 أخرى' };
      html += '<div style="display:flex;align-items:center;gap:10px;padding:8px 16px;border-bottom:1px solid var(--border);font-size:12px">';
      html += '<span>' + (typeLabel[item.detectedType] || '📝') + '</span>';
      html += '<span style="color:var(--text-muted)">' + esc(item.value || '(فارغ)') + '</span>';
      html += '<span style="margin-right:auto;font-size:11px;color:var(--text-muted)">سطر ' + item.row + '</span>';
      html += '</div>';
    });
    html += '</div></details>';
  }

  // Action Buttons
  html += '<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:20px;padding-top:16px;border-top:2px solid var(--border)">';
  html += '<button class="btn btn-primary" onclick="confirmImport()">✅ إضافة المحددين للنظام</button>';
  html += '<button class="btn btn-outline" onclick="resetImport()">🔄 تحليل ملف جديد</button>';
  html += '</div>';

  container.innerHTML = html;
}

function memberImportRow(item, section, idx) {
  var existsBadge = item.existsInSystem ?
    '<span style="background:#dbeafe;color:#1e40af;font-size:10px;padding:2px 8px;border-radius:6px;font-weight:600">موجود في النظام</span>' :
    '<span style="background:#dcfce7;color:#166534;font-size:10px;padding:2px 8px;border-radius:6px;font-weight:600">جديد</span>';

  var branchBadge = item.suggestedBranch ?
    '<span style="background:#f3e8ff;color:#7e22ce;font-size:10px;padding:2px 8px;border-radius:6px">فرع: ' + esc(item.suggestedBranch) + '</span>' : '';

  var reasonLine = item.reason ?
    '<div style="font-size:11px;color:#d97706;margin-top:2px">⚠️ ' + esc(item.reason) + '</div>' : '';

  var checked = section === 'confirmed' && !item.existsInSystem ? 'checked' : '';

  var html = '<div style="display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border)" data-section="' + section + '" data-idx="' + idx + '">';
  html += '<input type="checkbox" class="import-cb import-cb-' + section + '" data-section="' + section + '" data-idx="' + idx + '" ' + checked + (item.existsInSystem ? ' disabled title="موجود مسبقاً"' : '') + '>';
  html += '<div style="flex:1"><div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">';
  html += '<span style="font-weight:600;font-size:13px">' + esc(item.name) + '</span>';
  html += existsBadge + branchBadge;
  html += '</div>' + reasonLine;
  if (item.phone) html += '<div style="font-size:11px;color:var(--text-muted);margin-top:2px">📱 ' + esc(item.phone) + '</div>';
  html += '</div>';

  // Action dropdown for non-existing members
  if (!item.existsInSystem) {
    html += '<select class="form-control import-action" data-section="' + section + '" data-idx="' + idx + '" style="max-width:160px;font-size:11px;padding:4px 8px">';
    html += '<option value="add">إضافة كعضو جديد</option>';
    html += '<option value="ignore">تجاهل</option>';
    html += '</select>';
  }

  html += '</div>';
  return html;
}

function toggleAllImport(masterCb, section) {
  document.querySelectorAll('.import-cb-' + section + ':not(:disabled)').forEach(function(cb) {
    cb.checked = masterCb.checked;
  });
}

async function confirmImport() {
  if (!_importData) return;

  var toAdd = [];

  // Collect checked items
  document.querySelectorAll('.import-cb:checked:not(:disabled)').forEach(function(cb) {
    var section = cb.dataset.section;
    var idx = parseInt(cb.dataset.idx);
    var actionEl = document.querySelector('.import-action[data-section="' + section + '"][data-idx="' + idx + '"]');
    var action = actionEl ? actionEl.value : 'add';
    if (action !== 'add') return;

    var list = section === 'confirmed' ? _importData.confirmed : _importData.needsReview;
    var item = list ? list[idx] : null;
    if (item && !item.existsInSystem) {
      toAdd.push(item);
    }
  });

  if (toAdd.length === 0) {
    toast('لم يتم تحديد أي اسم جديد للإضافة', 'warning');
    return;
  }

  if (!confirm('سيتم إضافة ' + toAdd.length + ' عضو جديد للنظام. هل تريد المتابعة؟')) return;

  var added = 0;
  var failed = 0;

  for (var i = 0; i < toAdd.length; i++) {
    try {
      await AdminMember.save(null, {
        name:     toAdd[i].name,
        family:   toAdd[i].family || '',
        phone:    toAdd[i].phone || '',
        idNum:    toAdd[i].idNum || '',
        joinDate: today(),
        status:   'نشط',
        notes:    'تم الاستيراد بواسطة الذكاء الاصطناعي',
      });
      added++;
    } catch (err) {
      console.error('فشل إضافة ' + toAdd[i].name + ':', err);
      failed++;
    }
  }

  // Show import report
  var reportHtml = '<div class="card" style="border-color:#86efac">';
  reportHtml += '<div class="card-header" style="background:#dcfce7"><div class="card-title" style="color:#166534">📋 تقرير الاستيراد</div></div>';
  reportHtml += '<div class="card-body">';
  reportHtml += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px">';
  reportHtml += statMini('✅', 'تمت الإضافة', added, '#166534');
  reportHtml += statMini('❌', 'فشل الإضافة', failed, '#991b1b');
  reportHtml += statMini('🔄', 'مكررة (تم تجاهلها)', (_importData.duplicates || []).length, '#854d0e');
  reportHtml += statMini('👥', 'موجودون مسبقاً', (_importData.confirmed || []).filter(function(x) { return x.existsInSystem; }).length, '#1e40af');
  reportHtml += '</div>';
  reportHtml += '<div style="display:flex;gap:12px"><button class="btn btn-outline" onclick="resetImport()">🔄 استيراد ملف جديد</button>';
  reportHtml += '<button class="btn btn-primary" onclick="showPage(\'members\',document.querySelector(\'.nav-item:nth-child(4)\'))">👥 عرض الأعضاء</button></div>';
  reportHtml += '</div></div>';

  document.getElementById('ai-import-results').innerHTML = reportHtml;

  toast('تم استيراد ' + added + ' عضو بنجاح', 'success');

  // Refresh members page
  if (typeof renderMembers === 'function') renderMembers();
  if (typeof updateSidebar === 'function') updateSidebar();
  if (typeof renderDashboard === 'function') renderDashboard();
}

function resetImport() {
  _importData = null;
  document.getElementById('upload-area').style.display = 'block';
  document.getElementById('ai-import-processing').style.display = 'none';
  document.getElementById('ai-import-results').style.display = 'none';
  var fileInput = document.getElementById('file-input-ai');
  if (fileInput) fileInput.value = '';
  setImportProgress('', 0);
}

function setImportProgress(text, pct) {
  var el = document.getElementById('ai-import-status-text');
  var bar = document.getElementById('ai-import-progress');
  if (el) el.textContent = text;
  if (bar) bar.style.width = pct + '%';
}

// ============================================================
// FEATURE 2: SMART REPORTS
// ============================================================

async function analyzeReport() {
  var text = document.getElementById('report-text-input').value.trim();
  var reportType = document.getElementById('report-type-select').value;

  if (!text) {
    toast('يرجى إدخال نص التقرير', 'warning');
    return;
  }

  if (text.length < 20) {
    toast('النص قصير جداً للتحليل. يرجى إدخال تقرير أكثر تفصيلاً', 'warning');
    return;
  }

  document.getElementById('ai-report-processing').style.display = 'block';
  document.getElementById('ai-report-results').style.display = 'none';
  document.getElementById('btn-analyze-report').disabled = true;

  try {
    var result = await AIAPI.analyzeReport({
      text: text,
      reportType: reportType,
    });

    _reportAnalysis = result.data;
    _reportAnalysis._originalText = text;
    _reportAnalysis._reportType = reportType;
    _reportAnalysis._reportDate = document.getElementById('report-date-input').value || _reportAnalysis.reportDate || null;

    document.getElementById('ai-report-processing').style.display = 'none';
    displayReportResults(result.data);

  } catch (err) {
    document.getElementById('ai-report-processing').style.display = 'none';
    toast('فشل التحليل: ' + err.message, 'error');
  } finally {
    document.getElementById('btn-analyze-report').disabled = false;
  }
}

function displayReportResults(data) {
  var container = document.getElementById('ai-report-results');
  container.style.display = 'block';

  var html = '';

  // Summaries
  html += '<div style="display:grid;grid-template-columns:1fr;gap:16px;margin-bottom:20px">';

  // Executive Summary
  html += '<div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1.5px solid #93c5fd;border-radius:12px;padding:16px">';
  html += '<div style="font-weight:700;color:#1e40af;margin-bottom:8px;font-size:14px">📋 الملخص التنفيذي</div>';
  html += '<div style="font-size:14px;color:#1e3a8a;line-height:1.8">' + esc(data.summaryExecutive || 'لا يوجد') + '</div></div>';

  // Extended Summary
  html += '<div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1.5px solid #86efac;border-radius:12px;padding:16px">';
  html += '<div style="font-weight:700;color:#166534;margin-bottom:8px;font-size:14px">📝 الملخص الموسّع</div>';
  html += '<div style="font-size:13px;color:#14532d;line-height:1.8">' + esc(data.summaryExtended || 'لا يوجد') + '</div></div>';

  // Financial Summary
  if (data.summaryFinancial) {
    html += '<div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1.5px solid #fde047;border-radius:12px;padding:16px">';
    html += '<div style="font-weight:700;color:#854d0e;margin-bottom:8px;font-size:14px">💰 الملخص المالي</div>';
    html += '<div style="font-size:14px;color:#78350f;line-height:1.8;font-weight:600">' + esc(data.summaryFinancial) + '</div></div>';
  }

  html += '</div>';

  // Financial Items
  var items = data.financialItems || [];
  if (items.length > 0) {
    html += '<div class="card" style="margin-bottom:20px;border-color:#fde047">';
    html += '<div class="card-header" style="background:#fef9c3">';
    html += '<div class="card-title" style="color:#854d0e">💵 البنود المالية المستخرجة (' + items.length + ')</div>';
    html += '<button class="btn btn-success btn-sm" onclick="syncReportBudgetItems()">💰 إضافة المحددة للميزانية</button>';
    html += '</div>';
    html += '<div class="card-body" style="padding:0">';
    html += '<table style="width:100%"><thead><tr>';
    html += '<th style="width:40px;text-align:center"><input type="checkbox" onchange="toggleAllBudgetItems(this)" checked></th>';
    html += '<th>النوع</th><th>الوصف</th><th>المبلغ (ريال)</th><th>الفئة</th></tr></thead><tbody>';

    items.forEach(function(item, idx) {
      var typeColor = item.type === 'إيراد' ? '#166534' : '#991b1b';
      var typeBg = item.type === 'إيراد' ? '#dcfce7' : '#fef2f2';
      html += '<tr data-budget-idx="' + idx + '">';
      html += '<td style="text-align:center"><input type="checkbox" class="budget-item-cb" data-idx="' + idx + '" checked></td>';
      html += '<td><span style="background:' + typeBg + ';color:' + typeColor + ';padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600">' + esc(item.type) + '</span></td>';
      html += '<td style="font-size:13px">' + esc(item.description) + '</td>';
      html += '<td><input type="number" class="form-control budget-item-amount" data-idx="' + idx + '" value="' + (item.amount || 0) + '" style="width:120px;font-size:13px;padding:4px 8px"></td>';
      html += '<td style="font-size:12px;color:var(--text-muted)">' + esc(item.category || '—') + '</td>';
      html += '</tr>';
    });

    html += '</tbody></table></div></div>';
  }

  // Decisions
  var decisions = data.decisions || [];
  if (decisions.length > 0) {
    html += '<div class="card" style="margin-bottom:20px">';
    html += '<div class="card-header"><div class="card-title">📌 القرارات والالتزامات (' + decisions.length + ')</div></div>';
    html += '<div class="card-body" style="padding:0">';
    decisions.forEach(function(d) {
      html += '<div style="display:flex;align-items:flex-start;gap:10px;padding:10px 16px;border-bottom:1px solid var(--border)">';
      html += '<span style="font-size:16px;flex-shrink:0">📌</span>';
      html += '<div><div style="font-weight:600;font-size:13px">' + esc(d.decision) + '</div>';
      if (d.responsible) html += '<div style="font-size:11px;color:var(--text-muted);margin-top:2px">المسؤول: ' + esc(d.responsible) + '</div>';
      if (d.deadline) html += '<div style="font-size:11px;color:#d97706;margin-top:2px">الموعد: ' + esc(d.deadline) + '</div>';
      html += '</div></div>';
    });
    html += '</div></div>';
  }

  // People
  var people = data.people || [];
  if (people.length > 0) {
    html += '<div class="card" style="margin-bottom:20px">';
    html += '<div class="card-header"><div class="card-title">👥 الأشخاص المذكورون (' + people.length + ')</div></div>';
    html += '<div class="card-body" style="padding:0;display:flex;flex-wrap:wrap;gap:0">';
    people.forEach(function(p) {
      html += '<div style="display:flex;align-items:center;gap:8px;padding:8px 16px;border-bottom:1px solid var(--border);min-width:250px">';
      html += '<div class="avatar" style="background:' + avColor(p.name) + ';width:28px;height:28px;font-size:11px">' + avInit(p.name) + '</div>';
      html += '<div><div style="font-weight:600;font-size:12px">' + esc(p.name) + '</div>';
      if (p.role) html += '<div style="font-size:11px;color:var(--text-muted)">' + esc(p.role) + '</div>';
      html += '</div></div>';
    });
    html += '</div></div>';
  }

  // Conflicts
  var conflicts = data.conflicts || [];
  if (conflicts.length > 0) {
    html += '<div class="card" style="margin-bottom:20px;border-color:#fca5a5">';
    html += '<div class="card-header" style="background:#fef2f2"><div class="card-title" style="color:#991b1b">⚠️ تعارضات مع الميزانية الحالية</div></div>';
    html += '<div class="card-body" style="padding:0">';
    conflicts.forEach(function(c) {
      html += '<div style="padding:10px 16px;border-bottom:1px solid #fecaca;font-size:13px">';
      html += '<span style="color:#991b1b;font-weight:600">' + esc(c.item) + '</span>';
      html += ' — ' + Number(c.amount).toLocaleString('ar-SA') + ' ريال';
      html += ' <span style="background:#dbeafe;color:#1e40af;font-size:10px;padding:2px 8px;border-radius:6px">' + c.status + '</span>';
      html += '</div>';
    });
    html += '</div></div>';
  }

  // Keywords
  var keywords = data.keywords || [];
  if (keywords.length > 0) {
    html += '<div style="margin-bottom:20px;display:flex;gap:8px;flex-wrap:wrap">';
    keywords.forEach(function(kw) {
      html += '<span style="background:var(--bg);border:1px solid var(--border);border-radius:20px;padding:4px 12px;font-size:12px;color:var(--text-muted)">#' + esc(kw) + '</span>';
    });
    html += '</div>';
  }

  // Action buttons
  html += '<div style="display:flex;gap:12px;flex-wrap:wrap;padding-top:16px;border-top:2px solid var(--border)">';
  html += '<button class="btn btn-primary" onclick="archiveReport()">🗄️ حفظ وأرشفة</button>';
  html += '<button class="btn btn-outline" onclick="clearReportForm()">🔄 تقرير جديد</button>';
  html += '</div>';

  container.innerHTML = html;
}

function toggleAllBudgetItems(masterCb) {
  document.querySelectorAll('.budget-item-cb').forEach(function(cb) { cb.checked = masterCb.checked; });
}

async function syncReportBudgetItems() {
  if (!_reportAnalysis || !_reportAnalysis.financialItems) return;

  // First save the report if not saved yet
  var reportId = _reportAnalysis._savedReportId;
  if (!reportId) {
    try {
      var saveResult = await AIAPI.saveReport({
        title: _reportAnalysis.title || 'تقرير بدون عنوان',
        reportType: _reportAnalysis._reportType || 'عام',
        originalText: _reportAnalysis._originalText || '',
        summaryExecutive: _reportAnalysis.summaryExecutive || '',
        summaryExtended: _reportAnalysis.summaryExtended || '',
        summaryFinancial: _reportAnalysis.summaryFinancial || '',
        people: _reportAnalysis.people || [],
        decisions: _reportAnalysis.decisions || [],
        keywords: _reportAnalysis.keywords || [],
        totals: _reportAnalysis.totals || { income: 0, expense: 0, remaining: 0 },
        financialItems: _reportAnalysis.financialItems || [],
        reportDate: _reportAnalysis._reportDate || null,
      });
      reportId = saveResult.id;
      _reportAnalysis._savedReportId = reportId;
    } catch (err) {
      toast('فشل حفظ التقرير: ' + err.message, 'error');
      return;
    }
  }

  // Get checked items
  var itemsToSync = [];
  document.querySelectorAll('.budget-item-cb').forEach(function(cb) {
    if (cb.checked) {
      var idx = parseInt(cb.dataset.idx);
      var item = _reportAnalysis.financialItems[idx];
      var amountEl = document.querySelector('.budget-item-amount[data-idx="' + idx + '"]');
      var amount = amountEl ? parseFloat(amountEl.value) : item.amount;
      itemsToSync.push({
        id: item._savedId || ('temp-' + idx),
        status: 'مؤكد',
        amount: amount,
        category: item.category,
        description: item.description,
      });
    }
  });

  if (itemsToSync.length === 0) {
    toast('لم يتم تحديد أي بند', 'warning');
    return;
  }

  if (!confirm('سيتم إضافة ' + itemsToSync.length + ' بند مالي للميزانية. هل تريد المتابعة؟')) return;

  try {
    var result = await AIAPI.budgetItems({ items: itemsToSync });
    toast('تم مزامنة ' + result.synced + ' بند مع الميزانية', 'success');

    // Reload transactions
    var txRes = await TransactionsAPI.getAll();
    DB.transactions = (txRes.data || []).map(normalizeTx);
    if (typeof renderBudget === 'function') renderBudget();
    if (typeof renderDashboard === 'function') renderDashboard();
    if (typeof updateSidebar === 'function') updateSidebar();
  } catch (err) {
    toast('فشل المزامنة: ' + err.message, 'error');
  }
}

async function archiveReport() {
  if (!_reportAnalysis) return;

  try {
    var result = await AIAPI.saveReport({
      title: _reportAnalysis.title || 'تقرير بدون عنوان',
      reportType: _reportAnalysis._reportType || 'عام',
      originalText: _reportAnalysis._originalText || '',
      summaryExecutive: _reportAnalysis.summaryExecutive || '',
      summaryExtended: _reportAnalysis.summaryExtended || '',
      summaryFinancial: _reportAnalysis.summaryFinancial || '',
      people: _reportAnalysis.people || [],
      decisions: _reportAnalysis.decisions || [],
      keywords: _reportAnalysis.keywords || [],
      totals: _reportAnalysis.totals || { income: 0, expense: 0, remaining: 0 },
      financialItems: _reportAnalysis.financialItems || [],
      reportDate: _reportAnalysis._reportDate || null,
    });

    _reportAnalysis._savedReportId = result.id;
    toast('تم حفظ التقرير وأرشفته بنجاح', 'success');
  } catch (err) {
    toast('فشل الحفظ: ' + err.message, 'error');
  }
}

function clearReportForm() {
  _reportAnalysis = null;
  document.getElementById('report-text-input').value = '';
  document.getElementById('report-date-input').value = '';
  document.getElementById('report-type-select').value = 'عام';
  document.getElementById('ai-report-results').style.display = 'none';
  document.getElementById('ai-report-processing').style.display = 'none';
}

// ============================================================
// ARCHIVE & QUARTERLY
// ============================================================

async function loadArchivedReports() {
  var container = document.getElementById('archived-reports-list');
  container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted)"><div style="animation:spin 1s linear infinite;display:inline-block;font-size:24px">⏳</div><div style="margin-top:8px">جاري التحميل...</div></div>';

  try {
    var result = await AIAPI.getReports();
    var reports = result.data || [];

    if (reports.length === 0) {
      container.innerHTML = '<div class="empty-state"><div class="empty-icon">🗄️</div><p>لا توجد تقارير مؤرشفة بعد</p></div>';
      return;
    }

    var html = '';
    reports.forEach(function(r) {
      var typeIcons = { 'اجتماع': '🤝', 'فعالية': '🎉', 'مالي': '💰', 'عام': '📄' };
      var icon = typeIcons[r.report_type] || '📄';
      var income = parseFloat(r.total_income || 0);
      var expense = parseFloat(r.total_expense || 0);

      html += '<div style="display:flex;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid var(--border)">';
      html += '<div style="width:40px;height:40px;background:var(--bg);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">' + icon + '</div>';
      html += '<div style="flex:1;min-width:0">';
      html += '<div style="font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + esc(r.title || 'بدون عنوان') + '</div>';
      html += '<div style="font-size:11px;color:var(--text-muted);margin-top:2px;display:flex;gap:12px;flex-wrap:wrap">';
      html += '<span>' + esc(r.report_type) + '</span>';
      if (r.report_date) html += '<span>📅 ' + r.report_date + '</span>';
      html += '<span>' + esc(r.summary_executive || '').substring(0, 60) + (r.summary_executive && r.summary_executive.length > 60 ? '...' : '') + '</span>';
      html += '</div></div>';
      if (income > 0 || expense > 0) {
        html += '<div style="text-align:left;flex-shrink:0">';
        if (income > 0) html += '<div style="font-size:12px;color:#166534;font-weight:600">+' + income.toLocaleString('ar-SA') + '</div>';
        if (expense > 0) html += '<div style="font-size:12px;color:#991b1b;font-weight:600">-' + expense.toLocaleString('ar-SA') + '</div>';
        html += '</div>';
      }
      html += '<button class="btn btn-outline btn-xs" onclick="viewArchivedReport(\'' + r.id + '\')">عرض</button>';
      html += '<button class="btn btn-danger btn-xs" onclick="deleteArchivedReport(\'' + r.id + '\')">حذف</button>';
      html += '</div>';
    });

    container.innerHTML = html;
  } catch (err) {
    container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--danger)">فشل تحميل التقارير: ' + esc(err.message) + '</div>';
  }
}

async function viewArchivedReport(id) {
  try {
    var result = await AIAPI.getReport(id);
    var report = result.data;

    // Switch to reports tab and display the report
    switchAITab('reports', document.querySelectorAll('.ai-tab')[1]);
    document.getElementById('report-text-input').value = report.original_text || '';
    document.getElementById('report-type-select').value = report.report_type || 'عام';
    if (report.report_date) document.getElementById('report-date-input').value = report.report_date;

    // Parse JSON fields if needed
    var parsed = {
      title: report.title,
      summaryExecutive: report.summary_executive,
      summaryExtended: report.summary_extended,
      summaryFinancial: report.summary_financial,
      financialItems: (report.items || []).map(function(i) {
        return { type: i.item_type, description: i.description, amount: parseFloat(i.amount), category: i.category, date: i.item_date, _savedId: i.id };
      }),
      decisions: report.extracted_decisions || [],
      people: report.extracted_people || [],
      keywords: report.keywords || [],
      totals: { income: parseFloat(report.total_income), expense: parseFloat(report.total_expense), remaining: parseFloat(report.total_remaining) },
      conflicts: [],
      _savedReportId: report.id,
    };

    _reportAnalysis = parsed;
    _reportAnalysis._originalText = report.original_text;
    _reportAnalysis._reportType = report.report_type;
    _reportAnalysis._reportDate = report.report_date;

    displayReportResults(parsed);
  } catch (err) {
    toast('فشل تحميل التقرير: ' + err.message, 'error');
  }
}

async function deleteArchivedReport(id) {
  if (!confirm('هل تريد حذف هذا التقرير نهائياً؟')) return;
  try {
    await AIAPI.deleteReport(id);
    toast('تم حذف التقرير');
    loadArchivedReports();
  } catch (err) {
    toast('فشل الحذف: ' + err.message, 'error');
  }
}

async function searchArchivedReports() {
  var query = document.getElementById('archive-search-input').value.trim();
  if (!query) {
    loadArchivedReports();
    return;
  }

  var container = document.getElementById('archived-reports-list');
  container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted)">جاري البحث...</div>';

  try {
    var result = await AIAPI.searchReports(query);
    var reports = result.data || [];

    if (reports.length === 0) {
      container.innerHTML = '<div class="empty-state"><div class="empty-icon">🔍</div><p>لا توجد نتائج لـ "' + esc(query) + '"</p></div>';
      return;
    }

    // Reuse the same rendering
    var html = '<div style="font-size:12px;color:var(--text-muted);margin-bottom:12px">نتائج البحث عن "' + esc(query) + '": ' + reports.length + ' تقرير</div>';
    reports.forEach(function(r) {
      var typeIcons = { 'اجتماع': '🤝', 'فعالية': '🎉', 'مالي': '💰', 'عام': '📄' };
      html += '<div style="display:flex;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid var(--border)">';
      html += '<div style="width:36px;height:36px;background:var(--bg);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px">' + (typeIcons[r.report_type] || '📄') + '</div>';
      html += '<div style="flex:1"><div style="font-weight:600;font-size:13px">' + esc(r.title || 'بدون عنوان') + '</div>';
      html += '<div style="font-size:11px;color:var(--text-muted)">' + esc(r.summary_executive || '').substring(0, 80) + '</div></div>';
      html += '<button class="btn btn-outline btn-xs" onclick="viewArchivedReport(\'' + r.id + '\')">عرض</button>';
      html += '</div>';
    });

    container.innerHTML = html;
  } catch (err) {
    container.innerHTML = '<div style="color:var(--danger);padding:20px;text-align:center">فشل البحث: ' + esc(err.message) + '</div>';
  }
}

async function generateQuarterlyDraft() {
  var currentMonth = new Date().getMonth() + 1;
  var quarter = Math.ceil(currentMonth / 3);
  var year = new Date().getFullYear();

  var container = document.getElementById('quarterly-draft-container');
  container.style.display = 'block';
  container.innerHTML = '<div class="card"><div class="card-body" style="text-align:center;padding:30px"><div style="animation:spin 1s linear infinite;display:inline-block;font-size:32px">⏳</div><div style="margin-top:10px;color:var(--text-muted)">جاري إعداد المسودة الربعية...</div></div></div>';

  try {
    var result = await AIAPI.quarterlyDraft(quarter, year);

    var html = '<div class="card" style="border-color:#86efac">';
    html += '<div class="card-header" style="background:#dcfce7"><div class="card-title" style="color:#166534">📊 مسودة التقرير الربعي — الربع ' + result.quarter + ' لعام ' + result.year + '</div></div>';
    html += '<div class="card-body">';

    // Period
    html += '<div style="font-size:13px;color:var(--text-muted);margin-bottom:16px">الفترة: ' + result.period + '</div>';

    // Summary stats
    html += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px">';
    html += statMini('💰', 'إجمالي الإيرادات', Number(result.totalIncome).toLocaleString('ar-SA') + ' ريال', '#166534');
    html += statMini('💸', 'إجمالي المصروفات', Number(result.totalExpense).toLocaleString('ar-SA') + ' ريال', '#991b1b');
    html += statMini('📊', 'صافي الرصيد', Number(result.netBalance).toLocaleString('ar-SA') + ' ريال', result.netBalance >= 0 ? '#166534' : '#991b1b');
    html += statMini('📄', 'عدد التقارير', result.reportCount, '#1e40af');
    html += '</div>';

    // By Category
    var cats = result.byCategory || {};
    var catKeys = Object.keys(cats);
    if (catKeys.length > 0) {
      html += '<div class="card" style="margin-bottom:16px"><div class="card-header"><div class="card-title">تفصيل حسب الفئة</div></div>';
      html += '<div class="card-body" style="padding:0"><table style="width:100%"><thead><tr><th>الفئة</th><th>إيرادات</th><th>مصروفات</th><th>الصافي</th></tr></thead><tbody>';
      catKeys.forEach(function(cat) {
        var c = cats[cat];
        var net = (c.income || 0) - (c.expense || 0);
        html += '<tr><td style="font-weight:600">' + esc(cat) + '</td>';
        html += '<td style="color:#166534">' + Number(c.income || 0).toLocaleString('ar-SA') + '</td>';
        html += '<td style="color:#991b1b">' + Number(c.expense || 0).toLocaleString('ar-SA') + '</td>';
        html += '<td style="font-weight:700;color:' + (net >= 0 ? '#166534' : '#991b1b') + '">' + Number(net).toLocaleString('ar-SA') + '</td></tr>';
      });
      html += '</tbody></table></div></div>';
    }

    html += '</div></div>';
    container.innerHTML = html;

  } catch (err) {
    container.innerHTML = '<div class="card"><div class="card-body" style="text-align:center;padding:20px;color:var(--danger)">فشل إعداد المسودة: ' + esc(err.message) + '</div></div>';
  }
}

// ============================================================
// HELPERS
// ============================================================

function statMini(icon, label, value, color) {
  return '<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:12px;text-align:center">' +
    '<div style="font-size:20px;margin-bottom:4px">' + icon + '</div>' +
    '<div style="font-size:16px;font-weight:800;color:' + color + '">' + value + '</div>' +
    '<div style="font-size:11px;color:var(--text-muted)">' + label + '</div></div>';
}

function esc(str) {
  if (!str) return '';
  var div = document.createElement('div');
  div.textContent = String(str);
  return div.innerHTML;
}

function today() {
  return new Date().toISOString().split('T')[0];
}

// ── Drag and Drop support ──
(function() {
  document.addEventListener('DOMContentLoaded', function() {
    var uploadArea = document.getElementById('upload-area');
    if (!uploadArea) return;

    ['dragenter', 'dragover'].forEach(function(evt) {
      uploadArea.addEventListener(evt, function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.style.borderColor = 'var(--green)';
        uploadArea.style.backgroundColor = '#f0fdf4';
      });
    });

    ['dragleave', 'drop'].forEach(function(evt) {
      uploadArea.addEventListener(evt, function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.style.borderColor = 'var(--border)';
        uploadArea.style.backgroundColor = '';
      });
    });

    uploadArea.addEventListener('drop', function(e) {
      var files = e.dataTransfer.files;
      if (files.length > 0) {
        var fileInput = document.getElementById('file-input-ai');
        if (fileInput) {
          // Create a new DataTransfer to set files
          var dt = new DataTransfer();
          dt.items.add(files[0]);
          fileInput.files = dt.files;
          handleAIFileUpload({ target: { files: files } });
        }
      }
    });
  });
})();
