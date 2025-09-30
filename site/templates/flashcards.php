<?php /** @var Kirby\Cms\Page $page */ ?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>כרטיסיות</title>
  <?= snippet('global-head') ?>
</head>
<body>
<main class="container">
  <header class="topbar">
    <h1>כרטיסיות</h1>
    <nav class="nav">
      <a href="<?= url('flashcards/stats') ?>" class="btn">סטטיסטיקות</a>
      <a href="<?= url('flashcards/category-new') ?>" class="btn">הוסף קטגוריה</a>
      <a href="<?= url('flashcards/test?auto=1') ?>" class="btn">מבחן על הכל</a>
    </nav>
  </header>

  <?php
    $userName = 'עלמה';
    $root = page('flashcards');
    $cats = $root ? $root->children()->filterBy('intendedTemplate','category') : [];
    $allCards = $root 
      ? $root->children()->filterBy('intendedTemplate','category')
              ->children()->filterBy('intendedTemplate','subcategory')
              ->children()->filterBy('intendedTemplate','card')
      : [];
    $totalCards = $allCards->count();

    $progress = (function(){
      $file = kirby()->root('content').'/.flashcards/progress.json';
      return file_exists($file) ? (json_decode(\Kirby\Toolkit\F::read($file), true) ?: []) : [];
    })();

    $todayDue = 0; $reviewedToday = 0; $correctToday = 0;
    foreach ($allCards as $c) {
      $row = $progress[$c->id()] ?? null;
      if ($row) {
        if (!empty($row['dueAt']) && strtotime($row['dueAt']) <= time()) $todayDue++;
        if (!empty($row['updatedAt']) && date('Y-m-d', strtotime($row['updatedAt'])) === date('Y-m-d')) {
          $reviewedToday++;
          if (!empty($row['lastQuality']) && (int)$row['lastQuality'] >= 4) $correctToday++;
        }
      }
    }
  ?>

  <!-- בלוק עידוד אישי -->
  <section class="panel" id="greetPanel">
    <div class="row" style="justify-content:space-between; align-items:center;">
      <div>
        <h3 style="margin:0 0 6px 0;">היי <?= html($userName) ?> 👋</h3>
        <p id="greetLine" class="muted" style="margin:0;">כל הכבוד! התקדמות מעולה.</p>
      </div>
      <div class="row">
        <div class="kpi">כרטיסים לתרגול היום: <strong><?= $todayDue ?></strong></div>
        <div class="kpi">כרטיסים שלמדת היום: <strong><?= $reviewedToday ?></strong></div>
        <div class="kpi">סה״כ כרטיסים: <strong><?= $totalCards ?></strong></div>
      </div>
    </div>
  </section>

  <section>
    <h2>קטגוריות</h2>
    <div class="grid" id="catGrid">
      <?php foreach ($cats as $cat): 
        // ספירה מתוך תתי־קטגוריות בלבד
        $count = $cat->children()->filterBy('intendedTemplate','subcategory')->children()->filterBy('intendedTemplate','card')->count();
        $iconName = $cat->content()->get('icon')->value() ?? '';
        $href = url('flashcards/'.$cat->slug());
      ?>
      <div class="card" data-slug="<?= html($cat->slug()) ?>">
        <!-- שורה עליונה -->
        <div class="card-top">
          <div class="card-top-left">
            <div class="iconwrap" data-iconhold></div>
            <div class="meta">
              <div class="title">
                <a href="<?= $href ?>" data-link><?= html($cat->title()) ?></a>
              </div>
            </div>
          </div>
          <div class="actions">
            <a class="icon-btn" href="<?= url('flashcards/add') . '?category=' . urlencode($cat->slug()) ?>" title="הוסף כרטיסייה">
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

        <!-- שורה תחתונה -->
        <div class="card-bottom">
          <div class="sub">
            <span><?= $count ?> כרטיסיות</span>
            <span>לתרגול היום: <strong data-due>…</strong></span>
          </div>
          <a class="icon-btn"
             href="<?= url('flashcards/test') . '?category=' . urlencode($cat->slug()) . '&auto=1' ?>"
             title="מבחן בקטגוריה" aria-label="מבחן בקטגוריה">
            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
          </a>
        </div>

        <!-- עריכה inline -->
        <div class="editrow" data-editrow>
          <input type="text" class="pill" data-name placeholder="שם קטגוריה" value="<?= html($cat->title()) ?>" />
          <button class="btn" data-save>שמור</button>
          <button class="btn ghost" data-cancel>בטל</button>
        </div>

        <script>
          (function mountIcon(){
            const holder = document.currentScript.parentElement.querySelector('[data-iconhold]');
            const name = <?= json_encode($iconName) ?>;
            if (name) {
              holder.innerHTML = `<img alt="" src="/assets/icons/${name}.svg" onerror="this.onerror=null; this.remove();">`;
            }
          })();
        </script>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<script>
  // מסרי עידוד מתחלפים לאלמה
  (function encourage(){
    const lines = [
      'כל הכבוד, עלמה! התקדמות מעולה 💪',
      'עלמה, עוד קצת – ואת שם! 🚀',
      'יפה מאוד! הרצף שלך מרשים 👏',
      'לומדים חכם, לאט ובטוח – כל הכבוד!',
      'בחירה מעולה לחזור היום – גאווה גדולה!'
    ];
    const el = document.getElementById('greetLine');
    if (!el) return;
    const pick = lines[Math.floor(Math.random()*lines.length)];
    el.textContent = pick;
  })();

  async function postJSON(url, payload){
    try{
      const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
      const t = await r.text(); try{ return JSON.parse(t);}catch{return {ok:false,error:t||r.statusText||('HTTP '+r.status)}}
    }catch(e){ return {ok:false,error:e.message||'Network error'} }
  }

  // הידרציה: dueToday לכל קטגוריה
  (async function hydrate(){
    const res = await fetch('/categories'); 
    const t = await res.text(); 
    let json; try{ json = JSON.parse(t);}catch{ return; }
    if (!json.ok) return;
    const map = Object.fromEntries((json.categories||[]).map(x=>[x.slug,x]));
    document.querySelectorAll('[data-slug]').forEach(row=>{
      const slug=row.getAttribute('data-slug');
      const span=row.querySelector('[data-due]');
      const data=map[slug]; if(!data) return;
      if (span) span.textContent = String(data.dueToday ?? 0);
    });
  })();

  // עריכה/מחיקה קטגוריה
  document.querySelectorAll('[data-slug]').forEach(row=>{
    const slug=row.getAttribute('data-slug');
    const editBtn=row.querySelector('[data-edit]');
    const delBtn =row.querySelector('[data-delete]');
    const editRow=row.querySelector('[data-editrow]');
    const nameIn =row.querySelector('[data-name]');

    editBtn.addEventListener('click', ()=> editRow.classList.toggle('show'));
    editRow.querySelector('[data-cancel]').addEventListener('click', ()=> editRow.classList.remove('show'));
    editRow.querySelector('[data-save]').addEventListener('click', async ()=>{
      const title=(nameIn.value||'').trim(); if(!title){ nameIn.focus(); return; }
      const res = await postJSON('<?= url('categories/update') ?>',{ slug, title });
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
      const res = await postJSON('<?= url('categories/delete') ?>', { slug });
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