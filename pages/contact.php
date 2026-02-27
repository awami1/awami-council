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

<!-- الأسئلة الشائعة -->
<section>
  <div class="section-header">
    <div class="section-badge">نساعدك</div>
    <h2 class="section-title">الأسئلة الشائعة</h2>
    <p class="section-subtitle">إجابات على أكثر الأسئلة تكراراً</p>
  </div>
  <div class="accordion">
    <div class="accordion-item animate-in">
      <button class="accordion-header">
        <span>كيف يمكنني الانضمام لمجلس عائلة العوامي؟</span>
        <span class="accordion-icon">+</span>
      </button>
      <div class="accordion-body">
        <div class="accordion-body-inner">يمكنك التواصل مع أحد أعضاء المجلس أو إرسال رسالة عبر نموذج التواصل أعلاه أو عبر الواتساب. سيتم التواصل معك وإرشادك لإجراءات التسجيل.</div>
      </div>
    </div>
    <div class="accordion-item animate-in">
      <button class="accordion-header">
        <span>ما هي اشتراكات العضوية؟</span>
        <span class="accordion-icon">+</span>
      </button>
      <div class="accordion-body">
        <div class="accordion-body-inner">تُحدد قيمة الاشتراك السنوي من قبل الهيئة الإدارية في الاجتماع العمومي. يمكنك الاستفسار عن التفاصيل من خلال التواصل المباشر مع أمين الصندوق.</div>
      </div>
    </div>
    <div class="accordion-item animate-in">
      <button class="accordion-header">
        <span>كيف أطلع على شجرة العائلة؟</span>
        <span class="accordion-icon">+</span>
      </button>
      <div class="accordion-body">
        <div class="accordion-body-inner">يمكنك زيارة صفحة <a href="/tree" style="color:var(--green);font-weight:700">شجرة العائلة</a> للاطلاع على الأفرع الرئيسية. كما يمكنك التواصل مع لجنة التوثيق للحصول على مزيد من التفاصيل.</div>
      </div>
    </div>
    <div class="accordion-item animate-in">
      <button class="accordion-header">
        <span>كيف يمكنني المشاركة في الفعاليات والأنشطة؟</span>
        <span class="accordion-icon">+</span>
      </button>
      <div class="accordion-body">
        <div class="accordion-body-inner">يتم الإعلان عن الفعاليات والأنشطة عبر الموقع وقنوات التواصل الخاصة بالمجلس. يمكنك متابعة صفحة الأخبار أو التسجيل في القائمة البريدية للحصول على التحديثات.</div>
      </div>
    </div>
    <div class="accordion-item animate-in">
      <button class="accordion-header">
        <span>هل يمكنني اقتراح فعالية أو نشاط جديد؟</span>
        <span class="accordion-icon">+</span>
      </button>
      <div class="accordion-body">
        <div class="accordion-body-inner">بالطبع! نرحب بجميع الاقتراحات والأفكار البناءة. يمكنك إرسال اقتراحك عبر نموذج التواصل أو مباشرة عبر الواتساب وسيتم دراسته من قبل اللجنة المختصة.</div>
      </div>
    </div>
  </div>
</section>

<script>
function handleContactSubmit(e) {
  e.preventDefault();
  var form = e.target;
  var btn = form.querySelector('button[type="submit"]');
  var status = document.getElementById('contact-status');
  var data = {
    name: form.name.value.trim(),
    phone: form.phone.value.trim(),
    subject: form.subject.value.trim(),
    message: form.message.value.trim()
  };
  if (!data.name || !data.message) return false;

  btn.disabled = true;
  btn.textContent = 'جاري الإرسال...';

  fetch('/api/messages.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
  .then(function(res) { return res.json(); })
  .then(function(result) {
    if (result.error) throw new Error(result.error);
    status.style.display = 'block';
    status.style.background = 'var(--green-light)';
    status.style.color = 'var(--green-dark)';
    status.textContent = result.message || 'شكراً لتواصلك! تم استلام رسالتك بنجاح.';
    form.reset();
  })
  .catch(function(err) {
    status.style.display = 'block';
    status.style.background = '#fee2e2';
    status.style.color = '#991b1b';
    status.textContent = err.message || 'حدث خطأ أثناء الإرسال. حاول مرة أخرى.';
  })
  .finally(function() {
    btn.disabled = false;
    btn.innerHTML = '&#9993; إرسال الرسالة';
  });
  return false;
}
</script>
