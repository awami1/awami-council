#!/usr/bin/env bash
# check-page-sync.sh — يتحقق من تزامن الصفحات بين router.php و ajax-nav.js
# يشتغل تلقائياً بعد كل تعديل ملف (PostToolUse hook)

set -euo pipefail

ROUTER="router.php"
AJAXNAV="public/js/ajax-nav.js"

# تحقق من وجود الملفات
if [[ ! -f "$ROUTER" ]] || [[ ! -f "$AJAXNAV" ]]; then
    exit 0
fi

# استخراج المسارات من router.php (مثل '/news', '/events')
router_paths=$(grep -oP "^\s*'(/[a-z-]+)'" "$ROUTER" | tr -d "' " | sort)

# استخراج المسارات من ajax-nav.js pageScripts (مثل '/news', '/events')
ajax_paths=$(grep -oP "^\s*'(/[a-z-]+)'" "$AJAXNAV" | tr -d "' " | sort)

# مقارنة — ابحث عن مسارات في router.php غير موجودة في ajax-nav.js
missing=""
for path in $router_paths; do
    # تجاهل المسار الرئيسي والصفحات المستقلة (standalone)
    if [[ "$path" == "/" ]]; then continue; fi

    # تحقق من وجود standalone في نفس السطر
    line=$(grep "'$path'" "$ROUTER" | head -1)
    if echo "$line" | grep -q "standalone"; then continue; fi

    if ! echo "$ajax_paths" | grep -q "^${path}$"; then
        missing="$missing  - $path\n"
    fi
done

if [[ -n "$missing" ]]; then
    echo "⚠️  تنبيه: الصفحات التالية مسجلة في router.php لكن غير موجودة في ajax-nav.js:"
    echo -e "$missing"
    echo "أضف entries لها في pageScripts بملف public/js/ajax-nav.js"
    # exit 0 — تحذير فقط، لا يمنع العملية
fi

exit 0
