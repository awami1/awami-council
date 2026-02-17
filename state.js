/**
 * core/state.js
 * =============
 * Al Awami Family Council — State Module
 *
 * Owns the DB object (private) and exposes a clean API for every
 * DB mutation. Contains zero DOM references, zero localStorage calls,
 * zero toast/confirm2/render calls.
 *
 * Usage (main file):
 *   State.init(persistedData, COMMITTEES_DATA, COUNCIL_POSITIONS);
 *   const DB = State.getDB(); // backward-compat alias
 */

const State = (function () {
  'use strict';

  // ===========================================================
  // PRIVATE HELPERS
  // (needed internally by mutation functions — not exposed)
  // ===========================================================

  function uid() {
    return Date.now().toString(36) + Math.random().toString(36).slice(2);
  }

  function today() {
    return new Date().toISOString().split('T')[0];
  }

  function curPeriod() {
    return DB.periods[DB.periods.length - 1] || null;
  }

  // ===========================================================
  // DB — PRIVATE
  // Default schema mirrors the original initialization block.
  // Populated by State.init() before any other call.
  // ===========================================================

  let DB = {
    members:          [],
    periods:          [],
    payments:         [],
    transactions:     [],
    events:           [],
    polls:            [],
    activity:         [],
    committees:       [],
    committeeMembers: {},
    familyBranches:   [],
    nextMeeting:      null,
    budget:           [],
    media:            [],
    websiteSettings: {
      header: {
        title:    'مجلس عائلة العوامي',
        subtitle: 'AL AWAMI • ١٤١٣ - ١٩٩٢'
      },
      hero: {
        title:       'مرحباً بكم في مجلس عائلة العوامي',
        description: 'منذ عام ١٩٩٢م - ١٤١٣هـ، نعمل على تعزيز الترابط الأسري وخدمة أفراد العائلة من خلال الأنشطة والفعاليات المتنوعة التي تُنظّم بروح الأُلفة والتعاون والMسؤولية'
      },
      stats: { years: 32, committees: 11, members: '100+' },
      councilPositions: [],
      about: {
        mission: 'تعزيز الترابط الأسري والتواصل بين أفراد عائلة العوامي من خلال تنظيم الأنشطة والفعاليات الدينية والاجتماعية والترفيهية التي تحقق المصلحة العامة وتُرسّخ القيم الأصيلة.',
        vision:  'أن نكون مجلساً عائلياً نموذجياً يُحتذى به في التنظيم والتطوير والخدمة، ونسعى لبناء جيل واعٍ ومتماسك يفخر بانتمائه لعائلة العوامي.'
      },
      values: [
        { icon: '🤝', title: 'الترابط الأسري',     desc: 'نؤمن بأهمية التواصل والتآزر بين أفراد العائلة' },
        { icon: '⚖️', title: 'الشفافية والنزاهة',  desc: 'نلتزم بالشفافية في جميع أعمالنا المالية والإدارية' },
        { icon: '🌟', title: 'التطوير المستمر',     desc: 'نسعى دائماً لتحسين خدماتنا وتطوير أنشطتنا' }
      ]
    }
  };

  // ===========================================================
  // DB LIFECYCLE
  // ===========================================================

  /**
   * init(persistedData, COMMITTEES_DATA, COUNCIL_POSITIONS)
   * Called once on startup. Merges persisted localStorage data
   * (or null) with defaults, then applies post-load patches.
   * Mirrors the original initialization block exactly.
   */
  function init(persistedData, COMMITTEES_DATA, COUNCIL_POSITIONS) {
    if (persistedData && typeof persistedData === 'object') {
      DB = persistedData;
    }

    // Always sync committees structure from constants (not stored)
    DB.committees = COMMITTEES_DATA;

    // Post-load patches — mirrors original guard block
    if (!DB.familyBranches)   DB.familyBranches   = [];
    if (!DB.nextMeeting)      DB.nextMeeting       = null;
    if (!DB.budget)           DB.budget            = [];
    if (!DB.media)            DB.media             = [];
    if (!DB.polls)            DB.polls             = [];
    if (!DB.activity)         DB.activity          = [];
    if (!DB.committeeMembers) DB.committeeMembers  = {};

    if (!DB.websiteSettings) {
      DB.websiteSettings = {
        header:           { title: 'مجلس عائلة العوامي', subtitle: 'AL AWAMI • ١٤١٣ - ١٩٩٢' },
        hero:             { title: 'مرحباً بكم في مجلس عائلة العوامي', description: 'منذ عام ١٩٩٢م - ١٤١٣هـ، نعمل على تعزيز الترابط الأسري وخدمة أفراد العائلة من خلال الأنشطة والفعاليات المتنوعة التي تُنظّم بروح الأُلفة والتعاون والمسؤولية' },
        stats:            { years: 32, committees: 11, members: '100+' },
        councilPositions: COUNCIL_POSITIONS,
        about:            { mission: '', vision: '' },
        values:           []
      };
    }

    // Ensure councilPositions synced from constant if empty/missing
    if (!DB.websiteSettings.councilPositions || DB.websiteSettings.councilPositions.length === 0) {
      DB.websiteSettings.councilPositions = COUNCIL_POSITIONS;
    }
  }

  /**
   * replaceDB(data, COMMITTEES_DATA)
   * Full DB replacement — used by importData and restoreBackup.
   * Preserves the committees structure from constants.
   */
  function replaceDB(data, COMMITTEES_DATA) {
    DB = data;
    DB.committees = COMMITTEES_DATA;
  }

  /**
   * resetDB(COMMITTEES_DATA)
   * Clears all data — used by clearAllData.
   * Returns the pre-reset snapshot for backup before the caller wipes it.
   */
  function resetDB(COMMITTEES_DATA) {
    DB = {
      members:          [],
      periods:          [],
      payments:         [],
      transactions:     [],
      events:           [],
      polls:            [],
      activity:         [],
      committees:       COMMITTEES_DATA,
      committeeMembers: {},
      familyBranches:   [],
      nextMeeting:      null,
      budget:           [],
      media:            [],
      websiteSettings:  DB.websiteSettings || {}
    };
  }

  // ===========================================================
  // ACTIVITY LOG
  // NOTE: saveDB() call removed — caller's saveDB() captures it.
  // ===========================================================

  /**
   * log(action, icon)
   * Prepends an activity entry to DB.activity, caps at 40 entries.
   * Exact logic from original — saveDB() stripped (side effect).
   */
  function log(action, icon) {
    icon = icon || '📝';
    DB.activity.unshift({
      id:     uid(),
      action,
      icon,
      time:   new Date().toLocaleString('ar-SA')
    });
    if (DB.activity.length > 40) DB.activity.pop();
  }

  // ===========================================================
  // WEBSITE SETTINGS
  // All functions receive data as plain objects — no DOM reads.
  // ===========================================================

  /**
   * setAboutSettings({ mission, vision })
   * Exact mutation from saveAboutSettings() — DOM reads stripped.
   */
  function setAboutSettings(data) {
    if (!DB.websiteSettings.about) DB.websiteSettings.about = {};
    DB.websiteSettings.about = {
      mission: data.mission,
      vision:  data.vision
    };
    log('تحديث عن المجلس', '📖');
  }

  /**
   * setLogo(src)
   * Exact mutation from saveLogo() — DOM reads and preview updates stripped.
   */
  function setLogo(src) {
    DB.websiteSettings.logo = src;
    log('تحديث الشعار', '🎨');
  }

  /**
   * resetLogo()
   * Exact mutation from resetLogoToDefault() — confirm2 and DOM stripped.
   */
  function resetLogo() {
    DB.websiteSettings.logo = null;
    log('استعادة الشعار الافتراضي', '🔄');
  }

  /**
   * setHeaderSettings({ title, subtitle })
   * Exact mutation from saveHeaderSettings() — DOM reads stripped.
   */
  function setHeaderSettings(data) {
    DB.websiteSettings.header = {
      title:    data.title,
      subtitle: data.subtitle
    };
    log('تحديث الهيدر', '📌');
  }

  /**
   * setHeroSettings({ title, description })
   * Exact mutation from saveHeroSettings() — DOM reads stripped.
   */
  function setHeroSettings(data) {
    DB.websiteSettings.hero = {
      title:       data.title,
      description: data.description
    };
    log('تحديث البانر', '🎯');
  }

  /**
   * setStatsSettings({ years, committees, members })
   * Exact mutation from saveStatsSettings() — DOM reads stripped.
   */
  function setStatsSettings(data) {
    DB.websiteSettings.stats = {
      years:       parseInt(data.years)       || 0,
      committees:  parseInt(data.committees)  || 0,
      members:     data.members
    };
    log('تحديث الإحصائيات', '📊');
  }

  /**
   * savePosition(idx, data)
   * Exact logic from savePosition() — DOM reads, closeModal, toast, render stripped.
   * idx: string index (edit) or '' (add new).
   * data: { role, name, icon, type, tasks[] }
   */
  function savePosition(idx, data) {
    if (idx !== '') {
      DB.websiteSettings.councilPositions[idx] = data;
      log('تعديل منصب: ' + data.role, '✏️');
    } else {
      if (!DB.websiteSettings.councilPositions) DB.websiteSettings.councilPositions = [];
      DB.websiteSettings.councilPositions.push(data);
      log('إضافة منصب: ' + data.role, '👑');
    }
  }

  /**
   * deletePosition(idx)
   * Exact mutation from deletePosition() — DOM reads, confirm2, closeModal, toast, render stripped.
   */
  function deletePosition(idx) {
    var p = DB.websiteSettings.councilPositions[idx];
    DB.websiteSettings.councilPositions.splice(idx, 1);
    log('حذف منصب: ' + (p ? p.role : ''), '🗑️');
  }

  /**
   * saveValue(idx, data)
   * Exact logic from saveValue() — DOM reads, closeModal, toast, render stripped.
   * idx: string index (edit) or '' (add new).
   * data: { icon, title, desc }
   */
  function saveValue(idx, data) {
    if (idx !== '') {
      DB.websiteSettings.values[idx] = data;
      log('تعديل قيمة: ' + data.title, '✏️');
    } else {
      if (!DB.websiteSettings.values) DB.websiteSettings.values = [];
      DB.websiteSettings.values.push(data);
      log('إضافة قيمة: ' + data.title, '💎');
    }
  }

  /**
   * deleteValue(idx)
   * Exact mutation from deleteValue() — DOM reads, confirm2, closeModal, toast, render stripped.
   */
  function deleteValue(idx) {
    var v = DB.websiteSettings.values[idx];
    DB.websiteSettings.values.splice(idx, 1);
    log('حذف قيمة: ' + (v ? v.title : ''), '🗑️');
  }

  // ===========================================================
  // MEDIA
  // ===========================================================

  /**
   * addMedia(item)
   * Exact mutation from saveMedia() — DOM reads, toast, closeModal, render stripped.
   * item: { title, type, url, date, tags[] } — caller builds the object.
   * Returns the new item (with generated id/createdAt added).
   */
  function addMedia(item) {
    if (!DB.media) DB.media = [];
    var mediaItem = {
      id:        uid(),
      title:     item.title,
      type:      item.type,
      url:       item.url,
      date:      item.date || today(),
      tags:      item.tags || [],
      createdAt: new Date().toISOString()
    };
    DB.media.push(mediaItem);
    log('إضافة ميديا: ' + item.title, '📷');
    return mediaItem;
  }

  /**
   * deleteMedia(id)
   * Exact mutation from deleteMedia() — native confirm, toast, render stripped.
   */
  function deleteMedia(id) {
    DB.media = DB.media.filter(function (m) { return m.id !== id; });
  }

  // ===========================================================
  // MEETING / COUNTDOWN
  // ===========================================================

  /**
   * setNextMeeting({ date, time, title })
   * Exact mutation from saveMeeting() — DOM reads, toast, log, updateCountdown, preview DOM stripped.
   * Caller passes { dateStr, timeStr, titleStr }.
   */
  function setNextMeeting(data) {
    DB.nextMeeting = {
      date:    data.date + 'T' + (data.time || '10:00'),
      title:   data.title || 'الجلسة العمومية للمجلس',
      visible: true
    };
    log('تحديث موعد الجلسة', '⏰');
  }

  /**
   * toggleMeetingVisibility()
   * Exact mutation from hideMeeting() — toast and updateCountdown stripped.
   * Returns new visibility state so caller can show correct toast.
   */
  function toggleMeetingVisibility() {
    if (!DB.nextMeeting) return null;
    DB.nextMeeting.visible = !DB.nextMeeting.visible;
    return DB.nextMeeting.visible;
  }

  /**
   * clearMeeting()
   * Exact mutation from clearMeeting() — confirm2, DOM clear, toast, updateCountdown stripped.
   */
  function clearMeeting() {
    DB.nextMeeting = null;
    log('حذف موعد الجلسة', '🗑️');
  }

  // ===========================================================
  // MEMBERS
  // ===========================================================

  /**
   * saveMember(id, data)
   * Exact logic from saveMember() — DOM reads, toast, closeModal, renders stripped.
   * id: existing member id (edit) or '' (add new).
   * data: { name, phone, idNum, family, joinDate, status, notes }
   */
  function saveMember(id, data) {
    if (id) {
      var m = DB.members.find(function (x) { return x.id === id; });
      if (m) Object.assign(m, data);
      log('تعديل ' + data.name, '✏️');
    } else {
      var newMember = { id: uid() };
      Object.assign(newMember, data);
      DB.members.push(newMember);

      // Auto-create payment record for current period
      var p = curPeriod();
      if (p) {
        DB.payments.push({
          id:       uid(),
          memberId: newMember.id,
          periodId: p.id,
          amount:   0,
          required: p.feeAmount,
          date:     '',
          method:   '',
          status:   data.status === 'معفي' ? 'معفي' : 'لم يدفع',
          notes:    ''
        });
      }
      log('إضافة عضو ' + data.name, '👤');
    }
  }

  /**
   * deleteMember(id)
   * Exact logic from deleteMember() — confirm2, toast, renders stripped.
   * Removes member, their payments, and all committee memberships.
   */
  function deleteMember(id) {
    var m = DB.members.find(function (x) { return x.id === id; });
    DB.members          = DB.members.filter(function (x) { return x.id !== id; });
    DB.payments         = DB.payments.filter(function (x) { return x.memberId !== id; });
    Object.keys(DB.committeeMembers).forEach(function (k) {
      DB.committeeMembers[k] = (DB.committeeMembers[k] || []).filter(function (x) { return x !== id; });
    });
    log('حذف ' + (m ? m.name : ''), '🗑️');
  }

  // ===========================================================
  // FEES / PERIODS
  // ===========================================================

  /**
   * createPeriod(data)
   * Exact logic from createPeriod() — DOM reads, toast, closeModal, renders stripped.
   * data: { name, feeAmount, start, end }
   * Creates period and auto-generates payment records for all active members.
   */
  function createPeriod(data) {
    var p = {
      id:        uid(),
      name:      data.name,
      feeAmount: data.feeAmount,
      start:     data.start || today(),
      end:       data.end   || ''
    };
    DB.periods.push(p);

    DB.members.forEach(function (m) {
      if (m.status !== 'غير نشط') {
        DB.payments.push({
          id:       uid(),
          memberId: m.id,
          periodId: p.id,
          amount:   0,
          required: data.feeAmount,
          date:     '',
          method:   '',
          status:   m.status === 'معفي' ? 'معفي' : 'لم يدفع',
          notes:    ''
        });
      }
    });

    log('دورة جديدة: ' + data.name, '🗓️');
    return p;
  }

  /**
   * savePayment(memberId, paymentData)
   * Exact logic from savePayment() — DOM reads, toast, closeModal, renders stripped.
   * paymentData: { status, amount, date, method, notes }
   * Auto-creates income transaction when payment marked as paid.
   */
  function savePayment(memberId, paymentData) {
    var p = curPeriod();
    if (!p) return;

    var pay = DB.payments.find(function (x) {
      return x.memberId === memberId && x.periodId === p.id;
    });

    if (pay) {
      pay.amount = paymentData.amount;
      pay.date   = paymentData.date;
      pay.method = paymentData.method;
      pay.status = paymentData.status;
      pay.notes  = paymentData.notes;
    } else {
      DB.payments.push({
        id:       uid(),
        memberId: memberId,
        periodId: p.id,
        amount:   paymentData.amount,
        required: p.feeAmount,
        date:     paymentData.date,
        method:   paymentData.method,
        status:   paymentData.status,
        notes:    paymentData.notes
      });
    }

    // Auto-create income transaction when paid
    var m = DB.members.find(function (x) { return x.id === memberId; });
    if (paymentData.status === 'مدفوع' && paymentData.amount > 0) {
      var existing = DB.transactions.find(function (t) {
        return t.memberId === memberId && t.periodId === p.id && t.type === 'إيراد';
      });
      if (!existing) {
        DB.transactions.push({
          id:       uid(),
          type:     'إيراد',
          amount:   paymentData.amount,
          category: 'رسوم الأعضاء',
          committee:'',
          desc:     'رسوم ' + (m ? m.name : '') + ' - ' + p.name,
          date:     paymentData.date,
          memberId: memberId,
          periodId: p.id
        });
      }
    }

    log('دفعة ' + (m ? m.name : '') + ': ' + paymentData.status, '💳');
  }

  // ===========================================================
  // COMMITTEES
  // ===========================================================

  /**
   * addMemberToCommittee(cid, mid)
   * Exact logic from addMemberToCommittee() — DOM read (select value) stripped.
   * Caller reads the select value and passes mid directly.
   */
  function addMemberToCommittee(cid, mid) {
    if (!mid) return;
    if (!DB.committeeMembers[cid]) DB.committeeMembers[cid] = [];
    if (!DB.committeeMembers[cid].includes(mid)) {
      DB.committeeMembers[cid].push(mid);
    }
    var m = DB.members.find(function (x) { return x.id === mid; });
    var c = DB.committees.find(function (x) { return x.id === cid; });
    log('إضافة ' + (m ? m.name : '') + ' للجنة ' + (c ? c.name : ''), '🏛️');
  }

  /**
   * removeMemberFromCommittee(cid, mid)
   * Exact logic from removeMemberFromCommittee() — renders stripped.
   */
  function removeMemberFromCommittee(cid, mid) {
    DB.committeeMembers[cid] = (DB.committeeMembers[cid] || []).filter(function (x) {
      return x !== mid;
    });
  }

  // ===========================================================
  // BUDGET / TRANSACTIONS
  // ===========================================================

  /**
   * addTransaction(data)
   * Exact logic from addTransaction() — DOM reads, toast, closeModal, renders stripped.
   * data: { type, amount, category, committee, desc, date }
   */
  function addTransaction(data) {
    DB.transactions.push({
      id:        uid(),
      type:      data.type,
      amount:    data.amount,
      category:  data.category,
      committee: data.committee,
      desc:      data.desc,
      date:      data.date || today()
    });
    log('معاملة: ' + data.desc, '💵');
  }

  /**
   * deleteTx(id)
   * Exact logic from deleteTx() — confirm2, toast, renders stripped.
   */
  function deleteTx(id) {
    var tx = DB.transactions.find(function (x) { return x.id === id; });
    DB.transactions = DB.transactions.filter(function (x) { return x.id !== id; });
    log('حذف: ' + (tx ? tx.desc : ''), '🗑️');
  }

  // ===========================================================
  // EVENTS
  // ===========================================================

  /**
   * addEvent(data)
   * Exact logic from addEvent() — DOM reads, async FileReader, toast, closeModal, renders stripped.
   * Caller handles the FileReader/image async flow and passes the complete data object.
   * data: { name, committeeId, status, date, budget, participants, lead, notes, images[] }
   */
  function addEvent(data) {
    DB.events.push({
      id:          uid(),
      name:        data.name,
      committeeId: data.committeeId,
      status:      data.status,
      date:        data.date        || '',
      budget:      data.budget      || 0,
      participants:data.participants || 0,
      lead:        data.lead        || '',
      notes:       data.notes       || '',
      icon:        '🎉',
      images:      data.images      || []
    });
    log('فعالية: ' + data.name, '🎉');
  }

  /**
   * deleteEvent(id)
   * Extracted mutation from renderEvents() template string (Phase Pre refactor).
   * confirm2 and renders are handled by caller.
   */
  function deleteEvent(id) {
    DB.events = DB.events.filter(function (x) { return x.id !== id; });
  }

  // ===========================================================
  // FAMILY TREE
  // ===========================================================

  /**
   * saveBranch(id, data)
   * Exact logic from saveBranch() — DOM reads, toast, closeModal, render stripped.
   * id: existing branch id (edit) or '' (add new).
   * data: { name, head, color, members[], count, notes }
   */
  function saveBranch(id, data) {
    if (id) {
      var b = DB.familyBranches.find(function (x) { return x.id === id; });
      if (b) Object.assign(b, data);
      log('تعديل فرع: ' + data.name, '✏️');
    } else {
      DB.familyBranches.push(Object.assign({ id: uid() }, data));
      log('إضافة فرع: ' + data.name, '🌳');
    }
  }

  /**
   * deleteBranch(id)
   * Merges deleteBranch() and deleteBranchConfirm() — confirm2 and renders stripped.
   * Both original functions contained identical DB mutation logic.
   */
  function deleteBranch(id) {
    var b = DB.familyBranches.find(function (x) { return x.id === id; });
    DB.familyBranches = DB.familyBranches.filter(function (x) { return x.id !== id; });
    log('حذف فرع: ' + (b ? b.name : ''), '🗑️');
  }

  // ===========================================================
  // VOTING
  // ===========================================================

  /**
   * createPoll(data)
   * Exact logic from createPoll() — DOM reads, toast, DOM clear, render stripped.
   * data: { title, options[], committee, end }
   * options: array of strings (option text); converted to { text, votes:[] } here.
   */
  function createPoll(data) {
    DB.polls.push({
      id:        uid(),
      title:     data.title,
      options:   data.options.map(function (o) { return { text: o, votes: [] }; }),
      committee: data.committee || '',
      end:       data.end       || '',
      active:    true,
      created:   today()
    });
    log('تصويت: ' + data.title, '🗳️');
  }

  /**
   * vote(pollId, optIdx)
   * Exact logic from vote() — saveDB, render, toast stripped.
   * Reference mutation via DB.polls.find() — identical to original.
   */
  function vote(pollId, optIdx) {
    var poll = DB.polls.find(function (x) { return x.id === pollId; });
    if (!poll || !poll.active) return;
    var uid2 = 'user_default';
    poll.options.forEach(function (o) {
      o.votes = o.votes.filter(function (v) { return v !== uid2; });
    });
    poll.options[optIdx].votes.push(uid2);
  }

  /**
   * closePoll(id)
   * Exact logic from closePollF() — saveDB, render, toast stripped.
   * Reference mutation via DB.polls.find().
   */
  function closePoll(id) {
    var p = DB.polls.find(function (x) { return x.id === id; });
    if (p) p.active = false;
  }

  /**
   * deletePoll(id)
   * Exact logic from deletePollF() — confirm2, saveDB, render, toast stripped.
   */
  function deletePoll(id) {
    DB.polls = DB.polls.filter(function (x) { return x.id !== id; });
  }

  // ===========================================================
  // AI SYNC
  // ===========================================================

  /**
   * syncBudgetFromAI(transactions)
   * Exact logic from syncAIDataToDB() — native alert/confirm, saveDB, render stripped.
   * transactions: aiAnalysisData.transactions array.
   * Returns { synced, duplicates } counts for caller to display.
   */
  function syncBudgetFromAI(transactions) {
    if (!DB.budget) DB.budget = [];
    var synced     = 0;
    var duplicates = 0;

    for (var i = 0; i < transactions.length; i++) {
      var tx          = transactions[i];
      var isDuplicate = false;

      for (var j = 0; j < DB.budget.length; j++) {
        var existing = DB.budget[j];
        if (
          existing.description === tx.description &&
          Math.abs(existing.amount - tx.amount) < 0.01 &&
          existing.date === tx.date
        ) {
          isDuplicate = true;
          duplicates++;
          break;
        }
      }

      if (!isDuplicate) {
        DB.budget.push({
          id:          uid(),
          type:        tx.type,
          category:    tx.category,
          amount:      tx.amount,
          description: tx.description,
          date:        tx.date,
          createdAt:   new Date().toISOString()
        });
        synced++;
      }
    }

    return { synced: synced, duplicates: duplicates };
  }

  // ===========================================================
  // SAMPLE DATA
  // ===========================================================

  /**
   * loadSampleData()
   * Exact logic from loadSampleData() — saveDB call stripped.
   * Guard: no-ops silently if DB.members already populated.
   */
  function loadSampleData() {
    if (DB.members.length) return;

    var names = [
      ['منصور علي العوامي',    'العوامي', '0501111111'],
      ['حسين عبدالحميد العوامي','العوامي', '0502222222'],
      ['عبدالله عماد العوامي', 'العوامي', '0503333333'],
      ['محمود حسن العوامي',    'العوامي', '0504444444'],
      ['راضي ابراهيم العوامي', 'العوامي', '0505555555'],
      ['رضا حسين العوامي',     'العوامي', '0506666666'],
      ['مجتبى سلمان العوامي',  'العوامي', '0507777777'],
      ['حسن علي العوامي',      'العوامي', '0508888888'],
      ['أحمد غازي العوامي',    'العوامي', '0509999999'],
      ['عماد عبدالحميد العوامي','العوامي', '0501010101']
    ];

    names.forEach(function (row, i) {
      DB.members.push({
        id:       'm' + i,
        name:     row[0],
        family:   row[1],
        phone:    row[2],
        idNum:    '1' + (100000000 + i),
        joinDate: '2023-0' + ((i % 9) + 1) + '-01',
        status:   i === 7 ? 'معفي' : 'نشط',
        notes:    ''
      });
    });

    var p = { id: 'p1', name: 'الدورة الأولى 2025', feeAmount: 400, start: '2025-01-01', end: '2025-06-30' };
    DB.periods.push(p);

    DB.members.forEach(function (m, i) {
      var s = m.status === 'معفي' ? 'معفي' : i < 7 ? 'مدفوع' : 'لم يدفع';
      DB.payments.push({
        id:       'pay' + i,
        memberId: m.id,
        periodId: p.id,
        amount:   s === 'مدفوع' ? 400 : 0,
        required: 400,
        date:     s === 'مدفوع' ? '2025-0' + ((i % 6) + 1) + '-15' : '',
        method:   'تحويل بنكي',
        status:   s,
        notes:    ''
      });
    });

    [
      { type: 'إيراد',  amount: 2800, category: 'رسوم الأعضاء',   committee: '',   desc: 'رسوم الأعضاء الدورة الأولى 2025',   date: '2025-01-15' },
      { type: 'مصروف', amount: 8000, category: 'رحلة العمرة',     committee: 'c1', desc: 'تكاليف رحلة العمرة الرجبية',         date: '2025-02-10' },
      { type: 'مصروف', amount: 3200, category: 'غداء العيد',       committee: 'c2', desc: 'غداء عيد الفطر',                     date: '2025-04-11' },
      { type: 'إيراد',  amount: 1200, category: 'تبرعات',           committee: '',   desc: 'تبرع أحد الأعضاء',                    date: '2025-01-20' },
      { type: 'مصروف', amount: 500,  category: 'مصاريف إدارية',   committee: '',   desc: 'مصاريف إدارية',                       date: '2025-03-05' }
    ].forEach(function (t, i) {
      DB.transactions.push(Object.assign({ id: 'tx' + i }, t));
    });

    [
      { name: 'رحلة العمرة الرجبية 2025', committeeId: 'c1', status: 'مكتمل', date: '2025-02-10', budget: 8000, participants: 15, lead: 'عبدالله عماد', icon: '🕋',  images: [] },
      { name: 'غداء عيد الفطر',            committeeId: 'c2', status: 'مكتمل', date: '2025-04-11', budget: 3200, participants: 40, lead: 'محمود حسن',   icon: '🍖',  images: [] },
      { name: 'المسابقة الرمضانية',        committeeId: 'c3', status: 'قادم',  date: '2025-03-01', budget: 2000, participants: 30, lead: '',             icon: '🌙',  images: [] },
      { name: 'رحلة الباحة الترفيهية',     committeeId: 'c4', status: 'قادم',  date: '2025-09-01', budget: 5000, participants: 25, lead: '',             icon: '🎡',  images: [] },
      { name: 'ليلة القدر',                committeeId: 'c5', status: 'قادم',  date: '2025-04-25', budget: 1500, participants: 35, lead: '',             icon: '✨', images: [] }
    ].forEach(function (e, i) {
      DB.events.push(Object.assign({ id: 'ev' + i, type: 'أخرى', notes: '' }, e));
    });

    DB.committeeMembers = {
      c1:  ['m2', 'm0'],
      c2:  ['m3', 'm4'],
      c3:  ['m0', 'm6'],
      c4:  ['m3'],
      c8:  ['m1', 'm2'],
      c10: ['m0', 'm7']
    };

    DB.polls.push({
      id:      'poll1',
      title:   'هل توافق على رفع قيمة الاشتراك من 300 إلى 400 ريال؟',
      options: [
        { text: 'نعم، أوافق', votes: ['u1', 'u2', 'u3', 'u4'] },
        { text: 'لا، أرفض',   votes: ['u5'] },
        { text: 'محايد',       votes: ['u6'] }
      ],
      committee: '',
      end:       '2025-07-01',
      active:    true,
      created:   '2025-06-01'
    });

    var nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    nextMonth.setDate(15);
    DB.nextMeeting = {
      date:  nextMonth.toISOString().split('T')[0],
      title: 'الجلسة العمومية للمجلس'
    };

    DB.familyBranches = [
      { id: 'b_kazim',     name: 'آل كاظم',                    head: 'كاظم العوامي',                    count: 7,  color: '#47915C', notes: 'الفرع الرئيسي', members: ['علي', 'محمد', 'علي (أبو الحجي)', 'محمد', 'علي', 'هاني', 'تيسير'] },
      { id: 'b_ibrahim',   name: 'آل إبراهيم (أبو خليل)',      head: 'إبراهيم (أبو خليل)',              count: 3,  color: '#1B3456', notes: '',               members: ['سعيد (أبو خديجة)', 'عارف هنيدي', 'هاجر (أم وائل)'] },
      { id: 'b_radi',      name: 'آل راضي',                    head: 'راضي',                            count: 8,  color: '#c8a84b', notes: '',               members: ['علي (أبو صبري)', 'صبري', 'راضي', 'أمين', 'فخري', 'عماد', 'سعيد', 'معين'] },
      { id: 'b_salman',    name: 'آل سلمان علي',               head: 'سلمان علي',                       count: 11, color: '#8e44ad', notes: '',               members: ['سلمان علي', 'عبدالله', 'مصطفى', 'مدينة (أم علي)', 'فاطمة (أم حسين)', 'حسن', 'علي', 'وديع', 'زكي', 'محمد', 'مروان'] },
      { id: 'b_ali_haidar',name: 'آل علي (أبو حيدر)',          head: 'علي (أبو حيدر)',                  count: 5,  color: '#2980b9', notes: '',               members: ['محمد (أبو عبدالله)', 'عبدالله', 'غازي', 'سعيد', 'جهاد'] },
      { id: 'b_ahmed',     name: 'آل أحمد عبد الحميد',         head: 'أحمد عبد الحميد (أبو عالء)',     count: 3,  color: '#c0392b', notes: '',               members: ['مكي (أبو أحمد)', 'زهراء', 'زينب (أم أمجد وقع)'] },
      { id: 'b_naser',     name: 'آل ناصر العوامي',            head: 'ناصر العوامي',                    count: 10, color: '#27ae60', notes: '',               members: ['علي', 'محمد', 'ناصر', 'طارق', 'محمود', 'عزيز', 'مكي', 'عبدالله', 'مهند', 'علي'] },
      { id: 'b_ibrahim2',  name: 'آل إبراهيم (أبو مصطفى)',    head: 'إبراهيم (أبو مصطفى)',             count: 5,  color: '#e67e22', notes: '',               members: ['مصطفى', 'خليل', 'مرتضى', 'مجتبى', 'أمين'] },
      { id: 'b_hussein',   name: 'آل حسين',                    head: 'حسين',                            count: 0,  color: '#34495e', notes: '',               members: [] }
    ];

    log('تم تهيئة النظام ببيانات عائلة العوامي 🚀', '🎯');
  }

  // ===========================================================
  // SETTERS — Phase 3 minimal wrappers
  // Each wraps exactly one DB property assignment. No added logic.
  // ===========================================================

  function setMembers(arr)          { DB.members          = arr; }
  function setPayments(arr)         { DB.payments         = arr; }
  function setTransactions(arr)     { DB.transactions     = arr; }
  function setEvents(arr)           { DB.events           = arr; }
  function setPolls(arr)            { DB.polls            = arr; }
  function setFamilyBranches(arr)   { DB.familyBranches   = arr; }
  function setMedia(arr)            { DB.media            = arr; }
  function setCommitteeMembers(obj) { DB.committeeMembers = obj; }
  /** Raw object setter — receives a fully-built nextMeeting object */
  function setNextMeetingObj(obj)   { DB.nextMeeting      = obj; }
  /** Null-out nextMeeting */
  function clearNextMeeting()       { DB.nextMeeting      = null; }
  /** Guard: initialize DB.media array if absent (needed before first push) */
  function ensureMedia()            { if (!DB.media) DB.media = []; }

  // ===========================================================
  // PUBLIC API
  // ===========================================================

  return {

    // ── Lifecycle ──────────────────────────────────────────────
    init:      init,
    replaceDB: replaceDB,
    resetDB:   resetDB,

    // ── DB accessor (bridge for remaining LHS writes in main file) ──
    getDB: function () { return DB; },

    // ── Explicit getters — one per top-level DB property ───────
    // All return live object/array references; existing mutations
    // through them (e.g. State.getEvents().push(...)) remain valid.
    getMembers:          function () { return DB.members; },
    getEvents:           function () { return DB.events; },
    getTransactions:     function () { return DB.transactions; },
    getPolls:            function () { return DB.polls; },
    getPayments:         function () { return DB.payments; },
    getPeriods:          function () { return DB.periods; },
    getCommitteeMembers: function () { return DB.committeeMembers; },
    getFamilyBranches:   function () { return DB.familyBranches; },
    getWebsiteSettings:  function () { return DB.websiteSettings; },
    getNextMeeting:      function () { return DB.nextMeeting; },
    getActivity:         function () { return DB.activity; },
    getCommittees:       function () { return DB.committees; },
    getMedia:            function () { return DB.media || []; },
    getBudget:           function () { return DB.budget  || []; },

    // ── Helpers exposed for read-only use in main file ─────────
    curPeriod: curPeriod,

    // ── Activity ───────────────────────────────────────────────
    log: log,

    // ── Website settings ───────────────────────────────────────
    setAboutSettings:  setAboutSettings,
    setLogo:           setLogo,
    resetLogo:         resetLogo,
    setHeaderSettings: setHeaderSettings,
    setHeroSettings:   setHeroSettings,
    setStatsSettings:  setStatsSettings,
    savePosition:      savePosition,
    deletePosition:    deletePosition,
    saveValue:         saveValue,
    deleteValue:       deleteValue,

    // ── Media ──────────────────────────────────────────────────
    addMedia:    addMedia,
    deleteMedia: deleteMedia,

    // ── Meeting ────────────────────────────────────────────────
    setNextMeeting:          setNextMeeting,
    toggleMeetingVisibility: toggleMeetingVisibility,
    clearMeeting:            clearMeeting,

    // ── Members ────────────────────────────────────────────────
    saveMember:   saveMember,
    deleteMember: deleteMember,

    // ── Fees ───────────────────────────────────────────────────
    createPeriod: createPeriod,
    savePayment:  savePayment,

    // ── Committees ─────────────────────────────────────────────
    addMemberToCommittee:    addMemberToCommittee,
    removeMemberFromCommittee: removeMemberFromCommittee,

    // ── Budget ─────────────────────────────────────────────────
    addTransaction: addTransaction,
    deleteTx:       deleteTx,

    // ── Events ─────────────────────────────────────────────────
    addEvent:    addEvent,
    deleteEvent: deleteEvent,

    // ── Family tree ────────────────────────────────────────────
    saveBranch:   saveBranch,
    deleteBranch: deleteBranch,

    // ── Voting ─────────────────────────────────────────────────
    createPoll: createPoll,
    vote:       vote,
    closePoll:  closePoll,
    deletePoll: deletePoll,

    // ── AI ─────────────────────────────────────────────────────
    syncBudgetFromAI: syncBudgetFromAI,

    // ── Setters (Phase 3) ──────────────────────────────────────
    setMembers:          setMembers,
    setPayments:         setPayments,
    setTransactions:     setTransactions,
    setEvents:           setEvents,
    setPolls:            setPolls,
    setFamilyBranches:   setFamilyBranches,
    setMedia:            setMedia,
    setCommitteeMembers: setCommitteeMembers,
    setNextMeetingObj:   setNextMeetingObj,
    clearNextMeeting:    clearNextMeeting,
    ensureMedia:         ensureMedia,

    // ── Sample data ────────────────────────────────────────────
    loadSampleData: loadSampleData
  };

})();
