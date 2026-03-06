# UI_DESIGN_SYSTEM.md — نظام التصميم
# مجلس عائلة العوامي (Awami Council)

---

## 1. الألوان (Color Palette)

### 1.1 الألوان الأساسية

| المتغير CSS | القيمة | الاستخدام |
|-------------|--------|----------|
| `--green-dark` | `#1A5C32` | اللون الأساسي — عناوين، أزرار، تمييز |
| `--green` | `#3D8B37` | الأخضر المتوسط — تفاعلات hover |
| `--green-light` | `#e8f5ec` | خلفيات فاتحة — badges، أيقونات |
| `--navy` | `#1B3456` | اللون الثانوي — Header/Footer gradient |
| `--gold` | `#c8a84b` | لون التمييز — أزرار خاصة، مناصب الرئيس |

### 1.2 ألوان الخلفية والسطح

| المتغير | Light Mode | Dark Mode |
|---------|------------|-----------|
| `--bg` | `#FDFCF8` | `#0A0F0C` |
| `--bg-alt` | `#f5f9f6` | `#111a14` |
| `--surface` | `#fff` | `#162018` |
| `--surface-elevated` | — | `#223a28` |

### 1.3 ألوان النصوص

| المتغير | Light Mode | Dark Mode |
|---------|------------|-----------|
| `--text` | `#1a2a1e` | `#d1ddd4` |
| `--text-elevated` | — | `#e8f0ea` |
| `--muted` | `#546358` | `#7a8f80` |
| `--border` | `#c2cec5` | `#2a3d2e` |

### 1.4 ألوان الحالة (Admin)

| اللون | الاستخدام | الخلفية | النص |
|-------|----------|---------|------|
| Success | نجاح / نشط | `#e8f5ec` | `#1A5C32` |
| Warning | تحذير / معلق | `#fff8e1` | `#f57f17` |
| Danger | خطأ / حذف | `#ffebee` | `#c62828` |
| Info | معلومات | `#e3f2fd` | `#1565c0` |
| Gold | مميز | `#fef9e7` | `#8b6914` |
| Purple | استشاري | `#f3e5f5` | `#6a1b9a` |

### 1.5 التدرجات (Gradients)

```css
/* Header & Footer */
--gradient-header: linear-gradient(135deg, #1A5C32 0%, #1B3456 100%);

/* Hero Section */
background: linear-gradient(170deg,
  rgba(26,92,50,.97) 0%,
  rgba(27,52,86,.95) 55%,
  rgba(200,168,75,.85) 100%);

/* Green Button */
background: linear-gradient(135deg, #1A5C32, #3D8B37);

/* Card */
background: linear-gradient(145deg, #FDFCF8, #fff);
```

---

## 2. الخطوط (Typography)

### 2.1 عائلات الخطوط

| المتغير | الخط | الاستخدام | المصدر |
|---------|------|----------|--------|
| `--font-body` | Cairo | النصوص العامة | Google Fonts |
| `--font-heading` | Amiri | العناوين الرئيسية (serif) | Google Fonts |
| `--font-display` | Saudi | نصوص عرض خاصة | ملفات محلية TTF |

### 2.2 أحجام الخطوط

| السياق | الحجم | الوزن |
|--------|-------|-------|
| Hero h2 | 42px | 900 |
| Section Title | 34px | 900 |
| Admin Header | 24px | 700 |
| Card Title | 16px | 700 |
| Body Text | 15px | 400 |
| Meta / Label | 12-13px | 600 |
| Small Text | 11px | 400 |

### 2.3 ارتفاع الأسطر

```css
body { line-height: 1.7; }
h1, h2, h3, h4, h5, h6 { line-height: 1.3; }
```

---

## 3. التباعد والأبعاد (Spacing)

### 3.1 نظام التباعد

| الحجم | القيمة | الاستخدام |
|-------|--------|----------|
| Micro | 4px | فجوات form groups |
| Small | 8-12px | padding داخلي للعناصر |
| Base | 16-20px | padding الأقسام |
| Medium | 24-28px | فجوات بين العناصر |
| Large | 48px | فجوات بين الأقسام |
| Section | 70px | padding عمودي للأقسام |

### 3.2 حدود الزوايا (Border Radius)

| المتغير | القيمة | الاستخدام |
|---------|--------|----------|
| `--radius` | `12px` | البطاقات والحاويات |
| `--radius-lg` | `18px` | النوافذ المنبثقة |
| `--radius-xl` | `24px` | العناصر الكبيرة |
| `--radius-pill` | `100px` | الأزرار الكبسولية |

### 3.3 الحاوية (Container)

```css
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}
```

---

## 4. الظلال (Shadows)

| المتغير | القيمة | الاستخدام |
|---------|--------|----------|
| `--shadow-sm` | `0 2px 12px rgba(0,0,0,.06)` | عناصر صغيرة |
| `--shadow-md` | `0 8px 28px rgba(45,107,64,.12)` | بطاقات |
| `--shadow-lg` | `0 12px 32px rgba(71,145,92,.18)` | عناصر مرفوعة |
| `--shadow-card` | `0 4px 16px rgba(0,0,0,.08)` | بطاقات عامة |

---

## 5. الانتقالات والحركات (Transitions & Animations)

### 5.1 الانتقالات

```css
--ease: cubic-bezier(.22, 1, .36, 1);
--transition-fast: .22s var(--ease);
--transition-theme: .3s ease;
```

### 5.2 الحركات (Keyframes)

| الاسم | الوصف |
|-------|-------|
| `fadeInUp` | ظهور من الأسفل (30px) مع شفافية |
| `slideUp` | انزلاق للأعلى (20px) |
| `spin` | دوران 360 درجة |
| `shimmer` | تأثير تحميل |
| `pageLoadBar` | شريط تحميل AJAX (0→70%) |

### 5.3 تأثيرات التفاعل

```css
/* رفع عند التمرير */
:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

/* تكبير الصورة */
img:hover { transform: scale(1.05); }

/* دوران أيقونة Accordion */
.open .icon { transform: rotate(45deg); }
```

---

## 6. المكونات (UI Components)

### 6.1 الأزرار

**الأساسي (Primary)**:
```css
background: linear-gradient(135deg, #1A5C32, #3D8B37);
color: white;
padding: 12px 32px;
border-radius: 12px;
font-weight: 700;
box-shadow: 0 4px 12px rgba(26,92,50,.25);
/* hover: translateY(-2px) + shadow أكبر */
```

**الثانوي (Secondary)**:
```css
background: var(--green-light);
color: var(--green-dark);
border: 2px solid var(--green);
/* hover: يعكس الألوان */
```

**الذهبي (Accent)**:
```css
background: linear-gradient(135deg, #c8a84b, #e8c96a);
color: #1a2a1e;
```

**الخطر (Danger)**:
```css
background: #c62828;
color: white;
```

**WhatsApp**:
```css
background: #25D366;
color: white;
```

**أحجام**:
- Default: `8px 16px`, font `13px`, weight `600`
- Small (btn-sm): `5px 12px`
- Extra Small (btn-xs): `3px 9px`

### 6.2 البطاقات (Cards)

**بطاقة عامة**:
```css
background: var(--surface);
border: 2px solid var(--border);
border-radius: var(--radius);
box-shadow: var(--shadow-card);
padding: 24px;
/* hover: translateY(-6px) + shadow-lg + border-color: green */
```

**بطاقة المجلس (Council Card)**:
- حافة علوية ملونة: أخضر (عادي)، ذهبي (رئيس)، أزرق بحري (استشاري)
- أيقونة 50px بخلفية تدرج أخضر
- قائمة مهام بنقاط خضراء

**بطاقة اللجنة (Committee Card)**:
- شريط علوي ملون (88px) مع emoji/أيقونة
- شارة عدد الأعضاء في الزاوية العليا
- عنوان + وصف + عدد الأعضاء

**بطاقة الخبر (News Card)**:
- صورة 180px (cover fit، تكبير عند hover)
- شارة الفئة + التاريخ
- عنوان + مقتطف + كاتب
- تفاصيل قابلة للتوسيع (summary tag)

**بطاقة الفعالية (Event Card)**:
- تخطيط أفقي: أيقونة 52px + معلومات
- شارة حالة ملونة
- تاريخ + مسؤول + مشاركين

### 6.3 النماذج (Forms)

**حقل إدخال (Input)**:
```css
width: 100%;
padding: 9px 13px;
border: 1.5px solid var(--border);
border-radius: 8px;
font-size: 13px;
transition: border-color .2s, box-shadow .2s;
/* focus: border-color: green + box-shadow: 0 0 0 4px rgba(61,139,55,.1) */
```

**خطأ في الحقل**:
```css
border-color: #c62828;
box-shadow: 0 0 0 3px rgba(198,40,40,.1);
```

**تخطيط النماذج (Admin)**:
```css
display: grid;
grid-template-columns: 1fr 1fr;
gap: 14px;
```

### 6.4 الجداول (Tables) — Admin

**الرأس**:
```css
background: #f5f7f6;
font-size: 11px;
font-weight: 700;
text-transform: uppercase;
```

**الصفوف**:
```css
font-size: 12px;
vertical-align: middle;
/* hover: background فاتحة */
```

**Mobile**: الجدول يتحول لتخطيط بطاقات (`td::before` يعرض التسمية)

### 6.5 النوافذ المنبثقة (Modals)

```css
max-width: 540px; /* wide: 720px */
border-radius: var(--radius-lg);
/* ظهور: slideUp من 30px + opacity */

/* Header */
background: linear-gradient(135deg, #1A5C32, #3D8B37);
color: white;

/* Mobile */
width: 100%;
border-radius: 16px 16px 0 0;
/* slide up from bottom */
```

### 6.6 الإشعارات (Toast)

```css
position: fixed;
top: 20px;
left: 50%; /* (RTL: right) */
transform: translateX(-50%);
padding: 12px 20px;
border-radius: 10px;
z-index: 10000;
/* أنواع: success (أخضر)، error (أحمر)، warning (أصفر)، info (أزرق) */
```

### 6.7 الشارات (Badges)

```css
display: inline-block;
padding: 3px 10px;
border-radius: 20px;
font-size: 11px;
font-weight: 600;
/* 6 أنماط: success, warning, danger, info, gold, purple */
```

### 6.8 الأكورديون (Accordion)

```css
max-width: 800px;
margin: 0 auto;

/* Header */
display: flex;
justify-content: space-between;
padding: 16px 20px;
cursor: pointer;

/* Icon */
width: 28px; height: 28px;
background: var(--green-light);
border-radius: 50%;
/* open: rotate(45deg) */

/* Body */
max-height: 0;
overflow: hidden;
transition: max-height .4s ease;
```

### 6.9 Lightbox (المعرض)

```css
position: fixed;
inset: 0;
background: rgba(0,0,0,.92);
z-index: 9999;
display: flex;
align-items: center;
justify-content: center;

/* الصورة */
max-width: 90vw;
max-height: 85vh;
object-fit: contain;

/* أزرار التنقل */
position: absolute;
top: 50%;
font-size: 28px;
/* شفافية تزداد عند hover */
```

---

## 7. التخطيط (Layout)

### 7.1 الشريط العلوي (Header)

```css
position: sticky;
top: 0;
z-index: 1000;
background: linear-gradient(135deg, #1A5C32, #1B3456);
/* يختفي عند التمرير لأسفل: transform: translateY(-100%) */
/* يظهر عند التمرير لأعلى */
```

- الشعار: 52x52px، خلفية بيضاء مستديرة
- روابط التنقل: 8 عناصر بفجوة 8px
- زر تبديل الوضع (☀/🌙)
- قائمة الهاتف (hamburger menu)

### 7.2 التذييل (Footer)

```css
background: linear-gradient(135deg, #1A5C32, #1B3456);
text-align: center;
padding: 40px 24px;
color: white;
```

- الشعار: 48x48px
- روابط + WhatsApp
- حقوق النشر

### 7.3 القائمة السفلية للهاتف (Bottom Nav)

```css
position: fixed;
bottom: 0;
width: 100%;
height: 64px;
background: var(--surface);
display: flex;
justify-content: space-around;
/* 5 عناصر: الرئيسية، الأخبار، الشجرة، المعرض، المجلس */
/* أيقونات 20px + تسميات 10px */
/* padding-bottom: safe-area-inset */
```

### 7.4 الشريط الجانبي (Admin Sidebar)

```css
position: fixed;
right: 0; /* RTL */
width: 270px;
height: 100vh;
background: linear-gradient(180deg, #1A5C32, #1B3456);
overflow-y: auto;

/* Mobile: drawer مخفي */
transform: translateX(100%);
transition: transform .3s ease;
/* مع backdrop overlay */
```

---

## 8. التصميم المتجاوب (Responsive)

### 8.1 نقاط التوقف (Breakpoints)

| الحجم | القيمة | الوصف |
|-------|--------|-------|
| Desktop | > 1024px | التخطيط الكامل |
| Tablet | 768px - 1024px | 2 أعمدة |
| Mobile | < 768px | عمود واحد + قائمة سفلية |
| Small Mobile | < 480px | تقليص padding |

### 8.2 تحولات المكونات

| المكون | Desktop | Mobile |
|--------|---------|--------|
| Header Nav | أفقي | قائمة منسدلة |
| Bottom Nav | مخفي | ظاهر (64px) |
| Admin Sidebar | ثابت (270px) | Drawer overlay |
| Cards Grid | 3 أعمدة | عمود واحد |
| Tables | جدول عادي | تخطيط بطاقات |
| Modals | 540px centered | 100% width, slide up |
| Stats Grid | 4 أعمدة | 1-2 أعمدة |

---

## 9. الوضع الداكن (Dark Mode)

### 9.1 التفعيل

```css
html[data-theme="dark"] { /* متغيرات الوضع الداكن */ }
```

يُفعَّل عبر:
1. زر التبديل (☀/🌙) → يُخزن في localStorage
2. تفضيل النظام (`prefers-color-scheme: dark`)

### 9.2 التحولات

```css
/* انتقال سلس بين الوضعين */
* { transition: background-color .3s ease, color .3s ease, border-color .3s ease; }
```

### 9.3 القيم المختلفة في الوضع الداكن

| الخاصية | Light | Dark |
|---------|-------|------|
| الخلفية | `#FDFCF8` | `#0A0F0C` |
| السطح | `#fff` | `#162018` |
| النص | `#1a2a1e` | `#d1ddd4` |
| الحدود | `#c2cec5` | `#2a3d2e` |
| الذهبي | `#c8a84b` | `#e0c56a` (أكثر سطوعاً) |
| الظلال | أفتح | أغمق (opacity أعلى) |

---

## 10. RTL (من اليمين لليسار)

```css
html {
  direction: rtl;
}
```

### 10.1 قواعد RTL

- جميع النصوص تبدأ من اليمين
- الأيقونات والـ Badges في الجهة المعاكسة (للغة الإنجليزية)
- الـ Sidebar في Admin على اليمين
- الـ Bottom Nav: الأيقونات مرتبة من اليمين
- الـ Scroll-to-top: في الزاوية السفلية اليسرى
- `margin-right` بدل `margin-left` للـ Sidebar الثابت

---

## 11. إمكانية الوصول (Accessibility)

| الميزة | التطبيق |
|--------|---------|
| Skip Link | رابط مخفي يظهر عند Focus — يقفز للمحتوى الرئيسي |
| ARIA Labels | على جميع الأزرار التفاعلية والقوائم |
| Focus Outline | `3px solid var(--green)` |
| Focus Trapping | في Modals و Lightbox |
| Keyboard Navigation | Tab للتنقل، Enter للتأكيد، Escape للإغلاق |
| Color Contrast | WCAG AA على معظم النصوص |
| Reduced Motion | `prefers-reduced-motion: reduce → transitions: none` |
| Touch Targets | حد أدنى 44px للعناصر التفاعلية على الهاتف |

---

## 12. الأيقونات

المشروع يستخدم **Emoji Unicode** بدلاً من مكتبات أيقونات:

| الأيقونة | الاستخدام |
|---------|----------|
| 🕋 | العمرة |
| 🍖 | غداء العيد |
| 🌙 | رمضان |
| 🎡 | الرحلات |
| ✨ | ليلة القدر |
| 🕌 | المساجد |
| 🏆 | المسابقات |
| 📈 | الاستثمار |
| 🎓 | الاستشارية |
| 📢 | الإعلامية |
| 🐑 | العقيقة |
| 🎉 | فعاليات عامة |
| ☀️ / 🌙 | تبديل الوضع |

الأحجام: 20px (قائمة)، 36-52px (بطاقات)، 64px (Hero)
