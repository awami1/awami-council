<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>مجلس عائلة العوامي — قريباً</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Crect width='80' height='80' rx='16' fill='%231A5C32'/%3E%3Cpath d='M55 12 C58 8,65 10,64 18 C63 26,54 30,50 38 C46 46,48 56,42 62 C36 68,26 66,24 58 C22 50,30 44,32 36' stroke='%23fff' stroke-width='6' stroke-linecap='round' fill='none'/%3E%3Cpath d='M32 36 C28 44,20 46,20 54 C20 62,28 66,34 62' stroke='%23fff' stroke-width='5' stroke-linecap='round' fill='none'/%3E%3Ccircle cx='34' cy='62' r='5' fill='%23fff'/%3E%3C/svg%3E">
<script>
(function(){var t=localStorage.getItem('awami-theme')||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t)})();
</script>
<style>
:root {
  --green-dark: #1A5C32;
  --bg: #FDFCF8;
  --text: #1a2a1e;
  --text-muted: #546358;
  --gradient-header: linear-gradient(135deg, #1A5C32, #0f3d22 50%, #1B3456);
}
[data-theme="dark"] {
  --green-dark: #2A7A44;
  --bg: #0A0F0C;
  --text: #e8f0ea;
  --text-muted: #8ca892;
  --gradient-header: linear-gradient(135deg, #0f2d18, #0a1f10 50%, #0f1a2e);
}
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: 'Cairo', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-image: radial-gradient(ellipse at 50% 0%, rgba(26,92,50,.06) 0%, transparent 60%);
  transition: background-color .3s ease, color .3s ease;
}
[data-theme="dark"] body {
  background-image: radial-gradient(ellipse at 50% 0%, rgba(42,122,68,.08) 0%, transparent 60%);
}
.container {
  text-align: center;
  padding: 2rem 1.5rem;
  max-width: 520px;
  width: 100%;
  animation: fadeUp .8s ease both;
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(24px); }
  to   { opacity: 1; transform: translateY(0); }
}
.logo {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
}
.logo svg { width: 100%; height: 100%; }
h1 {
  font-family: 'Amiri', serif;
  font-size: 2rem;
  font-weight: 700;
  color: var(--green-dark);
  margin-bottom: .75rem;
  line-height: 1.4;
}
.message {
  font-size: 1.1rem;
  color: var(--text-muted);
  line-height: 1.8;
  margin-bottom: 1.5rem;
}
.theme-toggle {
  position: fixed;
  top: 1rem;
  left: 1rem;
  background: none;
  border: 2px solid var(--green-dark);
  color: var(--green-dark);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  font-size: 1.2rem;
  cursor: pointer;
  transition: border-color .3s, color .3s, transform .25s, opacity .3s;
  opacity: .7;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}
.theme-toggle:hover {
  opacity: 1;
  transform: scale(1.1);
}
</style>
</head>
<body>
<button class="theme-toggle" id="themeToggle" aria-label="تبديل الوضع الداكن/الفاتح">&#9790;</button>
<div class="container">
  <div class="logo">
    <svg viewBox="0 0 80 80" fill="none">
      <path d="M55 12 C58 8,65 10,64 18 C63 26,54 30,50 38 C46 46,48 56,42 62 C36 68,26 66,24 58 C22 50,30 44,32 36" stroke="var(--green-dark)" stroke-width="6" stroke-linecap="round" fill="none"/>
      <path d="M32 36 C28 44,20 46,20 54 C20 62,28 66,34 62" stroke="var(--green-dark)" stroke-width="5" stroke-linecap="round" fill="none"/>
      <circle cx="34" cy="62" r="5" fill="var(--green-dark)"/>
    </svg>
  </div>
  <h1>مجلس عائلة العوامي</h1>
  <p class="message">الموقع تحت الإنشاء — نعمل على تجهيزه لكم قريباً إن شاء الله</p>
</div>
<script>
(function(){
  var btn = document.getElementById('themeToggle');
  function updateIcon() {
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    btn.textContent = isDark ? '\u2600' : '\u263E';
  }
  updateIcon();
  btn.addEventListener('click', function(){
    var current = document.documentElement.getAttribute('data-theme');
    var next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('awami-theme', next);
    updateIcon();
  });
})();
</script>
</body>
</html>
