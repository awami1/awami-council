<!-- نفس HTML و CSS تبعك كما هو تماماً -->
<!-- تم حذف أي localStorage -->
<!-- لا يوجد أي تغيير شكلي -->

<!-- 👇👇👇 فقط استبدل جزء <script> بالكامل بهذا 👇👇👇 -->

<script>
(function(){

/* =========================
   MENU + SCROLL (كما هو)
========================= */
var nav = document.getElementById('mainNav');
document.getElementById('menuBtn').addEventListener('click', function(){
  nav.classList.toggle('open');
});
var navLinks = nav.querySelectorAll('a');
for(var i=0;i<navLinks.length;i++){
  navLinks[i].addEventListener('click',function(){
    nav.classList.remove('open');
  });
}

var secs = document.querySelectorAll('section[id]');
var stBtn = document.getElementById('scrollTop');
stBtn.addEventListener('click',function(){
  window.scrollTo({top:0,behavior:'smooth'});
});

window.addEventListener('scroll',function(){
  var y = window.pageYOffset;
  for(var i=0;i<secs.length;i++){
    var s=secs[i],t=s.offsetTop-120,h=s.offsetHeight,id=s.getAttribute('id');
    var link=document.querySelector('nav a[href="#'+id+'"]');
    if(link){
      if(y>=t&&y<t+h) link.classList.add('active');
      else link.classList.remove('active');
    }
  }
  if(y>400) stBtn.classList.add('show');
  else stBtn.classList.remove('show');
});

/* =========================
   COUNTDOWN (API VERSION)
========================= */
var tDate = null;

function updCD(){
  if(!tDate) return;
  var d = tDate - new Date();
  if(d<0) d=0;
  document.getElementById('cd-d').textContent=Math.floor(d/864e5);
  document.getElementById('cd-h').textContent=Math.floor(d%864e5/36e5);
  document.getElementById('cd-m').textContent=Math.floor(d%36e5/6e4);
  document.getElementById('cd-date').textContent =
    tDate.toLocaleDateString('ar-SA',{
      weekday:'long',
      year:'numeric',
      month:'long',
      day:'numeric',
      hour:'2-digit',
      minute:'2-digit'
    });
}

fetch('/api/settings.php')
  .then(r=>r.json())
  .then(res=>{
    if(res.data && res.data.nextMeeting){
      var m = res.data.nextMeeting;
      if(m.visible!==false){
        tDate = new Date(m.date);
        updCD();
        setInterval(updCD,60000);
      }
    }
  });

/* =========================
   FAMILY TREE (API)
========================= */
fetch('/api/branches.php')
  .then(r=>r.json())
  .then(res=>{
    if(!res.data) return;
    var tg=document.getElementById('tree-grid');
    var html='';
    res.data.forEach(function(b){
      var cnt=b.count||0;
      var col=b.color||'#47915C';
      html+=`
        <div class="tree-branch" style="border-color:${col}">
          <div style="font-size:28px;margin-bottom:10px">🌿</div>
          <div class="tree-branch-name">${b.name}</div>
          ${b.head?`<div class="tree-branch-head">${b.head}</div>`:''}
          <div class="tree-branch-stat">
            <div class="tree-branch-num" style="color:${col}">${cnt}</div>
            <div class="tree-branch-label">فرد</div>
          </div>
        </div>
      `;
    });
    tg.innerHTML=html;
  });

/* =========================
   WEBSITE SETTINGS (API)
========================= */
fetch('/api/settings.php')
  .then(r=>r.json())
  .then(res=>{
    if(!res.data) return;
    const ws=res.data;

    if(ws.header){
      if(ws.header.title)
        document.querySelector('.logo-text h1').textContent=ws.header.title;
      if(ws.header.subtitle)
        document.querySelector('.logo-text p').textContent=ws.header.subtitle;
    }

    if(ws.hero){
      if(ws.hero.title)
        document.querySelector('.hero h2').textContent=ws.hero.title;
      if(ws.hero.description)
        document.querySelector('.hero p').textContent=ws.hero.description;
    }

    if(ws.stats){
      const nums=document.querySelectorAll('.hero-meta .num');
      if(nums[0]) nums[0].textContent=ws.stats.years;
      if(nums[1]) nums[1].textContent=ws.stats.committees;
      if(nums[2]) nums[2].textContent=ws.stats.members;
    }

    if(ws.about){
      const ac=document.querySelector('.about-content');
      let html='';
      if(ws.about.mission)
        html+=`<h3>رسالتنا</h3><p>${ws.about.mission}</p>`;
      if(ws.about.vision)
        html+=`<h3>رؤيتنا</h3><p>${ws.about.vision}</p>`;
      if(html) ac.innerHTML=html;
    }

    if(ws.values){
      const vg=document.getElementById('values-grid');
      let html='';
      ws.values.forEach(v=>{
        html+=`
          <div class="value-card">
            <div class="value-icon">${v.icon}</div>
            <div class="value-title">${v.title}</div>
            <div class="value-desc">${v.desc}</div>
          </div>
        `;
      });
      vg.innerHTML=html;
    }
  });

/* =========================
   MEDIA (API)
========================= */
var mediaFilter='all';
var mediaTabs=document.querySelectorAll('.media-tab');

mediaTabs.forEach(tab=>{
  tab.addEventListener('click',function(){
    mediaTabs.forEach(t=>t.classList.remove('active'));
    this.classList.add('active');
    mediaFilter=this.dataset.filter;
    loadMedia();
  });
});

function loadMedia(){
  fetch('/api/media.php')
    .then(r=>r.json())
    .then(res=>{
      var grid=document.getElementById('media-grid');
      var items=res.data||[];

      if(mediaFilter!=='all')
        items=items.filter(m=>m.type===mediaFilter);

      if(!items.length){
        grid.innerHTML=`
          <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#888">
            لا توجد وسائط حالياً
          </div>
        `;
        return;
      }

      var html='';
      items.forEach(item=>{
        html+=`
          <div class="media-item">
            ${item.type==='videos'
              ? `<video controls><source src="${item.url}"></video>`
              : `<img src="${item.url}" alt="">`
            }
            <div class="media-item-content">
              <div class="media-item-title">${item.title||''}</div>
            </div>
          </div>
        `;
      });

      grid.innerHTML=html;
    });
}

loadMedia();

})();
</script>
