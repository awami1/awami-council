<?php
/**
 * card.php — مولّد بطاقة تهنئة العيد (Wizard)
 * صفحة مستقلة بتصميم immersive — 4 خطوات
 */
?>

<style>
/* ══════════════════════════════════════════════
   Card Wizard — أنماط مخصوصة (card-* prefix)
   ══════════════════════════════════════════════ */

#card-wizard {
  background: var(--gradient-header);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  overflow-x: hidden;
  padding-bottom: 40px;
}

/* ── Top Bar ── */
.card-topbar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 18px 20px 10px;
  width: 100%;
  max-width: 480px;
}
.card-topbar-logo {
  width: 36px;
  height: 36px;
  flex-shrink: 0;
}
.card-topbar-name {
  color: rgba(255,255,255,0.85);
  font-family: var(--font-heading);
  font-size: 15px;
  font-weight: 700;
  white-space: nowrap;
}

/* ── Progress Dots ── */
.card-progress {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 0 20px;
}
.card-dot {
  width: 8px;
  height: 8px;
  border-radius: 4px;
  background: rgba(255,255,255,0.25);
  transition: all 0.35s ease;
}
.card-dot.active {
  width: 28px;
  background: rgba(255,255,255,0.95);
}
.card-dot.completed {
  background: rgba(255,255,255,0.55);
}

/* ── App Container ── */
.card-app {
  width: 100%;
  max-width: 480px;
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

/* ── Steps ── */
.card-step {
  display: none;
  width: 100%;
  padding: 0 20px;
  box-sizing: border-box;
}
.card-step.active {
  display: flex;
  flex-direction: column;
  align-items: center;
  animation: cardFadeIn 0.4s ease;
}

@keyframes cardFadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Step 0: Welcome ── */
.card-welcome-logo {
  width: 100px;
  height: 100px;
  margin-top: 20px;
  opacity: 0.9;
}
.card-welcome-title {
  font-family: var(--font-heading);
  font-size: 38px;
  font-weight: 700;
  color: #fff;
  margin: 24px 0 12px;
  line-height: 1.3;
}
.card-welcome-subtitle {
  color: rgba(255,255,255,0.75);
  font-family: var(--font-body);
  font-size: 16px;
  line-height: 1.7;
  margin-bottom: 8px;
  max-width: 340px;
}
.card-welcome-council {
  color: rgba(255,255,255,0.5);
  font-family: var(--font-heading);
  font-size: 14px;
  margin-bottom: 32px;
}
.card-welcome-footer {
  color: rgba(255,255,255,0.3);
  font-size: 12px;
  margin-top: auto;
  padding-top: 40px;
}

/* ── Buttons ── */
.card-btn-primary {
  background: #fff;
  color: var(--green-dark);
  border: none;
  border-radius: 14px;
  padding: 14px 48px;
  font-size: 17px;
  font-weight: 700;
  font-family: var(--font-body);
  cursor: pointer;
  transition: all 0.25s ease;
  min-width: 160px;
}
.card-btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}
.card-btn-primary:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.card-btn-secondary {
  background: transparent;
  color: rgba(255,255,255,0.8);
  border: 1.5px solid rgba(255,255,255,0.25);
  border-radius: 14px;
  padding: 12px 32px;
  font-size: 15px;
  font-weight: 600;
  font-family: var(--font-body);
  cursor: pointer;
  transition: all 0.25s ease;
}
.card-btn-secondary:hover {
  border-color: rgba(255,255,255,0.5);
  background: rgba(255,255,255,0.05);
}

.card-btn-row {
  display: flex;
  gap: 12px;
  margin-top: 28px;
  width: 100%;
  justify-content: center;
}

/* ── Step 1: Name + Shape ── */
.card-step-title {
  color: #fff;
  font-family: var(--font-heading);
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 20px;
}
.card-step-label {
  color: rgba(255,255,255,0.7);
  font-family: var(--font-body);
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 10px;
  align-self: flex-start;
}

.card-input {
  width: 100%;
  padding: 14px 18px;
  background: rgba(255,255,255,0.12);
  border: 1.5px solid rgba(255,255,255,0.2);
  border-radius: 12px;
  color: #fff;
  font-size: 17px;
  font-family: var(--font-body);
  outline: none;
  transition: all 0.25s ease;
  box-sizing: border-box;
  text-align: center;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}
.card-input::placeholder {
  color: rgba(255,255,255,0.4);
}
.card-input:focus {
  border-color: rgba(255,255,255,0.5);
  background: rgba(255,255,255,0.18);
}

.card-shape-options {
  display: flex;
  gap: 16px;
  width: 100%;
  margin-top: 6px;
}
.card-shape-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 18px 12px;
  background: rgba(255,255,255,0.08);
  border: 2px solid rgba(255,255,255,0.15);
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.25s ease;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}
.card-shape-btn:hover {
  background: rgba(255,255,255,0.12);
}
.card-shape-btn.selected {
  border-color: rgba(255,255,255,0.7);
  background: rgba(255,255,255,0.15);
}
.card-shape-preview {
  background: rgba(255,255,255,0.15);
  border-radius: 6px;
}
.card-shape-preview-square {
  width: 50px;
  height: 50px;
}
.card-shape-preview-portrait {
  width: 36px;
  height: 64px;
}
.card-shape-label {
  color: rgba(255,255,255,0.8);
  font-size: 14px;
  font-weight: 600;
  font-family: var(--font-body);
}
.card-shape-desc {
  color: rgba(255,255,255,0.45);
  font-size: 11px;
  font-family: var(--font-body);
}

/* ── Step 2: Template Grid ── */
.card-carousel {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  width: 100%;
  padding: 10px 0 16px;
}
.card-template-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  position: relative;
}
.card-template-thumb {
  border-radius: 12px;
  border: 3px solid transparent;
  transition: all 0.25s ease;
  display: block;
  width: 100%;
  height: auto;
}
.card-template-item.selected .card-template-thumb {
  border-color: var(--accent, #c8a84b);
  box-shadow: 0 4px 20px rgba(200,168,75,0.3);
}
.card-template-name {
  color: rgba(255,255,255,0.7);
  font-size: 13px;
  font-family: var(--font-body);
  font-weight: 600;
}
.card-template-item.selected .card-template-name {
  color: #fff;
}

/* ── Step 3: Result ── */
.card-result-msg {
  color: #fff;
  font-family: var(--font-heading);
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 20px;
}
.card-result-canvas-wrap {
  width: 100%;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 12px 40px rgba(0,0,0,0.25);
}
.card-result-canvas-wrap canvas {
  display: block;
  width: 100%;
  height: auto;
}
.card-action-row {
  display: flex;
  gap: 10px;
  margin-top: 20px;
  width: 100%;
}
.card-action-btn {
  flex: 1;
  padding: 13px 16px;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
  font-family: var(--font-body);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}
.card-action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
.card-action-download {
  background: #fff;
  color: var(--green-dark);
}
.card-action-whatsapp {
  background: #25D366;
  color: #fff;
}
.card-back-link {
  color: rgba(255,255,255,0.6);
  font-size: 14px;
  font-family: var(--font-body);
  margin-top: 20px;
  cursor: pointer;
  background: none;
  border: none;
  text-decoration: underline;
  text-underline-offset: 3px;
  transition: color 0.2s;
}
.card-back-link:hover {
  color: rgba(255,255,255,0.9);
}

/* ── Responsive ── */
@media (max-width: 480px) {
  .card-welcome-title { font-size: 32px; }
  .card-welcome-logo { width: 80px; height: 80px; }
  .card-action-row { flex-direction: column; }
  .card-action-btn { width: 100%; }
}
</style>

<section id="card-wizard">
  <div class="card-app">

    <!-- Top Bar -->
    <div class="card-topbar">
      <svg class="card-topbar-logo" viewBox="0 0 80 80" fill="none">
        <path d="M55 12 C58 8,65 10,64 18 C63 26,54 30,50 38 C46 46,48 56,42 62 C36 68,26 66,24 58 C22 50,30 44,32 36" stroke="#fff" stroke-width="6" stroke-linecap="round" fill="none"/>
        <path d="M32 36 C28 44,20 46,20 54 C20 62,28 66,34 62" stroke="#fff" stroke-width="5" stroke-linecap="round" fill="none"/>
        <circle cx="34" cy="62" r="5" fill="#fff"/>
      </svg>
      <span class="card-topbar-name">مجلس عائلة العوامي</span>
    </div>

    <!-- Progress Dots -->
    <div class="card-progress">
      <span class="card-dot active" data-dot="0"></span>
      <span class="card-dot" data-dot="1"></span>
      <span class="card-dot" data-dot="2"></span>
      <span class="card-dot" data-dot="3"></span>
    </div>

    <!-- Step 0: Welcome -->
    <div class="card-step active" data-step="0">
      <svg class="card-welcome-logo" viewBox="0 0 80 80" fill="none">
        <path d="M55 12 C58 8,65 10,64 18 C63 26,54 30,50 38 C46 46,48 56,42 62 C36 68,26 66,24 58 C22 50,30 44,32 36" stroke="#fff" stroke-width="6" stroke-linecap="round" fill="none"/>
        <path d="M32 36 C28 44,20 46,20 54 C20 62,28 66,34 62" stroke="#fff" stroke-width="5" stroke-linecap="round" fill="none"/>
        <circle cx="34" cy="62" r="5" fill="#fff"/>
      </svg>
      <h1 class="card-welcome-title">عادت أعيادكم</h1>
      <p class="card-welcome-subtitle">اصنع بطاقة تهنئة شخصية وشاركها مع أحبابك</p>
      <p class="card-welcome-council">مجلس عائلة العوامي</p>
      <button class="card-btn-primary" id="card-start-btn">ابدأ</button>
      <p class="card-welcome-footer">جميع الحقوق محفوظة لمجلس عائلة العوامي &copy; 2026</p>
    </div>

    <!-- Step 1: Name + Shape -->
    <div class="card-step" data-step="1">
      <h2 class="card-step-title">اكتب اسمك</h2>
      <input type="text" class="card-input" id="card-name-input" placeholder="مثال: أحمد العوامي" autocomplete="off" maxlength="60">
      <p class="card-step-label" style="margin-top:24px;">اختر شكل البطاقة</p>
      <div class="card-shape-options">
        <button class="card-shape-btn selected" data-shape="square">
          <div class="card-shape-preview card-shape-preview-square"></div>
          <span class="card-shape-label">مربع</span>
          <span class="card-shape-desc">1080×1080 — سوشال ميديا</span>
        </button>
        <button class="card-shape-btn" data-shape="portrait">
          <div class="card-shape-preview card-shape-preview-portrait"></div>
          <span class="card-shape-label">طولي</span>
          <span class="card-shape-desc">1080×1920 — ستوري واتساب</span>
        </button>
      </div>
      <div class="card-btn-row">
        <button class="card-btn-secondary" id="card-prev-1">السابق</button>
        <button class="card-btn-primary" id="card-next-1" disabled>التالي</button>
      </div>
    </div>

    <!-- Step 2: Template Selection -->
    <div class="card-step" data-step="2">
      <h2 class="card-step-title">اختر التصميم</h2>
      <div class="card-carousel" id="card-carousel">
        <!-- Templates rendered by JS -->
      </div>
      <div class="card-btn-row">
        <button class="card-btn-secondary" id="card-prev-2">السابق</button>
        <button class="card-btn-primary" id="card-next-2">التالي</button>
      </div>
    </div>

    <!-- Step 3: Result -->
    <div class="card-step" data-step="3">
      <p class="card-result-msg">🎉 تهانينا! بطاقتك جاهزة</p>
      <div class="card-result-canvas-wrap">
        <canvas id="card-result-canvas"></canvas>
      </div>
      <div class="card-action-row">
        <button class="card-action-btn card-action-download" id="card-download">📥 حفظ الصورة</button>
        <button class="card-action-btn card-action-whatsapp" id="card-share">📱 واتساب</button>
      </div>
      <button class="card-back-link" id="card-back-edit">← تعديل البطاقة</button>
    </div>

  </div>
</section>

<script>
(function() {
  if (typeof initCard === 'function') {
    initCard();
  } else {
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof initCard === 'function') initCard();
    });
  }
})();
</script>
