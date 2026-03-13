// ═══════════════════════════════════════════════════════════════
// admin-members-auth.js — وظائف تفعيل حسابات الأعضاء وإعادة تعيين كلمة السر
// يُحمّل بعد admin-app.js — يعتمد على: apiFetch, toast, State, openModal, closeModal
// ═══════════════════════════════════════════════════════════════

// خريطة حالات الحسابات — { member_id: { awm_id, is_active, last_login, first_login } }
var _accountsMap = {};

/**
 * تحميل حالات حسابات الأعضاء من الـ API
 */
async function loadAccountsStatus() {
    try {
        var res = await apiFetch('/api/members.php?action=accounts_status');
        _accountsMap = (res && res.data) ? res.data : {};
    } catch (e) {
        _accountsMap = {};
    }
}

/**
 * جلب حالة حساب عضو معين
 */
function getMemberAccount(memberId) {
    return _accountsMap[memberId] || null;
}

/**
 * تفعيل حساب عضو جديد
 */
async function activateMemberAccount(memberId) {
    var acc = getMemberAccount(memberId);
    if (acc) {
        toast('العضو لديه حساب مسبق برقم ' + acc.awm_id, 'error');
        return;
    }

    var member = State.getMembers().find(function(m) { return m.id === memberId; });
    if (!member) { toast('العضو غير موجود', 'error'); return; }

    if (!confirm('هل تريد إنشاء حساب دخول لـ "' + member.name + '"؟')) return;

    try {
        var res = await apiFetch('/api/members.php?action=activate_account', {
            method: 'POST',
            body: JSON.stringify({ member_id: memberId })
        });

        // تحديث الخريطة المحلية
        _accountsMap[memberId] = {
            awm_id: res.awm_id,
            is_active: 0,
            last_login: null,
            first_login: 1
        };

        // عرض نافذة واتساب
        showWhatsAppModal(res);
        toast('تم إنشاء الحساب بنجاح — ' + res.awm_id);

        // تحديث جدول الأعضاء
        renderMembers();
    } catch (e) {
        toast(e.message || 'حدث خطأ أثناء التفعيل', 'error');
    }
}

/**
 * إعادة تعيين كلمة سر عضو
 */
async function resetMemberPassword(memberId) {
    var acc = getMemberAccount(memberId);
    if (!acc) {
        toast('العضو ليس لديه حساب — يجب التفعيل أولاً', 'error');
        return;
    }

    var member = State.getMembers().find(function(m) { return m.id === memberId; });
    if (!member) { toast('العضو غير موجود', 'error'); return; }

    if (!confirm('هل تريد إعادة تعيين كلمة السر لـ "' + member.name + '" (' + acc.awm_id + ')؟\n\nسيتم إلغاء كلمة السر الحالية وإنشاء رمز دخول جديد.')) return;

    try {
        var res = await apiFetch('/api/members.php?action=reset_password', {
            method: 'POST',
            body: JSON.stringify({ member_id: memberId })
        });

        // تحديث الخريطة
        _accountsMap[memberId].first_login = 1;

        // عرض نافذة واتساب
        showWhatsAppModal(res);
        toast('تم إعادة تعيين كلمة السر بنجاح');
    } catch (e) {
        toast(e.message || 'حدث خطأ أثناء إعادة التعيين', 'error');
    }
}

/**
 * عرض نافذة رسالة واتساب جاهزة للنسخ
 */
function showWhatsAppModal(data) {
    // إزالة نافذة سابقة إذا وجدت
    var old = document.getElementById('modal-wa-message');
    if (old) old.remove();

    var phoneClean = data.phone ? data.phone.replace(/\D/g, '').replace(/^0+/, '') : '';
    if (phoneClean && !/^966/.test(phoneClean)) phoneClean = '966' + phoneClean;
    var waLink = 'https://wa.me/' + phoneClean;
    var waLinkWithText = waLink + '?text=' + encodeURIComponent(data.wa_message);

    var html = '<div class="modal-overlay" id="modal-wa-message" onclick="if(event.target===this)closeWaModal()">'
        + '<div class="modal" style="max-width:520px">'
        + '<div class="modal-header">'
        + '<h3>رسالة واتساب — ' + escHtml(data.member_name) + '</h3>'
        + '<button class="modal-close" onclick="closeWaModal()">&times;</button>'
        + '</div>'
        + '<div class="modal-body">'
        + '<div style="margin-bottom:12px;display:flex;gap:8px;flex-wrap:wrap">'
        + '<span class="badge badge-success" style="font-size:13px">رقم العضوية: ' + escHtml(data.awm_id) + '</span>'
        + '<span class="badge badge-warning" style="font-size:13px">الرمز: ' + escHtml(data.temp_token) + '</span>'
        + '</div>'
        + '<label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px">نص الرسالة:</label>'
        + '<textarea id="wa-msg-text" readonly style="width:100%;min-height:180px;padding:12px;border:2px solid var(--border);border-radius:8px;font-family:inherit;font-size:13px;line-height:1.7;background:var(--bg);color:var(--text);resize:vertical;direction:rtl">' + escHtml(data.wa_message) + '</textarea>'
        + '<div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">'
        + '<button class="btn btn-primary" onclick="copyWaMessage()" style="flex:1;min-width:140px">📋 نسخ الرسالة</button>'
        + (data.phone ? '<a href="' + waLinkWithText + '" rel="noopener" class="btn btn-accent" style="flex:1;min-width:140px;text-align:center;text-decoration:none;background:#25D366;color:#fff">📱 إرسال واتساب</a>' : '')
        + '</div>'
        + '</div>'
        + '</div>'
        + '</div>';

    document.body.insertAdjacentHTML('beforeend', html);
    document.getElementById('modal-wa-message').style.display = 'flex';
}

function closeWaModal() {
    var el = document.getElementById('modal-wa-message');
    if (el) el.remove();
}

function copyWaMessage() {
    var ta = document.getElementById('wa-msg-text');
    if (!ta) return;
    ta.select();
    ta.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(ta.value).then(function() {
        toast('تم نسخ الرسالة');
    }).catch(function() {
        // fallback
        document.execCommand('copy');
        toast('تم نسخ الرسالة');
    });
}

/**
 * HTML escaping helper
 */
function escHtml(str) {
    if (!str) return '';
    var d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

/**
 * إرجاع أزرار الحساب لصف عضو في الجدول
 */
function memberAuthButtons(memberId) {
    var acc = getMemberAccount(memberId);

    if (!acc) {
        // لا يوجد حساب — زر تفعيل
        return '<button class="btn btn-outline btn-xs" onclick="activateMemberAccount(\'' + memberId + '\')" title="تفعيل حساب">🔑</button>';
    }

    // حساب موجود — زر إعادة تعيين
    return '<button class="btn btn-outline btn-xs" onclick="resetMemberPassword(\'' + memberId + '\')" title="إعادة تعيين كلمة السر" style="color:var(--accent)">🔄</button>';
}

/**
 * إرجاع شارة حالة الحساب
 */
function memberAccountBadge(memberId) {
    var acc = getMemberAccount(memberId);

    if (!acc) {
        return '<span class="badge badge-gray" style="font-size:10px">بدون حساب</span>';
    }

    if (acc.is_active && acc.last_login) {
        return '<span class="badge badge-success" style="font-size:10px" title="آخر دخول: ' + acc.last_login + '">مُفعَّل</span>';
    }

    if (acc.is_active) {
        return '<span class="badge badge-success" style="font-size:10px">مُفعَّل — لم يدخل</span>';
    }

    return '<span class="badge badge-warning" style="font-size:10px">بانتظار التفعيل</span>';
}

// ── تحميل حالات الحسابات عند بدء لوحة التحكم ──
(function() {
    // تحميل تلقائي بعد تهيئة لوحة التحكم
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { loadAccountsStatus(); });
    } else {
        loadAccountsStatus();
    }
})();
