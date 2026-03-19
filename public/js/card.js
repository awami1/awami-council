/**
 * card.js — Eid Greeting Card Wizard
 * مولّد بطاقة تهنئة العيد بأربع خطوات
 */

/* ── Font & sizing constants (easy to change later) ── */
const CARD_FONT_FAMILY   = "'Cairo', sans-serif";
const CARD_FONT_SIZE_RATIO = 0.055;           // name font size as fraction of card width
const CARD_GREETING_FONT  = "'Amiri', serif";

function initCard() {
  'use strict';

  /* ════════════════════════════════════════════
     State
     ════════════════════════════════════════════ */
  const state = {
    currentStep: 0,
    userName: '',
    selectedShape: 'square',
    selectedTemplate: 0,
  };

  const SIZES = {
    square:   { w: 1080, h: 1080 },
    portrait: { w: 1080, h: 1920 },
  };

  const THUMB_SCALE = 0.38;

  /* ════════════════════════════════════════════
     Templates
     ════════════════════════════════════════════ */
  const TEMPLATES = [
    {
      id: 'classic-green',
      name: 'أخضر كلاسيك',
      greeting: 'عادت أعيادكم',
      subtext: 'أهنئكم بمناسبة عيد الفطر المبارك\nأعاده الله علينا وعليكم بالصحة والعافية',
      colors: {
        bg1: '#0C3B2E', bg2: '#1A5C32', bg3: '#0f3d22',
        accent: '#c8a84b', accentLight: '#e0c56a',
        star: '#c8a84b',
        text: '#ffffff',
        nameBg: '#c8a84b', nameColor: '#1a2a1e',
      },
      // Future: src: { square: '/assets/card-green-sq.jpg', portrait: '/assets/card-green-pt.jpg' }
    },
    {
      id: 'royal-blue',
      name: 'أزرق ملكي',
      greeting: 'كل عام وأنتم بخير',
      subtext: 'تقبل الله منا ومنكم صالح الأعمال\nوكل عام وأنتم إلى الله أقرب',
      colors: {
        bg1: '#0A1628', bg2: '#1B3456', bg3: '#0f2440',
        accent: '#c8a84b', accentLight: '#e0c56a',
        star: '#c8a84b',
        text: '#ffffff',
        nameBg: '#c8a84b', nameColor: '#0f1e36',
      },
    },
  ];

  /* ════════════════════════════════════════════
     DOM References
     ════════════════════════════════════════════ */
  const wizard      = document.getElementById('card-wizard');
  if (!wizard) return; // safety — page not loaded

  const steps       = wizard.querySelectorAll('.card-step');
  const dots        = wizard.querySelectorAll('.card-dot');
  const nameInput   = document.getElementById('card-name-input');
  const nextBtn1    = document.getElementById('card-next-1');
  const prevBtn1    = document.getElementById('card-prev-1');
  const nextBtn2    = document.getElementById('card-next-2');
  const prevBtn2    = document.getElementById('card-prev-2');
  const startBtn    = document.getElementById('card-start-btn');
  const downloadBtn = document.getElementById('card-download');
  const shareBtn    = document.getElementById('card-share');
  const backEdit    = document.getElementById('card-back-edit');
  const carousel    = document.getElementById('card-carousel');
  const resultCanvas = document.getElementById('card-result-canvas');
  const themeToggle = document.getElementById('card-theme-toggle');
  const shapeBtns   = wizard.querySelectorAll('.card-shape-btn');

  let lastRenderedShape = null;

  /* ════════════════════════════════════════════
     Navigation
     ════════════════════════════════════════════ */
  function goToStep(n) {
    if (n < 0 || n > 3) return;
    state.currentStep = n;

    steps.forEach(function(s) { s.classList.remove('active'); });
    steps[n].classList.add('active');

    // Update dots
    dots.forEach(function(d, i) {
      d.classList.remove('active', 'completed');
      if (i === n) d.classList.add('active');
      else if (i < n) d.classList.add('completed');
    });

    // Step-specific actions
    if (n === 2) renderThumbnails();
    if (n === 3) renderResult();

    // Scroll to top of wizard
    wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  /* ════════════════════════════════════════════
     Canvas Drawing Helpers
     ════════════════════════════════════════════ */

  /** Radial gradient background */
  function drawBackground(ctx, w, h, colors) {
    var cx = w / 2, cy = h * 0.4;
    var r = Math.max(w, h) * 0.8;
    var grad = ctx.createRadialGradient(cx, cy, 0, cx, cy, r);
    grad.addColorStop(0, colors.bg2);
    grad.addColorStop(0.5, colors.bg1);
    grad.addColorStop(1, colors.bg3);
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, w, h);
  }

  /** Geometric diamond pattern */
  function drawGeometricPattern(ctx, w, h, color) {
    ctx.save();
    ctx.globalAlpha = 0.06;
    ctx.strokeStyle = color;
    ctx.lineWidth = 1;
    var spacing = w * 0.06;
    for (var x = 0; x < w; x += spacing) {
      for (var y = 0; y < h; y += spacing) {
        ctx.beginPath();
        ctx.moveTo(x, y - spacing / 2);
        ctx.lineTo(x + spacing / 2, y);
        ctx.lineTo(x, y + spacing / 2);
        ctx.lineTo(x - spacing / 2, y);
        ctx.closePath();
        ctx.stroke();
      }
    }
    ctx.restore();
  }

  /** Dashed border inset */
  function drawDashedBorder(ctx, w, h, color) {
    var inset = w * 0.04;
    ctx.save();
    ctx.strokeStyle = color;
    ctx.globalAlpha = 0.25;
    ctx.lineWidth = 2;
    ctx.setLineDash([12, 8]);
    ctx.strokeRect(inset, inset, w - inset * 2, h - inset * 2);
    ctx.restore();
  }

  /** Corner decorations (circles) */
  function drawCornerDecorations(ctx, w, h, color) {
    ctx.save();
    ctx.globalAlpha = 0.08;
    ctx.fillStyle = color;
    var r = w * 0.08;
    var inset = w * 0.06;

    // Four corners
    var corners = [
      [inset, inset],
      [w - inset, inset],
      [inset, h - inset],
      [w - inset, h - inset]
    ];
    corners.forEach(function(c) {
      ctx.beginPath();
      ctx.arc(c[0], c[1], r, 0, Math.PI * 2);
      ctx.fill();
    });
    ctx.restore();
  }

  /** Four-point star */
  function drawStar(ctx, cx, cy, r, color) {
    ctx.save();
    ctx.fillStyle = color;
    ctx.globalAlpha = 0.3;
    ctx.beginPath();
    for (var i = 0; i < 4; i++) {
      var angle = (Math.PI / 2) * i;
      var ox = Math.cos(angle) * r;
      var oy = Math.sin(angle) * r;
      if (i === 0) ctx.moveTo(cx + ox, cy + oy);
      else ctx.lineTo(cx + ox, cy + oy);

      var midAngle = angle + Math.PI / 4;
      var mr = r * 0.35;
      ctx.lineTo(cx + Math.cos(midAngle) * mr, cy + Math.sin(midAngle) * mr);
    }
    ctx.closePath();
    ctx.fill();
    ctx.restore();
  }

  /** Scattered stars */
  function drawScatteredStars(ctx, w, h, color) {
    var positions = [
      { x: 0.12, y: 0.15, r: 0.018 },
      { x: 0.88, y: 0.12, r: 0.022 },
      { x: 0.08, y: 0.45, r: 0.015 },
      { x: 0.92, y: 0.50, r: 0.020 },
      { x: 0.15, y: 0.80, r: 0.016 },
      { x: 0.85, y: 0.82, r: 0.019 },
      { x: 0.50, y: 0.08, r: 0.014 },
      { x: 0.30, y: 0.25, r: 0.012 },
      { x: 0.70, y: 0.30, r: 0.013 },
    ];
    positions.forEach(function(p) {
      drawStar(ctx, w * p.x, h * p.y, w * p.r, color);
    });
  }

  /** Crescent and star */
  function drawCrescent(ctx, cx, cy, size, color) {
    ctx.save();
    ctx.fillStyle = color;
    ctx.globalAlpha = 0.85;

    // Crescent
    ctx.beginPath();
    ctx.arc(cx, cy, size, 0, Math.PI * 2);
    ctx.fill();

    // Cut-out circle (slightly offset)
    ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath();
    ctx.arc(cx + size * 0.35, cy - size * 0.1, size * 0.78, 0, Math.PI * 2);
    ctx.fill();
    ctx.globalCompositeOperation = 'source-over';

    // Small star next to crescent
    ctx.fillStyle = color;
    ctx.globalAlpha = 0.85;
    var starX = cx + size * 0.8;
    var starY = cy - size * 0.4;
    drawStarShape(ctx, starX, starY, size * 0.3);

    ctx.restore();
  }

  /** Simple 5-point star shape (filled) */
  function drawStarShape(ctx, cx, cy, r) {
    ctx.beginPath();
    for (var i = 0; i < 5; i++) {
      var angle = (Math.PI / 2.5) * i - Math.PI / 2;
      var ox = Math.cos(angle) * r;
      var oy = Math.sin(angle) * r;
      if (i === 0) ctx.moveTo(cx + ox, cy + oy);
      else ctx.lineTo(cx + ox, cy + oy);

      var innerAngle = angle + Math.PI / 5;
      var ir = r * 0.4;
      ctx.lineTo(cx + Math.cos(innerAngle) * ir, cy + Math.sin(innerAngle) * ir);
    }
    ctx.closePath();
    ctx.fill();
  }

  /** Decorative divider line above name */
  function drawDivider(ctx, cx, y, lineW, color) {
    ctx.save();
    ctx.strokeStyle = color;
    ctx.globalAlpha = 0.5;
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(cx - lineW / 2, y);
    ctx.lineTo(cx + lineW / 2, y);
    ctx.stroke();

    // Small diamond in center
    var d = 5;
    ctx.fillStyle = color;
    ctx.globalAlpha = 0.6;
    ctx.beginPath();
    ctx.moveTo(cx, y - d);
    ctx.lineTo(cx + d, y);
    ctx.lineTo(cx, y + d);
    ctx.lineTo(cx - d, y);
    ctx.closePath();
    ctx.fill();
    ctx.restore();
  }

  /** Name pill (rounded rect with name) */
  function drawNamePill(ctx, name, cx, cy, maxW, bgColor, textColor, fontSize) {
    if (!name) return;

    ctx.save();
    ctx.font = 'bold ' + fontSize + 'px ' + CARD_FONT_FAMILY;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';

    // Auto-shrink if too wide
    var measured = ctx.measureText(name).width;
    var currentSize = fontSize;
    while (measured > maxW * 0.7 && currentSize > fontSize * 0.5) {
      currentSize -= 2;
      ctx.font = 'bold ' + currentSize + 'px ' + CARD_FONT_FAMILY;
      measured = ctx.measureText(name).width;
    }

    var padX = currentSize * 1.2;
    var padY = currentSize * 0.5;
    var pillW = measured + padX * 2;
    var pillH = currentSize + padY * 2;
    var pillR = pillH / 2;

    // Pill background
    ctx.fillStyle = bgColor;
    ctx.globalAlpha = 0.9;
    ctx.beginPath();
    ctx.moveTo(cx - pillW / 2 + pillR, cy - pillH / 2);
    ctx.lineTo(cx + pillW / 2 - pillR, cy - pillH / 2);
    ctx.arcTo(cx + pillW / 2, cy - pillH / 2, cx + pillW / 2, cy, pillR);
    ctx.arcTo(cx + pillW / 2, cy + pillH / 2, cx + pillW / 2 - pillR, cy + pillH / 2, pillR);
    ctx.lineTo(cx - pillW / 2 + pillR, cy + pillH / 2);
    ctx.arcTo(cx - pillW / 2, cy + pillH / 2, cx - pillW / 2, cy, pillR);
    ctx.arcTo(cx - pillW / 2, cy - pillH / 2, cx - pillW / 2 + pillR, cy - pillH / 2, pillR);
    ctx.closePath();
    ctx.fill();

    // Name text
    ctx.globalAlpha = 1;
    ctx.fillStyle = textColor;
    ctx.fillText(name, cx, cy + 2);
    ctx.restore();
  }

  /* ════════════════════════════════════════════
     Main Template Renderer
     ════════════════════════════════════════════ */

  function drawTemplate(canvas, templateIdx, shape, name, callback) {
    var tpl = TEMPLATES[templateIdx];
    var size = SIZES[shape];
    canvas.width = size.w;
    canvas.height = size.h;
    var ctx = canvas.getContext('2d');
    var w = size.w, h = size.h;
    var colors = tpl.colors;

    // Future: if template has image source, use that instead
    if (tpl.src && tpl.src[shape]) {
      var img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = function() {
        ctx.drawImage(img, 0, 0, w, h);
        // Draw name over image
        var nameY = shape === 'square' ? h * 0.70 : h * 0.58;
        var nameSize = w * CARD_FONT_SIZE_RATIO;
        drawDivider(ctx, w / 2, nameY - nameSize * 1.2, w * 0.2, colors.accent);
        drawNamePill(ctx, name, w / 2, nameY, w, colors.nameBg, colors.nameColor, nameSize);
        if (callback) callback();
      };
      img.onerror = function() {
        // Fallback to code-drawn template
        drawCodeTemplate(ctx, w, h, tpl, shape, name);
        if (callback) callback();
      };
      img.src = tpl.src[shape];
      return;
    }

    drawCodeTemplate(ctx, w, h, tpl, shape, name);
    if (callback) callback();
  }

  function drawCodeTemplate(ctx, w, h, tpl, shape, name) {
    var colors = tpl.colors;

    // 1. Background
    drawBackground(ctx, w, h, colors);

    // 2. Geometric pattern
    drawGeometricPattern(ctx, w, h, colors.accent);

    // 3. Dashed border
    drawDashedBorder(ctx, w, h, colors.accent);

    // 4. Corner decorations
    drawCornerDecorations(ctx, w, h, colors.text);

    // 5. Scattered stars
    drawScatteredStars(ctx, w, h, colors.star);

    // 6. Crescent at top center
    var crescentY = shape === 'square' ? h * 0.18 : h * 0.12;
    drawCrescent(ctx, w / 2, crescentY, w * 0.055, colors.accent);

    // 7. Council name (top)
    ctx.save();
    ctx.font = '600 ' + (w * 0.028) + 'px ' + CARD_FONT_FAMILY;
    ctx.fillStyle = colors.text;
    ctx.globalAlpha = 0.6;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    var councilY = shape === 'square' ? h * 0.08 : h * 0.055;
    ctx.fillText('مجلس عائلة العوامي', w / 2, councilY);
    ctx.restore();

    // 8. Greeting text (large, Amiri)
    ctx.save();
    var greetSize = w * 0.09;
    ctx.font = 'bold ' + greetSize + 'px ' + CARD_GREETING_FONT;
    ctx.fillStyle = colors.text;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.shadowColor = 'rgba(0,0,0,0.4)';
    ctx.shadowBlur = 15;
    ctx.shadowOffsetY = 4;
    var greetY = shape === 'square' ? h * 0.42 : h * 0.32;
    ctx.fillText(tpl.greeting, w / 2, greetY);
    ctx.restore();

    // 9. Subtext
    ctx.save();
    var subSize = w * 0.032;
    ctx.font = subSize + 'px ' + CARD_FONT_FAMILY;
    ctx.fillStyle = colors.text;
    ctx.globalAlpha = 0.7;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    var lines = tpl.subtext.split('\n');
    var subY = greetY + greetSize * 0.8;
    lines.forEach(function(line, i) {
      ctx.fillText(line, w / 2, subY + i * (subSize * 1.8));
    });
    ctx.restore();

    // 10. Name with divider and pill
    var nameY = shape === 'square' ? h * 0.70 : h * 0.58;
    var nameSize = w * CARD_FONT_SIZE_RATIO;
    drawDivider(ctx, w / 2, nameY - nameSize * 1.2, w * 0.2, colors.accent);
    drawNamePill(ctx, name, w / 2, nameY, w, colors.nameBg, colors.nameColor, nameSize);

    // 11. Footer
    ctx.save();
    ctx.font = '600 ' + (w * 0.022) + 'px ' + CARD_FONT_FAMILY;
    ctx.fillStyle = colors.text;
    ctx.globalAlpha = 0.4;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'bottom';
    var footerY = h - (w * 0.05);
    ctx.fillText('مجلس عائلة العوامي', w / 2, footerY);
    ctx.font = (w * 0.018) + 'px ' + CARD_FONT_FAMILY;
    ctx.globalAlpha = 0.3;
    ctx.fillText('alawami.site', w / 2, footerY + (w * 0.028));
    ctx.restore();
  }

  /* ════════════════════════════════════════════
     Thumbnails (Step 2)
     ════════════════════════════════════════════ */
  function renderThumbnails() {
    // Skip if already rendered for current shape
    if (lastRenderedShape === state.selectedShape) return;
    lastRenderedShape = state.selectedShape;

    carousel.innerHTML = '';

    TEMPLATES.forEach(function(tpl, idx) {
      var item = document.createElement('div');
      item.className = 'card-template-item' + (idx === state.selectedTemplate ? ' selected' : '');

      var badge = document.createElement('span');
      badge.className = 'card-template-badge';
      badge.textContent = '✓ محدد';

      var canvas = document.createElement('canvas');
      canvas.className = 'card-template-thumb';
      var size = SIZES[state.selectedShape];
      var thumbW = Math.round(size.w * THUMB_SCALE);
      var thumbH = Math.round(size.h * THUMB_SCALE);
      canvas.style.width = thumbW + 'px';
      canvas.style.height = thumbH + 'px';

      var nameLabel = document.createElement('span');
      nameLabel.className = 'card-template-name';
      nameLabel.textContent = tpl.name;

      item.appendChild(badge);
      item.appendChild(canvas);
      item.appendChild(nameLabel);

      item.addEventListener('click', function() {
        state.selectedTemplate = idx;
        carousel.querySelectorAll('.card-template-item').forEach(function(el) {
          el.classList.remove('selected');
        });
        item.classList.add('selected');
      });

      carousel.appendChild(item);

      // Draw thumbnail
      drawTemplate(canvas, idx, state.selectedShape, state.userName || 'اسمك هنا');
    });
  }

  /* ════════════════════════════════════════════
     Result (Step 3)
     ════════════════════════════════════════════ */
  function renderResult() {
    drawTemplate(resultCanvas, state.selectedTemplate, state.selectedShape, state.userName);
  }

  /* ════════════════════════════════════════════
     Download
     ════════════════════════════════════════════ */
  function handleDownload() {
    var dataUrl = resultCanvas.toDataURL('image/png', 1.0);
    var link = document.createElement('a');
    var safeName = state.userName.replace(/[^a-zA-Z0-9\u0600-\u06FF_-]/g, '_');
    link.download = (safeName || 'card') + '-eid-' + Date.now() + '.png';
    link.href = dataUrl;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  /* ════════════════════════════════════════════
     WhatsApp Share
     ════════════════════════════════════════════ */
  function handleShare() {
    var shareText = 'كل عام وأنتم بخير — مجلس عائلة العوامي';

    resultCanvas.toBlob(function(blob) {
      if (!blob) {
        fallbackShare(shareText);
        return;
      }

      var file = new File([blob], 'eid-card.png', { type: 'image/png' });

      // Try Web Share API
      if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
        navigator.share({
          text: shareText,
          files: [file]
        }).catch(function() {
          fallbackShare(shareText);
        });
      } else {
        fallbackShare(shareText);
      }
    }, 'image/png');
  }

  function fallbackShare(text) {
    if (confirm('سيتم تحميل الصورة أولاً، ثم فتح واتساب للمشاركة. متابعة؟')) {
      handleDownload();
      setTimeout(function() {
        window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
      }, 500);
    }
  }

  /* ════════════════════════════════════════════
     Theme Toggle
     ════════════════════════════════════════════ */
  function updateThemeIcon() {
    var current = document.documentElement.getAttribute('data-theme');
    themeToggle.innerHTML = current === 'dark' ? '&#9788;' : '&#9790;';
  }

  function toggleTheme() {
    var current = document.documentElement.getAttribute('data-theme');
    var next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('awami-theme', next);
    updateThemeIcon();
    // Sync with main theme toggle if it exists
    var mainToggle = document.getElementById('themeToggle');
    if (mainToggle) {
      mainToggle.innerHTML = next === 'dark' ? '&#9788;' : '&#9790;';
    }
  }

  /* ════════════════════════════════════════════
     Event Binding
     ════════════════════════════════════════════ */

  // Start button
  startBtn.addEventListener('click', function() { goToStep(1); });

  // Name input
  nameInput.addEventListener('input', function() {
    state.userName = nameInput.value.trim();
    nextBtn1.disabled = !state.userName;
    // Force re-render of thumbnails when going back to step 2
    lastRenderedShape = null;
  });

  // Shape selection
  shapeBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      shapeBtns.forEach(function(b) { b.classList.remove('selected'); });
      btn.classList.add('selected');
      state.selectedShape = btn.getAttribute('data-shape');
      lastRenderedShape = null; // force re-render
    });
  });

  // Navigation buttons
  prevBtn1.addEventListener('click', function() { goToStep(0); });
  nextBtn1.addEventListener('click', function() {
    if (state.userName) goToStep(2);
  });
  prevBtn2.addEventListener('click', function() { goToStep(1); });
  nextBtn2.addEventListener('click', function() { goToStep(3); });

  // Result actions
  downloadBtn.addEventListener('click', handleDownload);
  shareBtn.addEventListener('click', handleShare);
  backEdit.addEventListener('click', function() { goToStep(2); });

  // Theme toggle
  themeToggle.addEventListener('click', toggleTheme);
  updateThemeIcon();

  /* ════════════════════════════════════════════
     Font Preload & Init
     ════════════════════════════════════════════ */
  function init() {
    goToStep(0);
  }

  // Preload fonts then init
  if (document.fonts && document.fonts.load) {
    Promise.all([
      document.fonts.load("bold 48px 'Amiri'"),
      document.fonts.load("normal 32px 'Cairo'")
    ]).then(init).catch(init);
  } else {
    init();
  }
}
