/**
 * core/services/member.service.js
 * ================================
 * Al Awami Family Council — Member Service
 *
 * Coordination layer for all member-related write operations.
 * Sits between the UI layer (DOM reads, event handlers) and the
 * State layer (pure in-memory cache).
 *
 * Responsibilities:
 *   1. Accept pre-read data from the UI layer
 *   2. Persist to MySQL via /api/members.php (fetch)
 *   3. Update State cache on success
 *   4. Trigger side-effect renders
 *   5. Show toast feedback
 *
 * Does NOT:
 *   - Read from the DOM
 *   - Call saveDB() / touch localStorage for members
 *   - Contain business logic
 *
 * Depends on globals available at call time:
 *   State, closeModal, toast, confirm2,
 *   renderMembers, renderDashboard, renderCommittees,
 *   updateSidebar, showCommitteeDetail, log
 */

const MemberService = (function () {
  'use strict';

  const API = 'api/members.php';

  // ─────────────────────────────────────────────────────────────
  // _request(url, options)
  // Central fetch wrapper — throws on non-2xx with server message.
  // ─────────────────────────────────────────────────────────────
  async function _request(url, options = {}) {
    const res  = await fetch(url, {
      headers: { 'Content-Type': 'application/json' },
      ...options,
    });
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || `HTTP ${res.status}`);
    return json;
  }

  // ─────────────────────────────────────────────────────────────
  // _toState(row)
  // Normalises API snake_case response fields -> State camelCase
  // shape used by all render functions.
  //   id_num    -> idNum
  //   join_date -> joinDate
  //   branch_id -> branchId
  // ─────────────────────────────────────────────────────────────
  function _toState(row) {
    return {
      id:       row.id,
      name:     row.name      || '',
      family:   row.family    || '',
      phone:    row.phone     || '',
      idNum:    row.id_num    || '',
      joinDate: row.join_date || '',
      status:   row.status    || 'مشترك',
      notes:    row.notes     || '',
      branchId: row.branch_id || null,
    };
  }

  // ─────────────────────────────────────────────────────────────
  // _toAPI(data)
  // Normalises State camelCase payload -> API snake_case field names.
  // ─────────────────────────────────────────────────────────────
  function _toAPI(data) {
    return {
      name:      data.name     || '',
      family:    data.family   || '',
      phone:     data.phone    || '',
      id_num:    data.idNum    || null,
      join_date: data.joinDate || null,
      status:    data.status   || 'مشترك',
      notes:     data.notes    || '',
      branch_id: data.branchId || null,
    };
  }

  // ─────────────────────────────────────────────────────────────
  // syncMembersFromAPI()
  //
  // Fetches all members from MySQL and populates State cache.
  // Called once on app boot, before any render.
  // ─────────────────────────────────────────────────────────────
  async function syncMembersFromAPI() {
    try {
      const json = await _request(API);
      State.setMembers(json.data.map(_toState));
    } catch (e) {
      console.error('syncMembersFromAPI failed:', e.message);
      toast('تعذّر تحميل بيانات الأعضاء', 'error');
    }
  }

  // ─────────────────────────────────────────────────────────────
  // saveMember(id, data)
  //
  // Creates (POST) or updates (PUT) a member via API, then syncs
  // the returned row into State cache.
  //
  // @param {string}  id   — existing member id (edit) or '' (add)
  // @param {object}  data — { name, phone, idNum, family,
  //                           joinDate, status, notes }
  //
  // Flow:
  //   POST /api/members.php        (create)
  //   PUT  /api/members.php?id=x   (update)
  //   -> update State cache
  //   -> closeModal -> toast -> renderMembers -> renderDashboard
  //   -> updateSidebar
  // ─────────────────────────────────────────────────────────────
  async function saveMember(id, data) {
    try {
      var isUpdate = Boolean(id);
      var url      = isUpdate ? (API + '?id=' + encodeURIComponent(id)) : API;
      var method   = isUpdate ? 'PUT' : 'POST';

      var json  = await _request(url, {
        method: method,
        body:   JSON.stringify(_toAPI(data)),
      });
      var saved = _toState(json.data);

      if (isUpdate) {
        var idx = State.getMembers().findIndex(function(x) { return x.id === id; });
        if (idx !== -1) {
          State.getMembers()[idx] = saved;
        }
      } else {
        State.getMembers().push(saved);
      }

      log((isUpdate ? 'تعديل ' : 'إضافة عضو ') + saved.name, '👤');
      closeModal('modal-member');
      toast(isUpdate ? 'تم التحديث' : 'تم الإضافة');
      renderMembers();
      renderDashboard();
      updateSidebar();

    } catch (e) {
      console.error('saveMember failed:', e.message);
      toast('فشل الحفظ: ' + e.message, 'error');
    }
  }

  // ─────────────────────────────────────────────────────────────
  // deleteMember(id)
  //
  // Prompts for confirmation, then deletes the member via API
  // and removes them from State cache.
  // ON DELETE CASCADE in schema cleans up payments + committee_members.
  //
  // Flow:
  //   confirm2 -> DELETE /api/members.php?id=x
  //   -> remove from State cache
  //   -> toast -> renderMembers -> renderDashboard -> updateSidebar
  // ─────────────────────────────────────────────────────────────
  function deleteMember(id) {
    var m = State.getMembers().find(function(x) { return x.id === id; });
    confirm2('\u062d\u0630\u0641 \u0627\u0644\u0639\u0636\u0648 "' + (m ? m.name : '') + '"؟', async function() {
      try {
        await _request(API + '?id=' + encodeURIComponent(id), { method: 'DELETE' });

        State.setMembers(State.getMembers().filter(function(x) { return x.id !== id; }));

        toast('تم الحذف');
        renderMembers();
        renderDashboard();
        updateSidebar();

      } catch (e) {
        console.error('deleteMember failed:', e.message);
        toast('فشل الحذف: ' + e.message, 'error');
      }
    });
  }

  // ─────────────────────────────────────────────────────────────
  // addMemberToCommittee(cid, mid)
  //
  // Adds a member to a committee (State-only; committee_members
  // table migration is out of scope for this release).
  // saveDB() removed.
  // ─────────────────────────────────────────────────────────────
  function addMemberToCommittee(cid, mid) {
    if (!mid) return;
    State.addMemberToCommittee(cid, mid);
    showCommitteeDetail(cid);
    renderCommittees();
  }

  // ─────────────────────────────────────────────────────────────
  // removeMemberFromCommittee(cid, mid)
  //
  // Removes a member from a committee (State-only).
  // saveDB() removed.
  // ─────────────────────────────────────────────────────────────
  function removeMemberFromCommittee(cid, mid) {
    State.removeMemberFromCommittee(cid, mid);
    showCommitteeDetail(cid);
    renderCommittees();
  }

  // ─────────────────────────────────────────────────────────────
  // Public API
  // ─────────────────────────────────────────────────────────────
  return {
    syncMembersFromAPI:        syncMembersFromAPI,
    saveMember:                saveMember,
    deleteMember:              deleteMember,
    addMemberToCommittee:      addMemberToCommittee,
    removeMemberFromCommittee: removeMemberFromCommittee,
  };

})();
