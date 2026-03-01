<?php
/**
 * eid.php — صفحة المناسبات / تهنئة العيد (ديناميكية)
 * تجلب البيانات من API وتعرض المحتوى حسب إعدادات لوحة التحكم
 */
?>

<section id="occasion-page">
  <!-- حالة التحميل -->
  <div id="occasion-loading" style="text-align:center;padding:60px 20px">
    <div style="font-size:48px;margin-bottom:16px">🎉</div>
    <div style="font-size:16px;color:var(--text-muted)">جاري تحميل صفحة المناسبة...</div>
  </div>

  <!-- رسالة الصفحة مخفية -->
  <div id="occasion-inactive" style="display:none;text-align:center;padding:80px 20px">
    <div style="font-size:64px;margin-bottom:20px">🌙</div>
    <h2 style="font-family:var(--font-heading);font-size:28px;color:var(--green-dark);margin-bottom:12px">لا توجد مناسبة حالياً</h2>
    <p style="color:var(--text-muted);font-size:16px;margin-bottom:24px">تابعونا لمعرفة المناسبات القادمة</p>
    <a href="/" class="eid-btn" style="display:inline-block;text-decoration:none">العودة للرئيسية</a>
  </div>

  <!-- المحتوى الديناميكي -->
  <div id="occasion-content" style="display:none">
    <div class="section-header">
      <div class="section-badge">بطاقات تهنئة</div>
      <h2 class="section-title" id="occasion-title">🎉 تهنئة المناسبة</h2>
      <p class="section-subtitle" id="occasion-message">اصنع بطاقة تهنئة شخصية بإسمك</p>
    </div>

    <div class="eid-section">
      <div class="eid-wrapper">

        <!-- عرض الصور -->
        <div id="occasion-images-display"></div>

        <!-- حقل الاسم (يظهر فقط إذا مفعّل) -->
        <div id="occasion-name-section" style="display:none">
          <label class="eid-label">&#x270D;&#xFE0F; اكتب اسمك:</label>
          <input type="text" id="occasion-name-input" class="eid-input" placeholder="مثال: أحمد محمد العوامي" oninput="occasionLivePreview()">

          <div class="eid-live-preview">
            <canvas id="occasion-canvas-preview"></canvas>
          </div>

          <button class="eid-btn" id="occasion-gen-btn">&#x2728; إنشاء البطاقة</button>

          <div class="eid-preview" id="occasion-result" style="display:none">
            <div class="eid-canvas-wrap"><canvas id="occasion-canvas-final"></canvas></div>
            <div class="eid-actions">
              <button class="eid-action-btn eid-dl" id="occasion-download-btn">&#x1F4E5; تحميل البطاقة</button>
              <button class="eid-action-btn eid-sh" id="occasion-share-btn">&#x1F4F1; مشاركة</button>
            </div>
          </div>

          <p class="eid-hint">&#x1F4A1; ستحصل على بطاقة بجودة عالية جاهزة للطباعة والمشاركة</p>
        </div>

      </div>
    </div>
  </div>
</section>

<script>
(function() {
  if (typeof initOccasionPage === 'function') {
    initOccasionPage();
  } else {
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof initOccasionPage === 'function') initOccasionPage();
    });
  }
})();
</script>
