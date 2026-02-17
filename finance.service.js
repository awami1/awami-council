/**
 * core/services/finance.service.js
 * ==================================
 * Al Awami Family Council — Finance Service
 *
 * Coordination layer for all financial write operations.
 * Sits between the UI layer (DOM reads, event handlers) and the
 * State layer (pure DB mutations).
 *
 * Responsibilities:
 *   1. Accept pre-read data from the UI layer
 *   2. Call the appropriate State method
 *   3. Persist via saveDB()
 *   4. Close modals / reset form fields where originally done
 *   5. Show toast feedback
 *   6. Trigger all render cascades in original order
 *   7. Hold confirm2() dialogs (kept out of State per rule 5)
 *
 * Does NOT:
 *   - Read from the DOM
 *   - Mutate DB directly
 *   - Contain business logic (that lives in State)
 *
 * Depends on globals available at call time:
 *   State, saveDB, closeModal, toast, confirm2, curPeriod, today,
 *   renderFees, renderBudget, renderDashboard, updateSidebar, log
 */

const FinanceService = (function () {
  'use strict';

  // ─────────────────────────────────────────────────────────────
  // createPeriod(data)
  //
  // Creates a new subscription period and auto-generates payment
  // records for every active member.
  //
  // @param {object} data — { name, feeAmount, start, end }
  //   All fields pre-read from the DOM by the UI caller.
  //
  // Mirrors original createPeriod() coordination exactly:
  //   State.createPeriod → saveDB → closeModal('modal-period')
  //   → toast → renderFees → renderDashboard
  //
  // Validation (name + amount required) stays in the UI layer
  // because it reads DOM values and shows inline error toasts.
  // ─────────────────────────────────────────────────────────────
  function createPeriod(data) {
    State.createPeriod(data);
    saveDB();
    closeModal('modal-period');
    toast(`تم إنشاء "${data.name}"`);
    renderFees();
    renderDashboard();
  }

  // ─────────────────────────────────────────────────────────────
  // savePayment(memberId, paymentData)
  //
  // Records or updates a member's payment for the current period.
  // Auto-creates an income transaction when status is 'مدفوع'.
  //
  // @param {string} memberId     — member id
  // @param {object} paymentData  — { status, amount, date,
  //                                  method, notes }
  //   All fields pre-read from the DOM by the UI caller.
  //
  // Mirrors original savePayment() coordination exactly:
  //   State.savePayment → saveDB → closeModal('modal-pay')
  //   → toast → renderFees → renderBudget → renderDashboard
  //   → updateSidebar
  // ─────────────────────────────────────────────────────────────
  function savePayment(memberId, paymentData) {
    State.savePayment(memberId, paymentData);
    saveDB();
    closeModal('modal-pay');
    toast('تم تسجيل الدفعة');
    renderFees();
    renderBudget();
    renderDashboard();
    updateSidebar();
  }

  // ─────────────────────────────────────────────────────────────
  // addTransaction(data)
  //
  // Appends a new income or expense transaction to the ledger.
  //
  // @param {object} data — { type, amount, category, committee,
  //                          desc, date }
  //   All fields pre-read from the DOM by the UI caller.
  //
  // Mirrors original addTransaction() coordination exactly:
  //   State.addTransaction → saveDB → closeModal('modal-tx')
  //   → clear tx-amount + tx-desc fields → toast
  //   → renderBudget → renderDashboard → updateSidebar
  //
  // Field clearing (tx-amount, tx-desc) is a post-save UI reset
  // that was interleaved with coordination in the original;
  // kept here to preserve the original single-call contract.
  // ─────────────────────────────────────────────────────────────
  function addTransaction(data) {
    State.addTransaction(data);
    saveDB();
    closeModal('modal-tx');
    ['tx-amount', 'tx-desc'].forEach(function (id) {
      document.getElementById(id).value = '';
    });
    toast('تم الإضافة');
    renderBudget();
    renderDashboard();
    updateSidebar();
  }

  // ─────────────────────────────────────────────────────────────
  // deleteTx(id)
  //
  // Prompts for confirmation, then permanently removes a
  // transaction from the ledger.
  //
  // @param {string} id — transaction id to delete
  //
  // Mirrors original deleteTx() coordination exactly:
  //   confirm2 → State.deleteTx → saveDB
  //   → renderBudget → renderDashboard → updateSidebar → toast
  //
  // confirm2 is kept here (service layer) not in State, per rule 5.
  // ─────────────────────────────────────────────────────────────
  function deleteTx(id) {
    const tx = State.getTransactions().find(x => x.id === id);
    confirm2(`حذف "${tx?.desc}"؟`, () => {
      State.deleteTx(id);
      saveDB();
      renderBudget();
      renderDashboard();
      updateSidebar();
      toast('تم');
    });
  }

  // ─────────────────────────────────────────────────────────────
  // Public API
  // ─────────────────────────────────────────────────────────────
  return {
    createPeriod:    createPeriod,
    savePayment:     savePayment,
    addTransaction:  addTransaction,
    deleteTx:        deleteTx,
  };

})();
