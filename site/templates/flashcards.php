<?php /** @var Kirby\Cms\Page $page */ ?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>כרטיסיות</title>
  <link rel="manifest" href="/assets/pwa/manifest.json">
  <meta name="theme-color" content="#ffffff">
  <style>
    :root{ --stroke:#000; --bg:#fff; --fg:#000; --muted:#666; }
    *{ box-sizing:border-box; }
    html,body{ margin:0; padding:0; background:var(--bg); color:var(--fg);
      font-family:system-ui,-apple-system,Segoe UI,Roboto; }
    .container{ padding:16px; max-width:1100px; margin:0 auto; }
    .topbar{ display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .nav{ display:flex; gap:8px; flex-wrap:wrap; }
    .btn{ border:1px solid var(--stroke); border-radius:12px; padding:8px 12px; background:#fff; text-decoration:none; color:#000; cursor:pointer; }
    .btn.icon{ padding:6px; display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; }
    .btn.ghost{ background:transparent; }
    .btn.danger{ border-color:#c00; color:#c00; }
    .grid{ display:grid; gap:12px; grid-template-columns: repeat(2,1fr); }
    @media (max-width:800px){ .grid{ grid-template-columns: 1fr; } }
    .card{ border:1px solid var(--stroke); border-radius:16px; padding:12px; display:grid; grid-template-columns:auto 1fr auto; gap:12px; align-items:center; position:relative; overflow:hidden; }
    .coverlink{ position:absolute; inset:0; z-index:2; cursor:pointer; }
    .iconwrap{ width:44px; height:44px; border:1px solid var(--stroke); border-radius:12px; display:flex; align-items:center; justify-content:center; background:#fff; position:relative; z-index:3; overflow:hidden; }
    .iconwrap img{ width:24px; height:24px; object-fit:contain; display:block; }
    .meta{ display:flex; flex-direction:column; gap:6px; min-width:0; position:relative; z-index:3; }
    .title a{ font-weight:700; color:#000; text-decoration:none; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .sub{ color:var(--muted); font-size:13px; display:flex; gap:10px; align-items:center; }
    .due{ border:1px solid var(--stroke); border-radius:999px; padding:2px 8px; font-size:12px; background:#fff; }
    .actions{ display:flex; gap:6px; align-items:center; position:relative; z-index:3; }
    .bg{ position:absolute; inset:0; opacity:0.14; z-index:1; background:#f5f5f5; }
    .editrow{ display:none; grid-column:1/-1; border-top:1px dashed #000; padding-top:10px; gap:8px; align-items:center; flex-wrap:wrap; position:relative; z-index:3; }
    .editrow.show{ display:flex; }
    .pill{ border:1px solid var(--stroke); border-radius:999px; padding:4px 8px; background:#fff; }
    .picker{ display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
    .grad{ width:36px; height:24px; border:1px solid var(--stroke); border-radius:6px; cursor:pointer; }
    .svgopt{ width:36px; height:36px; border:1px solid var(--stroke); border-radius:8px; background:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; }
    .svgopt.selected, .grad.selected{ outline:2px solid #000; }
    svg{ width:20px; height:20px; }
    .panel{ border:1px solid var(--stroke); border-radius:16px; padding:12px; margin-bottom:16px; }
    .row{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .kpi{ display:flex; gap:8px; align-items:center; border:1px solid var(--stroke); border-radius:10px; padding:6px 10px; background:#fff; }
  </style>
</head>
<body>
  <main class="container">
    <header class="topbar">
      <h1>כרטיסיות</h1>
      <nav class="nav">
        <a href="<?= url('flashcards/stats') ?>" class="btn">סטטיסטיקות</a>
        <a href="<?= url('flashcards/category-new') ?>" class="btn">קטגוריה חדשה</a>
        <a href="<?= url('flashcards/add') ?>" class="btn">הוסף כרטיס</a>
        <a href="<?= url('flashcards/test?auto=1') ?>" class="btn">מבחן על הכל</a>
      </nav>
    </header>

    <?php
      $root = page('flashcards');
      $cats = $root ? $root->children()->filterBy('intendedTemplate','category') : [];
      $allCards = $root ? $root->children()->filterBy('intendedTemplate','category')->children()->filterBy('intendedTemplate','card') : [];
      $totalCards = $allCards->count();
      $progress = (function(){
        $file = kirby()->root('content').'/.flashcards/progress.json';
        return file_exists($file) ? (json_decode(\Kirby\Toolkit\F::read($file), true) ?: []) : [];
      })();
      $todayDue = 0; $reviewedToday = 0; $correctToday = 0;
      $avgEase = 0; $easeCount = 0;
      foreach ($allCards as $c) {
        $row = $progress[$c->id()] ?? null;
        if ($row) {
          if (!empty($row['dueAt']) && strtotime($row['dueAt']) <= time()) $todayDue++;
          if (!empty($row['updatedAt']) && date('Y-m-d', strtotime($row['updatedAt'])) === date('Y-m-d')) {
            $reviewedToday++;
            if (!empty($row['lastQuality']) && (int)$row['lastQuality'] >= 4) $correctToday++;
          }
          if (isset($row['easiness'])) { $avgEase += (float)$row['easiness']; $easeCount++; }
        }
      }
      $avgEase = $easeCount ? round($avgEase/$easeCount, 2) : 2.5;
    ?>

    <!-- סיכום יומי + סטטוס כללי -->
    <section class="panel">
      <h3 style="margin:0 0 8px 0;">סיכום יומי</h3>
      <div class="row">
        <div class="kpi">כרטיסים Due היום: <strong><?= $todayDue ?></strong></div>
        <div class="kpi">נסקרו היום: <strong><?= $reviewedToday ?></strong></div>
        <div class="kpi">נכונים היום: <strong><?= $correctToday ?></strong></div>
        <div class="kpi">סה״כ כרטיסים: <strong><?= $totalCards ?></strong></div>
        <div class="kpi" title="EF — גבוה=קל">מדד קלות ממוצע: <strong><?= $avgEase ?></strong></div>
      </div>
    </section>

    <section>
      <h2>קטגוריות</h2>
      <div class="grid" id="catGrid">
        <?php foreach ($cats as $cat): 
          $count = $cat->children()->filterBy('intendedTemplate','card')->count();
          $iconName = $cat->content()->get('icon')->value() ?? '';
          $background = $cat->content()->get('background')->value() ?: ($cat->content()->get('gradient')->value() ?? '');
          $href = url('flashcards/'.$cat->slug());
        ?>
        <div class="card" data-slug="<?= html($cat->slug()) ?>">
          <a class="coverlink" href="<?= $href ?>" aria-label="פתח קטגוריה"></a>
          <div class="bg" style="<?= $background ? 'background:'.$background : '' ?>"></div>
          <div class="iconwrap" data-iconhold></div>
          <div class="meta">
            <div class="title">
              <a href="<?= $href ?>" data-link><?= html($cat->title()) ?></a>
            </div>
            <div class="sub">
              <span class="pill"><?= $count ?> כרטיסיות</span>
              <span class="due" data-due>Due Today: …</span>
            </div>
          </div>
          <div class="actions">
            <a class="btn icon ghost" href="<?= url('flashcards/test') . '?category=' . urlencode($cat->slug()) . '&auto=1' ?>" title="מבחן בקטגוריה" aria-label="מבחן בקטגוריה">▶</a>
            <a class="btn icon ghost" href="<?= url('flashcards/add') . '?category=' . urlencode($cat->slug()) ?>" title="הוסף שאלה" aria-label="הוסף שאלה">＋</a>
            <button class="btn icon ghost" data-edit title="עריכה">✎</button>
            <button class="btn icon ghost" data-delete title="מחיקה">🗑️</button>
          </div>

          <!-- עריכה inline -->
          <div class="editrow" data-editrow>
            <input type="text" class="pill" data-name placeholder="שם קטגוריה" value="<?= html($cat->title()) ?>" />
            <div class="picker" data-gradpicker></div>
            <div class="picker" data-iconpicker></div>
            <button class="btn" data-save>שמור</button>
            <button class="btn ghost" data-cancel>בטל</button>
          </div>

          <script>
            (function mountIcon(){
              const holder = document.currentScript.parentElement.querySelector('[data-iconhold]');
              const name = <?= json_encode($iconName) ?>;
              if (name) {
                // מעדיפים קובץ SVG מתיקיית assets/icons אם קיים
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
    // נשאר: פונקציות עזר לפיקרים של עריכה אינליין (כמו שהיה)
    const GRADS = [
      'linear-gradient(135deg,#e0f7fa,#80deea)',
      'linear-gradient(135deg,#e8f5e9,#a5d6a7)',
      'linear-gradient(135deg,#fff3e0,#ffcc80)',
      'linear-gradient(135deg,#f3e5f5,#ce93d8)',
      'linear-gradient(135deg,#ede7f6,#b39ddb)',
      'linear-gradient(135deg,#fbe9e7,#ffab91)',
      'linear-gradient(135deg,#e0f2f1,#80cbc4)',
      'linear-gradient(135deg,#fffde7,#fff59d)'
    ];
    const ICONS = ['dna','atom','flask','microscope','pill','leaf'];

    function iconSvg(name){
      switch(name){
        case 'dna': return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round" data-name="dna"><path d="M7 4c3 0 5 2 5 5s-2 5-5 5"/><path d="M17 20c-3 0-5-2-5-5s2-5 5-5"/><path d="M7 4c0 4 10 4 10 8M7 14c0 4 10 4 10 8"/></svg>`;
        case 'atom': return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round" data-name="atom"><circle cx="12" cy="12" r="1.5"/><ellipse cx="12" cy="12" rx="10" ry="4"/><ellipse cx="12" cy="12" rx="4" ry="10"/><path d="M2 12h20"/></svg>`;
        case 'flask': return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round" data-name="flask"><path d="M9 3h6"/><path d="M10 3v6l-5 9a3 3 0 0 0 2.6 4.5h8.8A3 3 0 0 0 19 18l-5-9V3"/></svg>`;
        case 'microscope': return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round" data-name="microscope"><path d="M6 18h12"/><path d="M9 14h6"/><path d="M10 6l4 4"/><rect x="11" y="2" width="2" height="6"/></svg>`;
        case 'pill': return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round" data-name="pill"><rect x="3" y="8" width="18" height="8" rx="4"/><path d="M12 8v8"/></svg>`;
        case 'leaf': return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round" data-name="leaf"><path d="M5 21c8-2 14-8 16-16-8 2-14 8-16 16Z"/><path d="M8 8c0 4 4 8 8 8"/></svg>`;
        default: return '';
      }
    }
    function renderGradPicker(container, current=''){
      container.innerHTML = '';
      GRADS.forEach(g=>{
        const d=document.createElement('button');
        d.type='button'; d.className='grad'; d.style.background=g;
        if (g===current) d.classList.add('selected');
        d.addEventListener('click',()=>{
          container.querySelectorAll('.grad').forEach(x=>x.classList.remove('selected'));
          d.classList.add('selected');
          container.dataset.value = g;
        });
        container.appendChild(d);
      });
      container.dataset.value = current || '';
    }
    function renderIconPicker(container, current=''){
      container.innerHTML = '';
      // מציגים גם את האייקונים הבנויים כ־fallback לעריכה מהירה
      ICONS.forEach(name=>{
        const d=document.createElement('button');
        d.type='button'; d.className='svgopt'; d.innerHTML=iconSvg(name);
        if (name===current) d.classList.add('selected');
        d.addEventListener('click',()=>{
          container.querySelectorAll('.svgopt').forEach(x=>x.classList.remove('selected'));
          d.classList.add('selected');
          container.dataset.value = name;
        });
        container.appendChild(d);
      });
      container.dataset.value = current || '';
    }
    async function postJSON(url, payload){
      try{
        const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const t = await r.text(); try{ return JSON.parse(t);}catch{return {ok:false,error:t||r.statusText||('HTTP '+r.status)}}
      }catch(e){ return {ok:false,error:e.message||'Network error'} }
    }

    (async function hydrate(){
      const res = await fetch('/categories'); const t = await res.text(); let json;
      try{ json = JSON.parse(t);}catch{ return; }
      if (!json.ok) return;
      const map = Object.fromEntries((json.categories||[]).map(x=>[x.slug,x]));
      document.querySelectorAll('[data-slug]').forEach(row=>{
        const slug=row.getAttribute('data-slug');
        const span=row.querySelector('[data-due]');
        const gbg = row.querySelector('.bg');
        const iconHold = row.querySelector('[data-iconhold]');
        const data=map[slug]; if(!data) return;
        if (span) span.textContent = 'Due Today: ' + (data.dueToday ?? 0);
        if (data.background && gbg) gbg.style.background = data.background;
        if (data.icon && iconHold && iconHold.childElementCount===0) {
          iconHold.innerHTML = `<img alt="" src="/assets/icons/${data.icon}.svg" onerror="this.onerror=null; this.remove();">`;
        }
      });
    })();

    // עריכה inline
    document.querySelectorAll('[data-slug]').forEach(row=>{
      const slug=row.getAttribute('data-slug');
      const editBtn=row.querySelector('[data-edit]');
      const delBtn =row.querySelector('[data-delete]');
      const editRow=row.querySelector('[data-editrow]');
      const nameIn =row.querySelector('[data-name]');
      const gradPk =row.querySelector('[data-gradpicker]');
      const iconPk =row.querySelector('[data-iconpicker]');
      const linkEl =row.querySelector('[data-link]');
      const bg     =row.querySelector('.bg');
      const iconHold=row.querySelector('[data-iconhold]');

      renderGradPicker(gradPk, bg && bg.style.background ? bg.style.background : '');
      renderIconPicker(iconPk, '');

      editBtn.addEventListener('click', ()=> editRow.classList.toggle('show'));
      editRow.querySelector('[data-cancel]').addEventListener('click', ()=> editRow.classList.remove('show'));
      editRow.querySelector('[data-save]').addEventListener('click', async ()=>{
        const title=(nameIn.value||'').trim(); if(!title){ nameIn.focus(); return; }
        const background = gradPk.dataset.value || '';
        const icon = iconPk.dataset.value || '';
        const res = await postJSON('<?= url('categories/update') ?>',{ slug, title, background, icon });
        if(!res.ok){ alert('שגיאה: '+(res.error||'')); return; }
        linkEl.textContent = title;
        if (bg && background) bg.style.background = background;
        if (iconHold) iconHold.innerHTML = icon ? `<img alt="" src="/assets/icons/${icon}.svg" onerror="this.onerror=null; this.remove();">` : '';
        editRow.classList.remove('show');
      });

      let armed=false, timer=null;
      function disarm(){ armed=false; delBtn.classList.remove('danger'); delBtn.textContent='🗑️'; if (timer){ clearTimeout(timer); timer=null; } }
      delBtn.addEventListener('click', async ()=>{
        if (!armed){ armed=true; delBtn.classList.add('danger'); delBtn.textContent='בטוח?'; timer=setTimeout(disarm,3000); return; }
        const res = await postJSON('<?= url('categories/delete') ?>', { slug });
        if(!res.ok){ alert('שגיאה: '+(res.error||'')); disarm(); return; }
        row.remove();
      });
      document.addEventListener('click', (ev)=>{ if (!delBtn.contains(ev.target)) disarm(); });
    });
  </script>
</body>
</html>