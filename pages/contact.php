<?php
/**
 * contact.php — صفحة تواصل معنا
 */
$ws = getWS();
?>

<section>
  <div class="section-header">
    <div class="section-badge">نسعد بتواصلكم</div>
    <h2 class="section-title">تواصل معنا</h2>
    <p class="section-subtitle">لأي استفسار أو اقتراح، لا تتردد في التواصل معنا</p>
  </div>

  <div style="max-width:700px;margin:0 auto">
    <div style="background:var(--surface);border-radius:var(--radius-lg);padding:36px;border:1px solid var(--border);box-shadow:var(--shadow-sm)">
      <form id="contact-form" onsubmit="return handleContactSubmit(event)">
        <div style="margin-bottom:20px">
          <label style="display:block;font-weight:700;margin-bottom:8px;color:var(--green-dark);font-size:14px">الاسم الكامل *</label>
          <input type="text" name="name" required placeholder="مثال: أحمد محمد العوامي" style="width:100%;padding:14px 18px;border:2px solid var(--border);border-radius:var(--radius);font-size:15px;font-family:inherit;background:var(--bg);color:var(--text);transition:border-color .25s" onfocus="this.style.borderColor='var(--green)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <div style="margin-bottom:20px">
          <label style="display:block;font-weight:700;margin-bottom:8px;color:var(--green-dark);font-size:14px">رقم الجوال</label>
          <input type="tel" name="phone" placeholder="05XXXXXXXX" dir="ltr" style="width:100%;padding:14px 18px;border:2px solid var(--border);border-radius:var(--radius);font-size:15px;font-family:inherit;background:var(--bg);color:var(--text);transition:border-color .25s;text-align:right" onfocus="this.style.borderColor='var(--green)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <div style="margin-bottom:20px">
          <label style="display:block;font-weight:700;margin-bottom:8px;color:var(--green-dark);font-size:14px">الموضوع *</label>
          <input type="text" name="subject" required placeholder="موضوع الرسالة" style="width:100%;padding:14px 18px;border:2px solid var(--border);border-radius:var(--radius);font-size:15px;font-family:inherit;background:var(--bg);color:var(--text);transition:border-color .25s" onfocus="this.style.borderColor='var(--green)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <div style="margin-bottom:24px">
          <label style="display:block;font-weight:700;margin-bottom:8px;color:var(--green-dark);font-size:14px">الرسالة *</label>
          <textarea name="message" required rows="5" placeholder="اكتب رسالتك هنا..." style="width:100%;padding:14px 18px;border:2px solid var(--border);border-radius:var(--radius);font-size:15px;font-family:inherit;background:var(--bg);color:var(--text);resize:vertical;transition:border-color .25s" onfocus="this.style.borderColor='var(--green)'" onblur="this.style.borderColor='var(--border)'"></textarea>
        </div>
        <button type="submit" class="cta-btn cta-primary" style="width:100%;justify-content:center;font-size:16px;padding:16px">&#9993; إرسال الرسالة</button>
      </form>
      <div id="contact-status" style="display:none;margin-top:20px;padding:16px;border-radius:var(--radius);text-align:center;font-weight:700"></div>
    </div>

    <?php if (!empty($ws['contact']['whatsapp'])): ?>
    <div style="text-align:center;margin-top:32px">
      <p style="color:var(--text-muted);margin-bottom:16px;font-size:14px">أو تواصل معنا مباشرة عبر واتساب</p>
      <a href="https://wa.me/<?= esc(preg_replace('/\D/', '', $ws['contact']['whatsapp'])) ?>" target="_blank" rel="noopener" class="cta-btn cta-primary" style="display:inline-flex;background:#25D366;box-shadow:0 4px 12px rgba(37,211,102,.3)">
        &#128241; واتساب
      </a>
    </div>
    <?php endif; ?>
  </div>
</section>

<script>
function handleContactSubmit(e) {
  e.preventDefault();
  var form = e.target;
  var status = document.getElementById('contact-status');
  var data = {
    name: form.name.value.trim(),
    phone: form.phone.value.trim(),
    subject: form.subject.value.trim(),
    message: form.message.value.trim()
  };
  if (!data.name || !data.subject || !data.message) return false;

  status.style.display = 'block';
  status.style.background = 'var(--green-light)';
  status.style.color = 'var(--green-dark)';
  status.textContent = 'شكراً لتواصلك! تم استلام رسالتك بنجاح.';
  form.reset();
  return false;
}
</script>
