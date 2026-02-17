/**
 * core/services/poll.service.js
 * ==============================
 * Al Awami Family Council — Poll Service
 *
 * Coordination layer for all voting/poll write operations.
 * Sits between the UI layer (DOM reads, event handlers) and the
 * State layer (pure DB mutations).
 *
 * Responsibilities:
 *   1. Accept pre-read data from the UI layer
 *   2. Call the appropriate State method
 *   3. Persist via saveDB()
 *   4. Reset form fields where originally done
 *   5. Show toast feedback
 *   6. Trigger renderVoting() (sole render in all 4 originals)
 *   7. Hold confirm2() dialogs — kept out of State per rule 5
 *
 * Does NOT:
 *   - Read from the DOM (except post-save field resets)
 *   - Mutate DB directly
 *   - Contain business logic (that lives in State)
 *   - Call renderDashboard() — absent from all 4 original functions
 *
 * Depends on globals available at call time:
 *   State, saveDB, toast, confirm2, renderVoting
 */

const PollService = (function () {
  'use strict';

  // ─────────────────────────────────────────────────────────────
  // createPoll(data)
  //
  // Creates a new poll and resets the creation form.
  //
  // @param {object} data — { title, options[], committee, end }
  //   options: array of option-text strings, already split and
  //   trimmed by the UI caller.
  //   All other fields pre-read from the DOM by the UI caller.
  //
  // Mirrors original createPoll() coordination exactly:
  //   State.createPoll → saveDB
  //   → clear poll-title + poll-options fields
  //   → toast('تم') → renderVoting()
  //
  // Validation (title required, ≥2 options) stays in the UI layer
  // because it reads DOM values and shows inline error toasts.
  //
  // Field clearing is kept here (not in UI) because it is
  // post-save cleanup, not pre-save input reading — matching
  // the same pattern used by FinanceService.addTransaction().
  // ─────────────────────────────────────────────────────────────
  function createPoll(data) {
    State.createPoll(data);
    saveDB();
    document.getElementById('poll-title').value = '';
    document.getElementById('poll-options').value = '';
    toast('تم');
    renderVoting();
  }

  // ─────────────────────────────────────────────────────────────
  // vote(pollId, optIdx)
  //
  // Records the default user's vote on a poll option, replacing
  // any previous vote they cast on the same poll.
  //
  // @param {string} pollId  — poll id
  // @param {number} optIdx  — zero-based index of chosen option
  //
  // Mirrors original vote() coordination exactly:
  //   State.vote → saveDB → renderVoting → toast('تم تسجيل صوتك')
  //
  // Guard (!poll || !poll.active) is inside State.vote() —
  // the service passes through unconditionally and lets State
  // handle the no-op silently, exactly as the original did.
  // ─────────────────────────────────────────────────────────────
  function vote(pollId, optIdx) {
    State.vote(pollId, optIdx);
    saveDB();
    renderVoting();
    toast('تم تسجيل صوتك');
  }

  // ─────────────────────────────────────────────────────────────
  // closePoll(id)
  //
  // Marks a poll as inactive (closed), preventing further votes.
  //
  // @param {string} id — poll id to close
  //
  // Mirrors original closePollF() coordination exactly:
  //   find poll → if(p): State.closePoll → saveDB
  //   → renderVoting → toast('تم إغلاق التصويت')
  //
  // The original guard `if(p)` is replicated here so the service
  // is a complete drop-in. State.closePoll() is also safe to call
  // with a non-existent id (no-op), but the guard is kept to
  // preserve exact original behavior.
  // ─────────────────────────────────────────────────────────────
  function closePoll(id) {
    const p = State.getPolls().find(x => x.id === id);
    if (p) {
      State.closePoll(id);
      saveDB();
      renderVoting();
      toast('تم إغلاق التصويت');
    }
  }

  // ─────────────────────────────────────────────────────────────
  // deletePoll(id)
  //
  // Prompts for confirmation, then permanently removes a poll.
  //
  // @param {string} id — poll id to delete
  //
  // Mirrors original deletePollF() coordination exactly:
  //   confirm2 → State.deletePoll → saveDB
  //   → renderVoting → toast('تم')
  //
  // confirm2 is kept here (service layer), not in State,
  // per rule 5.
  // ─────────────────────────────────────────────────────────────
  function deletePoll(id) {
    confirm2('حذف هذا التصويت؟', () => {
      State.deletePoll(id);
      saveDB();
      renderVoting();
      toast('تم');
    });
  }

  // ─────────────────────────────────────────────────────────────
  // Public API
  // ─────────────────────────────────────────────────────────────
  return {
    createPoll: createPoll,
    vote:       vote,
    closePoll:  closePoll,
    deletePoll: deletePoll,
  };

})();
