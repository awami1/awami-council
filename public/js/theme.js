/**
 * theme.js — تبديل الوضع الداكن/الفاتح
 */
(function() {
  var KEY = 'awami-theme';

  function getTheme() {
    return localStorage.getItem(KEY) ||
           (matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(KEY, theme);
    var btn = document.getElementById('themeToggle');
    if (btn) btn.innerHTML = theme === 'dark' ? '&#9788;' : '&#9790;';
  }

  applyTheme(getTheme());

  document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('themeToggle');
    if (btn) {
      btn.innerHTML = getTheme() === 'dark' ? '&#9788;' : '&#9790;';
      btn.addEventListener('click', function() {
        var next = getTheme() === 'dark' ? 'light' : 'dark';
        applyTheme(next);
      });
    }
  });
})();
