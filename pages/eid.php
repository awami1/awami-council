<?php
/**
 * eid.php — مولد بطاقة تهنئة العيد
 */
?>

<section id="eid-greeting">
  <div class="section-header">
    <div class="section-badge">بطاقات تهنئة</div>
    <h2 class="section-title">&#127769; تهنئة العيد</h2>
    <p class="section-subtitle">اصنع بطاقة تهنئة شخصية بإسمك</p>
  </div>
  <div class="eid-section">
    <div class="eid-wrapper">
      <label class="eid-label">&#x270D;&#xFE0F; اكتب اسمك:</label>
      <input type="text" id="eid-name" class="eid-input" placeholder="مثال: أحمد محمد العوامي" oninput="updateEidPreview()">

      <div class="eid-live-preview">
        <canvas id="eid-preview-canvas"></canvas>
      </div>

      <div class="eid-controls">
        <div>
          <label class="eid-label">&#x1F3A8; نوع الخط:</label>
          <select id="eid-font-weight" class="eid-input" onchange="updateEidPreview()">
            <option value="normal">السعودي — عادي</option>
            <option value="bold" selected>السعودي — عريض (Bold)</option>
          </select>
        </div>
        <div>
          <label class="eid-label">&#x1F4CF; حجم الخط: <span id="eid-font-size-label">70</span>px</label>
          <input type="range" id="eid-font-size" class="eid-range" min="40" max="120" value="70" oninput="updateFontSize()">
          <div class="eid-range-labels"><span>صغير</span><span>متوسط</span><span>كبير</span></div>
        </div>
      </div>

      <button class="eid-btn" id="eid-gen">&#x2728; إنشاء البطاقة</button>
      <div class="eid-preview" id="eid-preview">
        <div class="eid-canvas-wrap"><canvas id="eid-canvas"></canvas></div>
        <div class="eid-actions">
          <button class="eid-action-btn eid-dl" id="eid-download">&#x1F4E5; تحميل البطاقة</button>
          <button class="eid-action-btn eid-sh" id="eid-share">&#x1F4F1; مشاركة واتساب</button>
        </div>
      </div>
    </div>
    <p class="eid-hint">&#x1F4A1; ستحصل على بطاقة بجودة عالية جاهزة للطباعة والمشاركة</p>
  </div>
</section>

<script>
(function() {
  if (typeof initEid === 'function') {
    initEid();
  } else {
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof initEid === 'function') initEid();
    });
  }
})();
</script>
