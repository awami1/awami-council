<!-- ============================================================
     modals.php — جميع النوافذ المنبثقة للوحة التحكم
     ============================================================ -->

<!-- MEMBER MODAL -->
<div class="modal-overlay" id="modal-member" role="dialog" aria-modal="true" aria-labelledby="modal-member-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="modal-member-title">+ اضافة عضو</div><button class="modal-close" onclick="closeModal('modal-member')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="mm-id">
      <div class="form-grid">
        <div class="form-group"><label class="form-label" for="mm-name">الاسم الكامل *</label><input class="form-control" id="mm-name" placeholder="الاسم" required></div>
        <div class="form-group"><label class="form-label" for="mm-phone">رقم الجوال</label><input class="form-control" id="mm-phone" placeholder="05xxxxxxxx" pattern="05\d{8}" inputmode="tel"></div>
        <div class="form-group"><label class="form-label" for="mm-join">تاريخ الانضمام</label><input class="form-control" id="mm-join" type="date"></div>
        <div class="form-group"><label class="form-label" for="mm-status">الحالة</label><select class="form-control" id="mm-status"><option>مشترك</option><option>منقطع</option><option>غير مشترك</option></select></div>
      </div>
      <div class="form-group"><label class="form-label" for="mm-notes">ملاحظات</label><textarea class="form-control" id="mm-notes" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-member')">إلغاء</button><button class="btn btn-primary" id="btn-save-member" onclick="saveMember()">حفظ</button></div>
  </div>
</div>

<!-- PAYMENT MODAL -->
<div class="modal-overlay" id="modal-pay" role="dialog" aria-modal="true" aria-labelledby="modal-pay-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="modal-pay-title">💳 تسجيل دفعة</div><button class="modal-close" onclick="closeModal('modal-pay')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="pay-mid">
      <div class="form-group"><label class="form-label" for="pay-mname">العضو</label><input class="form-control" id="pay-mname" disabled></div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label" for="pay-amount">المبلغ (ريال)</label><input class="form-control" id="pay-amount" type="number" min="0"></div>
        <div class="form-group"><label class="form-label" for="pay-date">التاريخ</label><input class="form-control" id="pay-date" type="date"></div>
        <div class="form-group"><label class="form-label" for="pay-method">الطريقة</label><select class="form-control" id="pay-method"><option>تحويل بنكي</option><option>نقدي</option><option>STCPay</option></select></div>
        <div class="form-group"><label class="form-label" for="pay-status">الحالة</label><select class="form-control" id="pay-status"><option>مدفوع</option><option>لم يدفع</option></select></div>
      </div>
      <div class="form-group"><label class="form-label" for="pay-notes">ملاحظات</label><textarea class="form-control" id="pay-notes" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-pay')">إلغاء</button><button class="btn btn-primary" id="btn-save-payment" onclick="savePayment()">تسجيل</button></div>
  </div>
</div>

<!-- PERIOD MODAL -->
<div class="modal-overlay" id="modal-period" role="dialog" aria-modal="true" aria-labelledby="modal-period-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="modal-period-title">🗓 دورة دفع جديدة</div><button class="modal-close" onclick="closeModal('modal-period')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group"><label class="form-label" for="pd-name">اسم الدورة *</label><input class="form-control" id="pd-name" placeholder="الدورة الأولى 2025" required></div>
        <div class="form-group"><label class="form-label" for="pd-amount">مبلغ الرسوم (ريال) *</label><input class="form-control" id="pd-amount" type="number" placeholder="400" min="0" required></div>
        <div class="form-group"><label class="form-label" for="pd-start">تاريخ البدء</label><input class="form-control" id="pd-start" type="date"></div>
        <div class="form-group"><label class="form-label" for="pd-end">تاريخ الانتهاء</label><input class="form-control" id="pd-end" type="date"></div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-period')">إلغاء</button><button class="btn btn-primary" id="btn-create-period" onclick="createPeriod()">إنشاء</button></div>
  </div>
</div>

<!-- TRANSACTION MODAL -->
<div class="modal-overlay" id="modal-tx" role="dialog" aria-modal="true" aria-labelledby="modal-tx-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="modal-tx-title">💵 إضافة معاملة مالية</div><button class="modal-close" onclick="closeModal('modal-tx')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group"><label class="form-label" for="tx-type">النوع</label><select class="form-control" id="tx-type"><option>إيراد</option><option>مصروف</option></select></div>
        <div class="form-group"><label class="form-label" for="tx-amount">المبلغ (ريال) *</label><input class="form-control" id="tx-amount" type="number" min="0"></div>
        <div class="form-group"><label class="form-label" for="tx-date">التاريخ</label><input class="form-control" id="tx-date" type="date"></div>
        <div class="form-group"><label class="form-label">الفئة</label><select class="form-control" id="tx-cat"><option>رسوم الأعضاء</option><option>رحلة العمرة</option><option>غداء العيد</option><option>رحلة ترفيهية</option><option>مسابقة</option><option>مصاريف إدارية</option><option>استثمار</option><option>عقيقة جماعية</option><option>تبرعات</option><option>أخرى</option></select></div>
        <div class="form-group" style="grid-column:1/-1"><label class="form-label">اللجنة</label><select class="form-control" id="tx-committee"><option value="">عام</option></select></div>
      </div>
      <div class="form-group"><label class="form-label">الوصف *</label><input class="form-control" id="tx-desc" placeholder="وصف المعاملة"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-tx')">إلغاء</button><button class="btn btn-primary" id="btn-add-tx" onclick="addTransaction()">إضافة</button></div>
  </div>
</div>

<!-- EVENT MODAL -->
<div class="modal-overlay" id="modal-event" role="dialog" aria-modal="true" aria-labelledby="event-modal-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="event-modal-title">🎉 إضافة فعالية</div><button class="modal-close" onclick="closeModal('modal-event')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="ev-edit-id">
      <div class="form-group"><label class="form-label">اسم الفعالية *</label><input class="form-control" id="ev-name"></div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">اللجنة المنظِّمة</label><select class="form-control" id="ev-committee"><option value="">غير محدد</option></select></div>
        <div class="form-group"><label class="form-label">الحالة</label><select class="form-control" id="ev-status"><option>قادم</option><option>جاري</option><option>مكتمل</option><option>ملغي</option></select></div>
        <div class="form-group"><label class="form-label">التاريخ</label><input class="form-control" id="ev-date" type="date"></div>
        <div class="form-group"><label class="form-label">الميزانية (ريال)</label><input class="form-control" id="ev-budget" type="number"></div>
        <div class="form-group"><label class="form-label">المشاركون</label><input class="form-control" id="ev-participants" type="number"></div>
        <div class="form-group"><label class="form-label">المسؤول</label><input class="form-control" id="ev-lead"></div>
      </div>
      <div class="form-group">
        <label class="form-label">📸 صور الفعالية</label>
        <input type="file" class="form-control" id="ev-images" accept="image/*" multiple style="padding:6px">
        <div id="ev-images-preview" style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap"></div>
      </div>
      <div class="form-group"><label class="form-label">ملاحظات</label><textarea class="form-control" id="ev-notes" rows="2"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-event')">إلغاء</button>
      <button class="btn btn-danger" id="ev-delete-btn" onclick="deleteEventFromModal()" style="display:none">🗑 حذف</button>
      <button class="btn btn-primary" id="ev-save-btn" onclick="saveEvent()">حفظ</button>
    </div>
  </div>
</div>

<!-- COMMITTEE DETAIL MODAL -->
<div class="modal-overlay" id="modal-committee-detail" role="dialog" aria-modal="true" aria-labelledby="cdetail-title">
  <div class="modal modal-wide">
    <div class="modal-header"><div class="modal-title" id="cdetail-title">تفاصيل اللجنة</div><button class="modal-close" onclick="closeModal('modal-committee-detail')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body" id="cdetail-body"></div>
  </div>
</div>

<!-- WHATSAPP MODAL -->
<div class="modal-overlay" id="modal-whatsapp" role="dialog" aria-modal="true" aria-labelledby="modal-whatsapp-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="modal-whatsapp-title">📱 إشعارات واتساب للمتأخرين</div><button class="modal-close" onclick="closeModal('modal-whatsapp')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <div style="background:#f0fdf4;border-radius:10px;padding:12px;margin-bottom:14px;border:1px solid #86efac"><div style="font-size:12px;color:#166534">💡 اضغط على زر "إرسال" لكل عضو لفتح واتساب برسالة جاهزة</div></div>
      <div id="whatsapp-list"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-whatsapp')">إغلاق</button></div>
  </div>
</div>

<!-- CONFIRM MODAL -->
<div class="modal-overlay" id="modal-confirm" role="alertdialog" aria-modal="true" aria-labelledby="modal-confirm-title">
  <div class="modal" style="max-width:360px">
    <div class="modal-header"><div class="modal-title" id="modal-confirm-title">⚠ تأكيد</div><button class="modal-close" onclick="closeModal('modal-confirm')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body"><p style="color:var(--text-muted);font-size:14px" id="confirm-msg"></p></div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-confirm')">إلغاء</button><button class="btn btn-danger" id="confirm-btn">تأكيد</button></div>
  </div>
</div>

<!-- TREE MEMBER MODAL -->
<div class="modal-overlay" id="modal-tree-member" role="dialog" aria-modal="true" aria-labelledby="tree-member-modal-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="tree-member-modal-title">🌳 إضافة شخص للشجرة</div><button class="modal-close" onclick="closeModal('modal-tree-member')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="ftm-id">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">الاسم الكامل *</label><input class="form-control" id="ftm-name" placeholder="مثال: محمد علي العوامي"></div>
        <div class="form-group">
          <label class="form-label">الأب / الأم (الوالد في الشجرة)</label>
          <select class="form-control" id="ftm-parent">
            <option value="">-- بدون (جذر الشجرة) --</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">الجنس</label>
          <div style="display:flex;gap:16px;padding:8px 0">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer"><input type="radio" name="ftm-gender" value="ذكر" checked> ذكر</label>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer"><input type="radio" name="ftm-gender" value="أنثى"> أنثى</label>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">على قيد الحياة</label>
          <div style="padding:8px 0"><label style="display:flex;align-items:center;gap:6px;cursor:pointer"><input type="checkbox" id="ftm-alive" checked> نعم</label></div>
        </div>
        <div class="form-group"><label class="form-label">اسم الزوج / الزوجة</label><input class="form-control" id="ftm-spouse" placeholder="اختياري"></div>
        <div class="form-group"><label class="form-label">ترتيب العرض</label><input class="form-control" id="ftm-sort" type="number" value="0" min="0"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-tree-member')">إلغاء</button>
      <button class="btn btn-danger" id="ftm-delete-btn" onclick="deleteTreeMemberConfirm()" style="display:none">🗑 حذف</button>
      <button class="btn btn-primary" onclick="saveTreeMember()">💾 حفظ</button>
    </div>
  </div>
</div>

<!-- ADD BRANCH MODAL -->
<div class="modal-overlay" id="modal-add-branch" role="dialog" aria-modal="true" aria-labelledby="branch-modal-title">
  <div class="modal modal-wide">
    <div class="modal-header"><div class="modal-title" id="branch-modal-title">🌳 إضافة فرع عائلي</div><button class="modal-close" onclick="closeModal('modal-add-branch')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="branch-id">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">اسم الفرع *</label><input class="form-control" id="branch-name" placeholder="مثال: آل محمد"></div>
        <div class="form-group"><label class="form-label">رب العائلة / المؤسس</label><input class="form-control" id="branch-head" placeholder="الاسم"></div>
        <div class="form-group"><label class="form-label">اللون</label><input class="form-control" id="branch-color" type="color" value="#47915C"></div>
      </div>
      <div class="form-group">
        <label class="form-label">أفراد الفرع (سطر لكل فرد)</label>
        <textarea class="form-control" id="branch-members" rows="8" placeholder="محمد&#10;علي&#10;أحمد&#10;..."></textarea>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px">💡 اكتب كل اسم في سطر منفصل</div>
      </div>
      <div class="form-group"><label class="form-label">ملاحظات</label><textarea class="form-control" id="branch-notes" rows="2"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-add-branch')">إلغاء</button>
      <button class="btn btn-danger" id="branch-delete-btn" onclick="deleteBranchConfirm()" style="display:none">🗑 حذف</button>
      <button class="btn btn-primary" onclick="saveBranch()">💾 حفظ</button>
    </div>
  </div>
</div>

<!-- BACKUPS MODAL -->
<div class="modal-overlay" id="modal-backups" role="dialog" aria-modal="true" aria-labelledby="modal-backups-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="modal-backups-title">📂 النسخ الاحتياطية التلقائية</div><button class="modal-close" onclick="closeModal('modal-backups')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:12px;margin-bottom:14px">
        <div style="font-size:12px;color:#166534">💡 يتم حفظ نسخة احتياطية تلقائياً كل يوم (آخر 7 أيام)</div>
      </div>
      <div id="backup-list"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-backups')">إغلاق</button></div>
  </div>
</div>

<!-- IMPORT EXCEL MODAL -->
<div class="modal-overlay" id="modal-import-excel" role="dialog" aria-modal="true" aria-labelledby="modal-import-title">
  <div class="modal" style="max-width:700px">
    <div class="modal-header"><div class="modal-title" id="modal-import-title">📥 استيراد أعضاء من Excel</div><button class="modal-close" onclick="closeModal('modal-import-excel')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <div style="background:#fef9c3;border:1px solid #fde047;border-radius:10px;padding:14px;margin-bottom:16px">
        <div style="font-size:13px;color:#854d0e;font-weight:600;margin-bottom:6px">📊 الأعمدة المطلوبة في Excel:</div>
        <div style="font-size:12px;color:#854d0e">الاسم * الجوال (اختياري) * الحالة (اختياري)</div>
      </div>
      <input type="file" id="excel-import-input" accept=".xlsx,.xls,.csv" style="display:none">
      <button class="btn btn-primary" style="width:100%" onclick="document.getElementById('excel-import-input').click()">📁 اختر ملف Excel</button>
      <div id="import-result" style="margin-top:16px;display:none"></div>
      <div id="import-progress" style="margin-top:12px;display:none"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="cancelImport();closeModal('modal-import-excel')">إغلاق</button>
      <button class="btn btn-primary" id="btn-import-save" onclick="saveImport()" style="display:none">💾 حفظ الكل</button>
    </div>
  </div>
</div>

<!-- ADD / EDIT MEDIA MODAL -->
<div class="modal-overlay" id="modal-add-media" role="dialog" aria-modal="true" aria-labelledby="modal-media-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="modal-media-title">📷 إضافة ميديا</div><button class="modal-close" onclick="closeModal('modal-add-media')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="media-edit-id">
      <div class="form-group"><label class="form-label">العنوان *</label><input class="form-control" id="media-title" required></div>
      <div class="form-group"><label class="form-label">النوع *</label>
        <select class="form-control" id="media-type">
          <option value="images">📷 صورة</option>
          <option value="videos">🎥 فيديو</option>
          <option value="youtube">▶️ يوتيوب</option>
          <option value="events">🎉 فعالية</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">رابط الصورة/الفيديو *</label>
        <input class="form-control" id="media-url" placeholder="https://...">
        <div id="media-url-preview" style="display:none;margin-top:8px;border-radius:8px;overflow:hidden">
          <img id="media-url-preview-img" src="" alt="" style="width:100%;height:160px;object-fit:cover;display:none;border-radius:8px">
          <div id="media-url-preview-yt" style="display:none;background:#111;height:80px;align-items:center;justify-content:center;color:#fff;font-size:14px;font-weight:700;border-radius:8px;gap:8px">▶️ رابط يوتيوب</div>
        </div>
      </div>
      <div class="form-group"><label class="form-label">التاريخ</label><input class="form-control" id="media-date" type="date"></div>
      <div class="form-group"><label class="form-label">الوسوم (افصل بفاصلة)</label><input class="form-control" id="media-tags" placeholder="مثال: فعالية، رحلة، اجتماع"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-add-media')">إلغاء</button><button class="btn btn-primary" onclick="saveMedia()">💾 حفظ</button></div>
  </div>
</div>

<!-- BRANCH DETAIL MODAL -->
<div class="modal-overlay" id="modal-branch-detail" role="dialog" aria-modal="true" aria-labelledby="branch-detail-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="branch-detail-title">تفاصيل الفرع</div><button class="modal-close" onclick="closeModal('modal-branch-detail')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body" id="branch-detail-body"></div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-branch-detail')">إغلاق</button></div>
  </div>
</div>

<!-- POSITION MODAL -->
<div class="modal-overlay" id="modal-position" role="dialog" aria-modal="true" aria-labelledby="position-modal-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="position-modal-title">👑 إضافة منصب</div><button class="modal-close" onclick="closeModal('modal-position')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="position-index">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">المنصب *</label><input class="form-control" id="position-role" placeholder="مثال: الرئيس"></div>
        <div class="form-group"><label class="form-label">الأيقونة</label><input class="form-control" id="position-icon" placeholder="👑"></div>
      </div>
      <div class="form-group"><label class="form-label">الاسم / الأسماء *</label><input class="form-control" id="position-name" placeholder="مثال: منصور علي"></div>
      <div class="form-group"><label class="form-label">المهام (سطر لكل مهمة)</label><textarea class="form-control" id="position-tasks" rows="4" placeholder="الإشراف العام&#10;إدارة الاجتماعات"></textarea></div>
      <div class="form-group"><label class="form-label">النوع</label><select class="form-control" id="position-type"><option value="">عادي</option><option value="president">رئيس</option><option value="advisory">استشاري</option></select></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-position')">إلغاء</button>
      <button class="btn btn-danger" id="position-delete-btn" onclick="deletePosition()" style="display:none">🗑 حذف</button>
      <button class="btn btn-primary" onclick="savePosition()">💾 حفظ</button>
    </div>
  </div>
</div>

<!-- VALUE MODAL -->
<div class="modal-overlay" id="modal-value" role="dialog" aria-modal="true" aria-labelledby="value-modal-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="value-modal-title">💎 إضافة قيمة</div><button class="modal-close" onclick="closeModal('modal-value')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="value-index">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">الأيقونة *</label><input class="form-control" id="value-icon" placeholder="🤝"></div>
        <div class="form-group"><label class="form-label">العنوان *</label><input class="form-control" id="value-title" placeholder="مثال: الترابط الأسري"></div>
      </div>
      <div class="form-group"><label class="form-label">الوصف</label><textarea class="form-control" id="value-desc" rows="3"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-value')">إلغاء</button>
      <button class="btn btn-danger" id="value-delete-btn" onclick="deleteValue()" style="display:none">🗑 حذف</button>
      <button class="btn btn-primary" onclick="saveValue()">💾 حفظ</button>
    </div>
  </div>
</div>

<!-- COMMITTEE ADD/EDIT MODAL -->
<div class="modal-overlay" id="modal-add-committee" role="dialog" aria-modal="true" aria-labelledby="committee-modal-title">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="committee-modal-title">🏛️ إضافة لجنة</div><button class="modal-close" onclick="closeModal('modal-add-committee')" aria-label="إغلاق">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="cm-id">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">اسم اللجنة *</label><input class="form-control" id="cm-name" placeholder="مثال: لجنة الرحلات"></div>
        <div class="form-group"><label class="form-label">الأيقونة</label><input class="form-control" id="cm-icon" placeholder="🏛️" style="font-size:20px;text-align:center"></div>
        <div class="form-group"><label class="form-label">اللون</label><input class="form-control" id="cm-color1" type="color" value="#47915C"></div>
        <div class="form-group"><label class="form-label">اللون الثاني</label><input class="form-control" id="cm-color2" type="color" value="#2d6b40"></div>
      </div>
      <div class="form-group"><label class="form-label">الوصف</label><textarea class="form-control" id="cm-desc" rows="2" placeholder="وصف مختصر لعمل اللجنة"></textarea></div>
      <div class="form-group"><label class="form-label">عدد الأعضاء</label><input class="form-control" id="cm-members-count" type="number" min="0" placeholder="0" style="max-width:140px"></div>
      <div class="form-group" style="display:flex;align-items:center;gap:10px">
        <input type="checkbox" id="cm-advisory">
        <label class="form-label" for="cm-advisory" style="margin:0">لجنة استشارية</label>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-add-committee')">إلغاء</button>
      <button class="btn btn-danger" id="cm-delete-btn" onclick="deleteCommitteeFromModal()" style="display:none">🗑 حذف</button>
      <button class="btn btn-primary" id="btn-save-committee" onclick="saveCommittee()">💾 حفظ</button>
    </div>
  </div>
</div>
