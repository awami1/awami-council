// ═══════════════════════════════════════════════════════════════
// admin-members-ui.js — Members page UI enhancements
// Stats bar, compact table, sorting, duplicates, bulk actions
// ═══════════════════════════════════════════════════════════════

// =================== GLOBAL STATE ===================
var _memberSort = { field: 'name', dir: 'asc' };
var _showDuplicates = false;
var _selectedMembers = new Set();

// =================== STATS BAR ===================
function updateMembersStats(filteredList) {
  var total = filteredList.length;
  var active = 0, lapsed = 0, inactive = 0;
  for (var i = 0; i < total; i++) {
    var s = filteredList[i].status;
    if (s === 'مشترك') active++;
    else if (s === 'منقطع') lapsed++;
    else inactive++;
  }
  var el;
  el = document.getElementById('stat-total'); if (el) el.textContent = total;
  el = document.getElementById('stat-active'); if (el) el.textContent = active;
  el = document.getElementById('stat-lapsed'); if (el) el.textContent = lapsed;
  el = document.getElementById('stat-inactive'); if (el) el.textContent = inactive;
}

function quickFilter(status) {
  var flt = document.getElementById('m-flt-status');
  if (flt) flt.value = status;
  _pageState.members = 1;
  renderMembers();
}

// =================== VIEW TOGGLE ===================
function getMembersViewMode() {
  var saved = localStorage.getItem('members-view-mode');
  if (saved === 'cards' || saved === 'table') return saved;
  return window.innerWidth >= 768 ? 'table' : 'cards';
}

function setMembersView(mode) {
  localStorage.setItem('members-view-mode', mode);
  applyMembersView(mode);
  renderMembers();
}

function applyMembersView(mode) {
  var cardsView = document.getElementById('members-cards-view');
  var compactView = document.getElementById('members-compact');
  var btnCards = document.getElementById('view-cards');
  var btnTable = document.getElementById('view-table');
  if (!cardsView || !compactView) return;

  if (mode === 'table' && window.innerWidth >= 768) {
    cardsView.style.display = 'none';
    compactView.style.display = '';
    if (btnCards) btnCards.classList.remove('active');
    if (btnTable) btnTable.classList.add('active');
  } else {
    cardsView.style.display = '';
    compactView.style.display = 'none';
    if (btnCards) btnCards.classList.add('active');
    if (btnTable) btnTable.classList.remove('active');
  }
}

// =================== SORTING ===================
function sortMembers(field) {
  if (_memberSort.field === field) {
    if (_memberSort.dir === 'asc') _memberSort.dir = 'desc';
    else if (_memberSort.dir === 'desc') { _memberSort.field = 'name'; _memberSort.dir = 'asc'; }
  } else {
    _memberSort.field = field;
    _memberSort.dir = 'asc';
  }
  renderMembers();
}

function applySortToList(list) {
  var field = _memberSort.field;
  var dir = _memberSort.dir;
  list.sort(function(a, b) {
    if (field === 'name') {
      var cmp = (a.name || '').localeCompare(b.name || '', 'ar');
      return dir === 'asc' ? cmp : -cmp;
    }
    if (field === 'joinDate') {
      var cmp = (a.joinDate || '').localeCompare(b.joinDate || '');
      return dir === 'asc' ? cmp : -cmp;
    }
    if (field === 'status') {
      var order = {'مشترك': 0, 'منقطع': 1, 'غير مشترك': 2};
      var diff = (order[a.status] !== undefined ? order[a.status] : 9) - (order[b.status] !== undefined ? order[b.status] : 9);
      return dir === 'asc' ? diff : -diff;
    }
    return 0;
  });
}

function updateSortArrows() {
  // Clear all arrows
  document.querySelectorAll('.sort-arrow').forEach(function(el) { el.textContent = ''; });
  // Set active arrow
  var arrow = _memberSort.dir === 'asc' ? '▲' : '▼';
  var ids = [
    'sort-arrow-' + _memberSort.field,
    'sort-arrow-' + _memberSort.field + '-cards'
  ];
  ids.forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.textContent = arrow;
  });
}

// =================== DUPLICATES ===================
function findDuplicates(members) {
  var map = {};
  for (var i = 0; i < members.length; i++) {
    var m = members[i];
    var key = (m.name + ' ' + (m.family || '')).trim().replace(/\s+/g, ' ');
    if (!map[key]) map[key] = [];
    map[key].push(m);
  }
  var groups = [];
  var totalDuplicates = 0;
  for (var k in map) {
    if (map[k].length >= 2) {
      groups.push(map[k]);
      totalDuplicates += map[k].length;
    }
  }
  return { groups: groups, totalDuplicates: totalDuplicates };
}

function toggleDuplicatesFilter() {
  _showDuplicates = !_showDuplicates;
  var btn = document.getElementById('btn-duplicates');
  if (btn) {
    if (_showDuplicates) {
      btn.style.background = 'var(--green)';
      btn.style.color = '#fff';
      btn.style.borderColor = 'var(--green)';
    } else {
      btn.style.background = '';
      btn.style.color = '';
      btn.style.borderColor = '';
    }
  }
  _pageState.members = 1;
  renderMembers();
}

// =================== SELECTION & BULK ===================
function toggleMemberSelect(id, checkbox) {
  if (checkbox.checked) {
    _selectedMembers.add(id);
  } else {
    _selectedMembers.delete(id);
  }
  updateBulkBar();
}

function toggleSelectAll(masterCheckbox) {
  // Get visible member IDs on current page
  var checkboxes = document.querySelectorAll('input.member-check');
  checkboxes.forEach(function(cb) {
    cb.checked = masterCheckbox.checked;
    var id = cb.getAttribute('data-id');
    if (masterCheckbox.checked) {
      _selectedMembers.add(id);
    } else {
      _selectedMembers.delete(id);
    }
  });
  updateBulkBar();
}

function clearSelection() {
  _selectedMembers.clear();
  document.querySelectorAll('input.member-check').forEach(function(cb) { cb.checked = false; });
  var sa1 = document.getElementById('select-all-members');
  var sa2 = document.getElementById('select-all-cards');
  if (sa1) sa1.checked = false;
  if (sa2) sa2.checked = false;
  updateBulkBar();
}

function updateBulkBar() {
  var bar = document.getElementById('bulk-action-bar');
  var count = document.getElementById('bulk-count');
  if (!bar) return;
  if (_selectedMembers.size > 0) {
    bar.style.display = 'flex';
    if (count) count.textContent = 'تم تحديد ' + _selectedMembers.size + ' عضو';
  } else {
    bar.style.display = 'none';
  }
}

function bulkDelete() {
  if (_selectedMembers.size === 0) return;
  var members = State.getMembers();
  var names = [];
  _selectedMembers.forEach(function(id) {
    var m = members.find(function(x) { return x.id === id; });
    if (m) names.push(m.name + ' ' + (m.family || ''));
  });

  var warning = document.getElementById('bulk-delete-warning');
  var namesList = document.getElementById('bulk-delete-names');
  if (warning) warning.textContent = 'سيتم حذف ' + _selectedMembers.size + ' عضو نهائياً. هذا الإجراء لا يمكن التراجع عنه.';
  if (namesList) {
    var display = names.slice(0, 10).map(function(n) { return '• ' + esc(n); }).join('<br>');
    if (names.length > 10) display += '<br><strong>... و ' + (names.length - 10) + ' آخرين</strong>';
    namesList.innerHTML = display;
  }
  openModal('modal-bulk-delete');
}

async function confirmBulkDelete() {
  if (_selectedMembers.size === 0) return;
  var ids = Array.from(_selectedMembers);
  closeModal('modal-bulk-delete');

  try {
    var res = await apiFetch('/api/members.php?action=bulk_delete', {
      method: 'DELETE',
      body: JSON.stringify({ ids: ids })
    });
    toast('تم حذف ' + (res.deleted || 0) + ' عضو');
    // Remove from local state
    ids.forEach(function(id) {
      var idx = DB.members.findIndex(function(m) { return m.id === id; });
      if (idx >= 0) DB.members.splice(idx, 1);
      DB.payments = DB.payments.filter(function(p) { return p.memberId !== id; });
    });
    clearSelection();
    renderMembers();
    updateSidebar();
  } catch (e) {
    toast(e.message || 'خطأ في الحذف', 'error');
  }
}

function openBulkStatusModal() {
  if (_selectedMembers.size === 0) return;
  var el = document.getElementById('bulk-status-count');
  if (el) el.textContent = _selectedMembers.size;
  openModal('modal-bulk-status');
}

async function confirmBulkStatus() {
  if (_selectedMembers.size === 0) return;
  var newStatus = document.getElementById('bulk-new-status').value;
  var ids = Array.from(_selectedMembers);
  closeModal('modal-bulk-status');

  try {
    var res = await apiFetch('/api/members.php?action=bulk_status', {
      method: 'PUT',
      body: JSON.stringify({ ids: ids, status: newStatus })
    });
    toast('تم تحديث حالة ' + (res.updated || 0) + ' عضو');
    // Update local state
    ids.forEach(function(id) {
      var m = DB.members.find(function(x) { return x.id === id; });
      if (m) m.status = newStatus;
    });
    clearSelection();
    renderMembers();
  } catch (e) {
    toast(e.message || 'خطأ في التحديث', 'error');
  }
}

// =================== ACCORDION (Compact View) ===================
function toggleAccordion(row, memberId) {
  var existing = document.getElementById('accordion-' + memberId);
  if (existing) {
    existing.remove();
    row.classList.remove('expanded');
    return;
  }

  // Close any open accordion
  document.querySelectorAll('.accordion-content').forEach(function(el) { el.remove(); });
  document.querySelectorAll('.compact-table tr.expanded').forEach(function(el) { el.classList.remove('expanded'); });

  row.classList.add('expanded');
  var m = State.getMembers().find(function(x) { return x.id === memberId; });
  if (!m) return;

  var comms = typeof memberCommittees === 'function' ? memberCommittees(memberId) : '—';
  var accBtns = typeof memberAuthButtons === 'function' ? memberAuthButtons(memberId) : '';

  var tr = document.createElement('tr');
  tr.id = 'accordion-' + memberId;
  tr.className = 'accordion-content open';
  tr.innerHTML = '<td colspan="8" style="padding:14px 20px;background:var(--bg)">' +
    '<div class="accordion-details">' +
    '<div><dt>تاريخ الانضمام</dt><dd>' + (m.joinDate || '—') + '</dd></div>' +
    '<div><dt>الملاحظات</dt><dd>' + esc(m.notes || '—') + '</dd></div>' +
    '<div><dt>اللجان</dt><dd>' + comms + '</dd></div>' +
    '<div><dt>العائلة</dt><dd>' + esc(m.family || '—') + '</dd></div>' +
    '</div>' +
    '<div class="accordion-actions">' +
    '<button class="btn btn-outline btn-sm" onclick="editMember(\'' + m.id + '\')">✏️ تعديل</button>' +
    '<button class="btn btn-danger btn-sm" onclick="deleteMember(\'' + m.id + '\')">🗑️ حذف</button>' +
    accBtns +
    '</div></td>';

  row.parentNode.insertBefore(tr, row.nextSibling);
}

// =================== RENDER COMPACT TABLE ===================
function renderCompactTable(list, pg, startIdx) {
  var p = typeof curPeriod === 'function' ? curPeriod() : null;
  var tbody = document.getElementById('compact-tbody');
  if (!tbody) return;

  // Build duplicate groups map for coloring
  var dupMap = {};
  if (_showDuplicates) {
    var dupResult = findDuplicates(State.getMembers());
    var groupIdx = 0;
    dupResult.groups.forEach(function(group) {
      group.forEach(function(m) { dupMap[m.id] = groupIdx; });
      groupIdx++;
    });
  }

  tbody.innerHTML = pg.data.map(function(m, i) {
    var pay = p ? State.getPayments().find(function(x) { return x.memberId === m.id && x.periodId === p.id; }) : null;
    var pb = !p ? '<span class="badge badge-gray">لا دورة</span>' :
      pay && pay.status === 'مدفوع' ? '<span class="badge badge-success">✅ مدفوع</span>' :
      '<span class="badge badge-warning">⏳ لم يدفع</span>';
    var sb = m.status === 'مشترك' ? '<span class="badge badge-success">مشترك</span>' :
      m.status === 'منقطع' ? '<span class="badge badge-warning">منقطع</span>' :
      '<span class="badge badge-gray">غير مشترك</span>';
    var acc = typeof getMemberAccount === 'function' ? getMemberAccount(m.id) : null;
    var awmId = acc ? acc.awm_id : '—';
    var accBadge = typeof memberAccountBadge === 'function' ? memberAccountBadge(m.id) : '';
    var commsCount = typeof memberCommittees === 'function' ? memberCommittees(m.id) : '—';
    var dupClass = '';
    if (_showDuplicates && dupMap[m.id] !== undefined) {
      dupClass = dupMap[m.id] % 2 === 0 ? ' dup-group-a' : ' dup-group-b';
    }
    var checked = _selectedMembers.has(m.id) ? 'checked' : '';

    return '<tr class="' + dupClass + '" style="cursor:pointer">' +
      '<td class="th-check" onclick="event.stopPropagation()"><input type="checkbox" class="member-check" data-id="' + m.id + '" ' + checked + ' onchange="toggleMemberSelect(\'' + m.id + '\', this)"></td>' +
      '<td onclick="toggleAccordion(this.parentNode, \'' + m.id + '\')"><div style="display:flex;align-items:center;gap:8px"><span style="color:var(--text-muted);font-size:11px;min-width:20px">' + (startIdx + i + 1) + '</span><div class="avatar" style="background:' + avColor(m.name) + '">' + avInit(m.name) + '</div><div><div style="font-weight:600;font-size:13px">' + esc(m.name) + '</div><div style="font-size:11px;color:var(--text-muted)">' + esc(m.family || '') + '</div></div></div></td>' +
      '<td style="font-size:12px;font-weight:600;color:var(--green-dark);font-family:monospace">' + awmId + '</td>' +
      '<td>' + esc(m.phone || '—') + '</td>' +
      '<td style="font-size:11px">' + commsCount + '</td>' +
      '<td>' + sb + '</td>' +
      '<td>' + pb + '</td>' +
      '<td>' + accBadge + '</td>' +
      '</tr>';
  }).join('');
}

// =================== ENHANCED renderMembers ===================

function renderMembers(page) {
  var s = document.getElementById('m-search').value;
  var sf = document.getElementById('m-flt-status').value;
  var af = document.getElementById('m-flt-account') ? document.getElementById('m-flt-account').value : '';
  var list = State.getMembers().slice(); // copy

  // Search filter
  if (s) list = list.filter(function(m) {
    if (m.name.includes(s) || (m.phone && m.phone.includes(s))) return true;
    var acc = typeof getMemberAccount === 'function' ? getMemberAccount(m.id) : null;
    return acc && acc.awm_id && acc.awm_id.toLowerCase().includes(s.toLowerCase());
  });

  // Status filter
  if (sf) list = list.filter(function(m) { return m.status === sf; });

  // Account filter
  if (af && typeof getMemberAccount === 'function') {
    list = list.filter(function(m) {
      var acc = getMemberAccount(m.id);
      if (af === 'none') return !acc;
      if (af === 'active') return acc && acc.is_active;
      if (af === 'inactive') return acc && !acc.is_active;
      return true;
    });
  }

  // Update duplicates count (always, regardless of filter)
  var dupResult = findDuplicates(State.getMembers());
  var dupCountEl = document.getElementById('dup-count');
  if (dupCountEl) dupCountEl.textContent = dupResult.totalDuplicates;

  // Duplicates filter
  if (_showDuplicates) {
    var dupIds = {};
    dupResult.groups.forEach(function(group) {
      group.forEach(function(m) { dupIds[m.id] = true; });
    });
    list = list.filter(function(m) { return dupIds[m.id]; });
  }

  // Update stats (always from all members, not filtered list)
  updateMembersStats(State.getMembers());

  // Apply sorting
  applySortToList(list);
  updateSortArrows();

  var viewMode = getMembersViewMode();
  applyMembersView(viewMode);

  var p = typeof curPeriod === 'function' ? curPeriod() : null;

  // Check if empty
  if (!list.length) {
    var tbody = document.getElementById('members-tbody');
    var ctbody = document.getElementById('compact-tbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="11"><div class="empty-state"><div class="empty-icon">👥</div><p>لا يوجد أعضاء</p></div></td></tr>';
    if (ctbody) ctbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><div class="empty-icon">👥</div><p>لا يوجد أعضاء</p></div></td></tr>';
    var mp = document.getElementById('members-pagination');
    if (mp) mp.innerHTML = '';
    var countEl = document.getElementById('members-count');
    if (countEl) countEl.textContent = '0 عضو';
    return;
  }

  var pg = paginate(list, 'members', page, 25);
  var startIdx = (pg.page - 1) * 25;

  if (viewMode === 'table' && window.innerWidth >= 768) {
    // Render compact table
    renderCompactTable(list, pg, startIdx);
    // Clear cards view
    var tbody = document.getElementById('members-tbody');
    if (tbody) tbody.innerHTML = '';
  } else {
    // Render cards view (original)
    renderCardsView(list, pg, startIdx, p);
    // Clear compact view
    var ctbody = document.getElementById('compact-tbody');
    if (ctbody) ctbody.innerHTML = '';
  }

  // Update count
  var totalAll = State.getMembers().length;
  var countEl = document.getElementById('members-count');
  if (pg.total < totalAll) countEl.textContent = 'عرض ' + pg.total + ' من أصل ' + totalAll + ' عضو';
  else countEl.textContent = pg.total + ' عضو';

  // Pagination
  var paginEl = document.getElementById('members-pagination');
  if (paginEl) paginEl.innerHTML = renderPaginationHTML(pg, 'renderMembers');
}

function renderCardsView(list, pg, startIdx, p) {
  var tbody = document.getElementById('members-tbody');
  if (!tbody) return;

  // Build duplicate map for badge
  var dupMap = {};
  if (_showDuplicates) {
    var dupResult = findDuplicates(State.getMembers());
    var groupIdx = 0;
    dupResult.groups.forEach(function(group) {
      var count = group.length;
      group.forEach(function(m) { dupMap[m.id] = { groupIdx: groupIdx, count: count }; });
      groupIdx++;
    });
  }

  tbody.innerHTML = pg.data.map(function(m, i) {
    var pay = p ? State.getPayments().find(function(x) { return x.memberId === m.id && x.periodId === p.id; }) : null;
    var pb = !p ? '<span class="badge badge-gray">لا دورة</span>' :
      pay && pay.status === 'مدفوع' ? '<span class="badge badge-success">✅ مدفوع</span>' :
      '<span class="badge badge-warning">⏳ لم يدفع</span>';
    var sb = m.status === 'مشترك' ? '<span class="badge badge-success">مشترك</span>' :
      m.status === 'منقطع' ? '<span class="badge badge-warning">منقطع</span>' :
      '<span class="badge badge-gray">غير مشترك</span>';
    var acc = typeof getMemberAccount === 'function' ? getMemberAccount(m.id) : null;
    var awmId = acc ? acc.awm_id : '—';
    var accBadge = typeof memberAccountBadge === 'function' ? memberAccountBadge(m.id) : '';
    var comms = memberCommittees(m.id);
    var checked = _selectedMembers.has(m.id) ? 'checked' : '';
    var dupBadge = '';
    if (_showDuplicates && dupMap[m.id]) {
      dupBadge = ' <span class="dup-badge">🔁 مكرر (' + dupMap[m.id].count + ' نسخ)</span>';
    }

    return '<tr><td data-label="" style="width:30px"><input type="checkbox" class="member-check" data-id="' + m.id + '" ' + checked + ' onchange="toggleMemberSelect(\'' + m.id + '\', this)"></td>' +
    '<td data-label="#" style="color:var(--text-muted);font-size:11px">' + (startIdx + i + 1) + '</td>' +
    '<td data-label="العضو"><div style="display:flex;align-items:center;gap:8px"><div class="avatar" style="background:' + avColor(m.name) + '">' + avInit(m.name) + '</div><div><div style="font-weight:600">' + esc(m.name) + dupBadge + '</div><div style="font-size:11px;color:var(--text-muted)">' + esc(m.family || '') + '</div></div></div></td>' +
    '<td data-label="AWM-ID" style="font-size:12px;font-weight:600;color:var(--green-dark);font-family:monospace">' + awmId + '</td>' +
    '<td data-label="الجوال">' + esc(m.phone || '—') + '</td>' +
    '<td data-label="اللجان" class="committee-cell" title="' + comms.replace(/<[^>]*>/g, '') + '">' + comms + '</td>' +
    '<td data-label="الانضمام" style="font-size:11px">' + (m.joinDate || '—') + '</td><td data-label="الحالة">' + sb + '</td><td data-label="الحساب">' + accBadge + '</td><td data-label="الدفع">' + pb + '</td>' +
    '<td data-label="إجراءات"><div class="actions-cell">' +
      '<button class="btn btn-outline btn-xs" onclick="editMember(\'' + m.id + '\')" title="تعديل">✏️</button>' +
      (p ? '<button class="btn btn-accent btn-xs" onclick="openPayModal(\'' + m.id + '\')" title="تسجيل دفع">💳</button>' : '') +
      (typeof memberAuthButtons === 'function' ? memberAuthButtons(m.id) : '') +
      '<button class="btn btn-danger btn-xs" onclick="deleteMember(\'' + m.id + '\')" title="حذف">🗑️</button>' +
    '</div></td></tr>';
  }).join('');
}

function resetMembersFilters() {
  document.getElementById('m-search').value = '';
  document.getElementById('m-flt-status').value = '';
  var accFlt = document.getElementById('m-flt-account');
  if (accFlt) accFlt.value = '';
  _showDuplicates = false;
  var btn = document.getElementById('btn-duplicates');
  if (btn) { btn.style.background = ''; btn.style.color = ''; btn.style.borderColor = ''; }
  _pageState.members = 1;
  renderMembers();
}

var debouncedRenderMembers = debounce(function() { _pageState.members = 1; renderMembers(); }, 200);
