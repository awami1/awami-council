# Known Issues & Warnings

## Service Worker محدود

`sw.js` يخزّن فقط `offline.html`. لا يخزّن CSS أو JS أو صور أو خطوط.
الموقع لا يعمل offline — فقط يعرض صفحة "أنت غير متصل".

## حماية الملفات الحساسة

الملفات المحمية عبر `.htaccess` و `nginx.conf`:
- `.env` — بيانات الاعتماد
- `*.db` — قواعد بيانات SQLite
- `.git/` — مستودع Git
- `api/config.php` — إعدادات DB

**تنبيه**: PHP built-in server لا يقرأ `.htaccess`. في التطوير المحلي، طلب مباشر لـ `.env` لا يُحظر.
عند النشر، تأكد: `curl -I https://your-domain/.env` يرجع 403.

## Cache Busting

`asset()` في `includes/helpers.php` يضيف `?v=<filemtime>` للملفات الثابتة.
`window.__ASSET_V__` متاح في JS. كافي للمشروع الحالي.

## لا يوجد package manager

المكتبات الخارجية تُحمّل من CDN:
- D3.js v7 من `d3js.org` (شجرة العائلة)
- XLSX 0.18.5 من `cdnjs.cloudflare.com` (استيراد CSV في الأدمن)
- Google Fonts: Cairo, Amiri, Tajawal

إذا سقط CDN، الميزة المعتمدة عليه تتعطل.
