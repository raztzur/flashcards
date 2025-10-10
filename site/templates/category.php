<?php /** @var Kirby\Cms\Page $page */ ?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= html($page->title()) ?> — קטגוריה</title>
  <?= snippet('global-head') ?>
</head>
<body>
<main class="container">
  <header class="topbar">
    <h1><?= html($page->title()) ?></h1>
    <button class="hamburger" type="button" aria-label="תפריט" data-menu-btn>
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
    <nav class="nav">
      <a href="<?= url('flashcards') ?>" class="btn">← חזרה</a>
      <a href="<?= url('flashcards/test') . '?category=' . urlencode($page->slug()) . '&auto=1' ?>" class="btn">מבחן בקטגוריה</a>
    </nav>
  </header>
  <script>
    // Hamburger toggle for mobile (category)
    (function(){
      const topbar = document.currentScript.previousElementSibling; // header.topbar
      const btn = topbar.querySelector('[data-menu-btn]');
      if (!btn) return;
      btn.addEventListener('click', (e)=>{
        e.stopPropagation();
        topbar.classList.toggle('menu-open');
      });
      document.addEventListener('click', (e)=>{
        if (!topbar.contains(e.target)) topbar.classList.remove('menu-open');
      });
    })();
  </script>

  <!-- טופס נגלל להוספת תת-קטגוריה -->
  <section class="panel" id="subFormPanel" style="display:none;">
    <form id="subForm" class="row" action="<?= url('subcats/add') ?>" method="post">
      <div class="field" style="min-width:220px;">
        <label for="subTitle">שם תת־קטגוריה</label>
        <input type="text" id="subTitle" name="title" placeholder="למשל: מיטוכונדריה" required>
      </div>
      <button type="submit" class="btn">הוסף</button>
      <button type="button" class="btn ghost" id="subFormCancel">בטל</button>
      <div id="subMsg" class="muted"></div>
    </form>
  </section>

  <section class="panel">
    <div class="row" style="justify-content:space-between;">
      <h3 style="margin:0">תתי־קטגוריות</h3>
      <button class="btn" id="toggleSubForm">+ הוסף תת־קטגוריה</button>
    </div>
    <div class="grid" id="subGrid">
      <?php 
        $subs = $page->children()->filterBy('intendedTemplate','subcategory');
        foreach ($subs as $sub): 
          $cards = $sub->children()->filterBy('intendedTemplate','card');
          $count = $cards->count();
          $href  = url($page->url() . '/' . $sub->slug());
      ?>
      <div class="card" data-sub="<?= html($sub->slug()) ?>">
        <div class="card-top">
          <div class="card-top-left">
            <div class="iconwrap" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6" stroke="currentColor" fill="none"/></svg>
            </div>
            <div class="meta">
              <div class="title"><a href="<?= $href ?>"><?= html($sub->title()) ?></a></div>
            </div>
          </div>
          <div class="actions">
            <a class="icon-btn" 
               href="<?= url('flashcards/add') . '?category=' . urlencode($page->slug()) . '&subcategory=' . urlencode($sub->slug()) ?>" 
               title="הוסף כרטיסייה" aria-label="הוסף כרטיסייה">
              <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-width="2" /></svg>
            </a>
            <button class="icon-btn" data-edit title="עריכה" aria-label="עריכה">
              <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
            </button>
            <button class="icon-btn danger" data-delete title="מחיקה" aria-label="מחיקה">
              <svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </div>
        </div>
        <div class="card-bottom">
          <div class="sub"><span><?= $count ?> כרטיסיות</span></div>
          <a class="icon-btn"
             href="<?= url('flashcards/test') . '?category=' . urlencode($page->slug()) . '&subcategory=' . urlencode($sub->slug()) . '&auto=1' ?>"
             title="מבחן בתת־קטגוריה" aria-label="מבחן בתת־קטגוריה">
            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
          </a>
        </div>

        <!-- עריכה inline -->
        <div class="editrow" data-editrow>
          <input type="text" class="pill" data-name placeholder="שם תת־קטגוריה" value="<?= html($sub->title()) ?>" />
          <button class="btn" data-save>שמור</button>
          <button class="btn ghost" data-cancel>בטל</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<script>
  async function postJSON(url, payload){
    try{
      const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
      const t = await r.text(); try{ return JSON.parse(t);}catch{return {ok:false,error:t||r.statusText||('HTTP '+r.status)}}
    }catch(e){ return {ok:false,error:e.message||'Network error'} }
  }

  // טוגל טופס
  const subPanel = document.getElementById('subFormPanel');
  document.getElementById('toggleSubForm')?.addEventListener('click', ()=>{
    subPanel.style.display = (subPanel.style.display==='none'||!subPanel.style.display) ? 'block' : 'none';
  });
  document.getElementById('subFormCancel')?.addEventListener('click', ()=> subPanel.style.display='none');

  // שליחת טופס
  document.getElementById('subForm')?.addEventListener('submit', async (ev)=>{
    ev.preventDefault();
    const title = (document.getElementById('subTitle').value||'').trim();
    const msg = document.getElementById('subMsg');
    if(!title){ msg.textContent='נא להזין שם'; return; }
    msg.textContent = 'שומר…';
    const res = await postJSON('<?= url('subcats/add') ?>', { category: '<?= $page->slug() ?>', title });
    if(!res.ok){ msg.textContent='שגיאה: '+(res.error||''); return; }
    location.reload();
  });

  // עריכה/מחיקה לכל תת-קטגוריה
  document.querySelectorAll('[data-sub]').forEach(row=>{
    const slug = row.getAttribute('data-sub');
    const editBtn=row.querySelector('[data-edit]');
    const delBtn =row.querySelector('[data-delete]');
    const editRow=row.querySelector('[data-editrow]');
    const nameIn =row.querySelector('[data-name]');

    editBtn.addEventListener('click', ()=> editRow.classList.toggle('show'));
    editRow.querySelector('[data-cancel]').addEventListener('click', ()=> editRow.classList.remove('show'));
    editRow.querySelector('[data-save]').addEventListener('click', async ()=>{
      const title=(nameIn.value||'').trim(); if(!title){ nameIn.focus(); return; }
      const res = await postJSON('<?= url('subcats/update') ?>', { category:'<?= $page->slug() ?>', slug, title });
      if(!res.ok){ alert('שגיאה: '+(res.error||'')); return; }
      location.reload();
    });

    let armed=false, timer=null;
    function disarm(){ 
      armed=false; 
      delBtn.classList.remove('danger'); 
      delBtn.innerHTML='<svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>'; 
      if (timer){ clearTimeout(timer); timer=null; } 
    }
    delBtn.addEventListener('click', async ()=>{
      if (!armed){ 
        armed=true; 
        delBtn.classList.add('danger'); 
        delBtn.innerHTML='בטוח?'; 
        timer=setTimeout(disarm,3000); 
        return; 
      }
      const res = await postJSON('<?= url('subcats/delete') ?>', { category:'<?= $page->slug() ?>', slug: slug });
      if(!res.ok){ alert('שגיאה: '+(res.error||'')); disarm(); return; }
      row.remove();
    });
    document.addEventListener('click', (ev)=>{ if (!delBtn.contains(ev.target)) disarm(); });
    
    // וידוא שהכפתור במצב רגיל בטעינת הדף
    document.addEventListener('DOMContentLoaded', () => {
      if (delBtn) {
        delBtn.disabled = false;
        delBtn.style.opacity = '';
        delBtn.style.pointerEvents = '';
      }
    });
  });
</script>
</body>
</html>