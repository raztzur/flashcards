<?php /** @var Kirby\Cms\Page $page */ ?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>כרטיסיות</title>
  <?= snippet('global-head') ?>
</head>
<body class="template-flashcards">
<main class="container">
  <header class="topbar">
    <div>
      <h3 style="margin:0 0 6px 0;">היי <?= html($userName ?? 'עלמה') ?> 👋</h3>
      <p id="greetLine" class="muted" style="margin:0;">כל הכבוד! התקדמות מעולה.</p>
    </div>
    <button class="hamburger" type="button" aria-label="תפריט" data-menu-btn>
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
    <nav class="nav">
      <a href="<?= url('flashcards/stats') ?>" class="btn">סטטיסטיקות</a>
      <a href="<?= url('flashcards/category-new') ?>" class="btn">הוסף קטגוריה</a>
      <a href="<?= url('flashcards/add') ?>" class="btn">הוסף כרטיסייה</a>
      <a href="<?= url('flashcards/test?auto=1') ?>" class="btn">מבחן</a>
    </nav>
  </header>
  <script>
    // Hamburger toggle for mobile
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

    // שליפת אייקונים זמינים
    $iconsDir = kirby()->root('index') . '/assets/icons';
    $availableIcons = [];
    if (is_dir($iconsDir)) {
      foreach (glob($iconsDir . '/*.svg') as $file) {
        $availableIcons[] = basename($file, '.svg');
      }
    }

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
  <!-- <section class="panel" id="greetPanel">
    <div class="row" style="justify-content:space-between; align-items:center;">
      <div>
        <h3 style="margin:0 0 6px 0;">היי <?= html($userName) ?> 👋</h3>
        <p id="greetLine" class="muted" style="margin:0;">כל הכבוד! התקדמות מעולה.</p>
      </div>
      <div class="row">
        <div class="kpi">כרטיסיות לתרגול היום: <strong><?= $todayDue ?></strong></div>
        <div class="kpi">כרטיסיות שלמדת היום: <strong><?= $reviewedToday ?></strong></div>
        <div class="kpi">סה״כ כרטיסיות: <strong><?= $totalCards ?></strong></div>
      </div>
    </div>
  </section> -->

  <section>
    <div class="grid" id="catGrid">
      <?php foreach ($cats as $cat): 
        // ספירה מתוך תתי־קטגוריות בלבד
        $subcats = $cat->children()->filterBy('intendedTemplate','subcategory');
        $count = $subcats->children()->filterBy('intendedTemplate','card')->count();
        $iconName = $cat->content()->get('icon')->value() ?? '';
        $href = url('flashcards/'.$cat->slug());
      ?>
      <div class="category-card">
        <!-- קטגוריה ראשית -->
        <div class="card" data-slug="<?= html($cat->slug()) ?>" data-color="<?= $cat->content()->get('color')->value() ?? 'blue' ?>">
          <!-- שורה עליונה -->
          <div class="card-top">
            <div class="card-top-left">
              <div class="iconwrap" data-iconhold></div>
              <div class="meta">
                <div class="title">
                  <span><?= html($cat->title()) ?></span>
                </div>
              </div>
            </div>
            <div class="actions">
              <button class="menu-btn" data-menu-toggle aria-label="אפשרויות">
                <svg viewBox="0 0 24 24">
                  <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
              </button>
              <div class="menu-dropdown" data-menu-dropdown>
                <button data-add-sub title="הוסף תת־קטגוריה">
                  <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-linecap="round" stroke-width="2" fill="none"/></svg>
                </button>
                <button data-edit title="עריכה">
                  <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
                </button>
                <button data-delete class="danger" title="מחיקה">
                  <svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
                </button>
              </div>
            </div>
          </div>

          <!-- שורה תחתונה -->
          <div class="card-bottom">
            <a class="icon-btn"
               href="<?= url('flashcards/test') . '?category=' . urlencode($cat->slug()) ?>"
               title="מבחן בקטגוריה" aria-label="מבחן בקטגוריה">
              <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </a>
          </div>

          <!-- עריכה inline -->
          <div class="editrow" data-editrow>
            <div class="edit-field">
              <label>שם קטגוריה</label>
              <input type="text" class="pill" data-name placeholder="שם קטגוריה" value="<?= html($cat->title()) ?>" />
            </div>
            
            <div class="edit-field">
              <label>צבע</label>
              <div class="color-picker" data-color-picker>
                <div class="color-option <?= ($cat->content()->get('color')->value() === 'pink' || !$cat->content()->get('color')->value()) ? 'selected' : '' ?>" 
                     data-color="pink" style="background-color: #ffcadc;" title="ורוד"></div>
                <div class="color-option <?= ($cat->content()->get('color')->value() === 'yellow') ? 'selected' : '' ?>" 
                     data-color="yellow" style="background-color: #fbe74e;" title="צהוב"></div>
                <div class="color-option <?= (($cat->content()->get('color')->value() ?: 'blue') === 'blue') ? 'selected' : '' ?>" 
                     data-color="blue" style="background-color: #9dc4f5;" title="כחול"></div>
                <div class="color-option <?= ($cat->content()->get('color')->value() === 'green') ? 'selected' : '' ?>" 
                     data-color="green" style="background-color: #c2ddc2;" title="ירוק"></div>
                <div class="color-option <?= ($cat->content()->get('color')->value() === 'red') ? 'selected' : '' ?>" 
                     data-color="red" style="background-color: #ed6f60;" title="אדום"></div>
              </div>
              <input type="hidden" data-color value="<?= $cat->content()->get('color')->value() ?: 'blue' ?>" />
            </div>
            
            <div class="edit-field">
              <label>אייקון</label>
              <div class="icon-picker" data-icon-picker>
                <div class="icon-option <?= !$iconName ? 'selected' : '' ?>" data-icon="" title="ללא אייקון">
                  <span>ללא</span>
                </div>
                <?php foreach ($availableIcons as $icon): ?>
                <div class="icon-option <?= ($iconName === $icon) ? 'selected' : '' ?>" 
                     data-icon="<?= html($icon) ?>" title="<?= html($icon) ?>">
                  <img src="/assets/icons/<?= html($icon) ?>.svg" alt="<?= html($icon) ?>" />
                </div>
                <?php endforeach; ?>
              </div>
              <input type="hidden" data-icon value="<?= html($iconName) ?>" />
            </div>
            
            <div class="edit-actions">
              <button class="btn" data-save>שמור</button>
              <button class="btn ghost" data-cancel>בטל</button>
            </div>
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

        <!-- טופס קטן להוספת תת־קטגוריה (מוסתר עד שיפתח) -->
        <div class="panel" data-add-sub-panel style="display:none; margin-top:8px;">
          <div class="form-row" style="align-items:flex-end;">
            <div class="field" style="min-width:240px;">
              <label>שם תת־קטגוריה</label>
              <input type="text" data-add-sub-title placeholder="למשל: מיטוכונדריה" />
            </div>
            <button class="btn" type="button" data-add-sub-save>הוסף</button>
            <button class="btn ghost" type="button" data-add-sub-cancel>בטל</button>
            <div class="form-msg" data-add-sub-msg></div>
          </div>
        </div>

        <!-- תת-קטגוריות -->
        <?php if ($subcats->count() > 0): ?>
        <div class="subcategories">
          <?php foreach ($subcats as $sub): 
            $subCount = $sub->children()->filterBy('intendedTemplate','card')->count();
            $subHref = url('flashcards/'.$cat->slug().'/'.$sub->slug());
          ?>
          <div class="subcard" data-slug="<?= html($sub->slug()) ?>">
            <div class="subcard-main">
              <a href="<?= $subHref ?>" class="subcard-title"><?= html($sub->title()) ?></a>
              <div class="subcard-actions">
                <a class="icon-btn" href="<?= url('flashcards/add') . '?category=' . urlencode($cat->slug()) . '&subcategory=' . urlencode($sub->slug()) ?>" title="הוסף כרטיסייה">
                  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8"/><path d="M8 12h8"/></svg>
                </a>
                <a class="icon-btn" href="<?= url('flashcards/test') . '?category=' . urlencode($cat->slug()) . '&subcategory=' . urlencode($sub->slug()) ?>" title="מבחן">
                  <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </a>
                <button class="icon-btn" data-edit title="עריכה">
                  <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                </button>
                <button class="icon-btn danger" data-delete title="מחיקה">
                  <svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                </button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      
      <?php endforeach; ?>
    </div>
  </section>
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

  // עריכה/מחיקה קטגוריה (רק כרטיסי קטגוריה, לא תת-קטגוריות)
  document.querySelectorAll('.card[data-slug]').forEach(row=>{
    const slug=row.getAttribute('data-slug');
    const addSubBtn = row.querySelector('[data-add-sub]');
    const editBtn=row.querySelector('[data-edit]');
    const delBtn =row.querySelector('[data-delete]');
    const editRow=row.querySelector('[data-editrow]');
    const nameIn =row.querySelector('[data-name]');
    const addSubPanel = row.parentElement.querySelector('[data-add-sub-panel]');
    const addSubTitle = addSubPanel ? addSubPanel.querySelector('[data-add-sub-title]') : null;
    const addSubSave  = addSubPanel ? addSubPanel.querySelector('[data-add-sub-save]') : null;
    const addSubCancel= addSubPanel ? addSubPanel.querySelector('[data-add-sub-cancel]') : null;
    const addSubMsg   = addSubPanel ? addSubPanel.querySelector('[data-add-sub-msg]') : null;

    // טופס פנימי להוספת תת־קטגוריה
    if (addSubBtn && addSubPanel && addSubTitle && addSubSave && addSubCancel) {
      addSubBtn.addEventListener('click', ()=>{
        // סגור פאנלים אחרים
        document.querySelectorAll('[data-add-sub-panel]').forEach(p=>{ if(p!==addSubPanel) p.style.display='none'; });
        addSubPanel.style.display = addSubPanel.style.display==='none' ? 'block' : 'none';
        if (addSubPanel.style.display==='block') {
          addSubTitle.value = '';
          addSubMsg.textContent = '';
          setTimeout(()=> addSubTitle.focus(), 50);
        }
      });
      addSubCancel.addEventListener('click', ()=>{
        addSubPanel.style.display='none';
        addSubMsg.textContent='';
      });
      addSubSave.addEventListener('click', async ()=>{
        const name = (addSubTitle.value||'').trim();
        if (!name){ addSubMsg.textContent='נא להזין שם'; return; }
        addSubMsg.textContent='מוסיף…';
        const res = await postJSON('<?= url('subcats/add') ?>', { category: slug, title: name });
        if (!res.ok){ addSubMsg.textContent='שגיאה: ' + (res.error||''); return; }
        location.reload();
      });
      // שליחה ב-Enter
      addSubTitle.addEventListener('keydown', (e)=>{
        if (e.key==='Enter'){ e.preventDefault(); addSubSave.click(); }
        if (e.key==='Escape'){ e.preventDefault(); addSubCancel.click(); }
      });
    }

    // וודא שכל האלמנטים קיימים לפני הוספת event listeners
    if (editBtn && editRow) {
      editBtn.addEventListener('click', ()=> editRow.classList.toggle('show'));
    }
    
    if (editRow) {
      const cancelBtn = editRow.querySelector('[data-cancel]');
      const saveBtn = editRow.querySelector('[data-save]');
      
      // פיקר צבעים
      const colorPicker = editRow.querySelector('[data-color-picker]');
      const colorInput = editRow.querySelector('input[data-color]');
      if (colorPicker && colorInput) {
        colorPicker.addEventListener('click', (e) => {
          const option = e.target.closest('.color-option');
          if (!option) return;
          
          // הסר selection מכל האפשרויות
          colorPicker.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
          // בחר את האפשרות הנוכחית
          option.classList.add('selected');
          // עדכן את הערך המוסתר
          colorInput.value = option.getAttribute('data-color');
        });
      }
      
      // פיקר אייקונים
      const iconPicker = editRow.querySelector('[data-icon-picker]');
      const iconInput = editRow.querySelector('input[data-icon]');
      if (iconPicker && iconInput) {
        iconPicker.addEventListener('click', (e) => {
          const option = e.target.closest('.icon-option');
          if (!option) return;
          
          // הסר selection מכל האפשרויות
          iconPicker.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
          // בחר את האפשרות הנוכחית
          option.classList.add('selected');
          // עדכן את הערך המוסתר
          iconInput.value = option.getAttribute('data-icon');
        });
      }
      
      if (cancelBtn) {
        cancelBtn.addEventListener('click', ()=> editRow.classList.remove('show'));
      }
      
      if (saveBtn) {
        saveBtn.addEventListener('click', async ()=>{
          const title = (nameIn?.value||'').trim(); 
          const colorInput = editRow.querySelector('input[data-color]');
          const iconInput = editRow.querySelector('input[data-icon]');
          const color = colorInput?.value || 'blue';
          const icon = iconInput?.value || '';
          
          if(!title){ nameIn?.focus(); return; }
          
          const updateData = { slug, title, color, icon };
          const res = await postJSON('<?= url('categories/update') ?>', updateData);
          if(!res.ok){ alert('שגיאה: '+(res.error||'')); return; }
          location.reload();
        });
      }
    }

    // מחיקה - רק אם יש כפתור מחיקה
    if (delBtn) {
      let armed=false, timer=null;
      function disarm(){ 
        armed=false; 
        delBtn.classList.remove('danger'); 
        delBtn.innerHTML='<svg viewBox="0 0 24 24"><path d="M3 6h18" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M10 11v6" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M14 11v6" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>'; 
        if (timer){ clearTimeout(timer); timer=null; } 
      }
      
      delBtn.addEventListener('click', async (e)=>{
        e.stopPropagation();
        if (!armed){ 
          armed=true; 
          delBtn.classList.add('danger'); 
          delBtn.innerHTML='בטוח?'; 
          timer=setTimeout(disarm,5000); // 5 שניות במקום 3
          return; 
        }
        const res = await postJSON('<?= url('categories/delete') ?>', { slug });
        if(!res.ok){ alert('שגיאה: '+(res.error||'')); disarm(); return; }
        row.remove();
      });
    }
  });

  // הוספת לוגיקה למחיקת תת-קטגוריות
  document.querySelectorAll('.subcard[data-slug]').forEach(subRow=>{
    const subSlug = subRow.getAttribute('data-slug');
    const categoryCard = subRow.closest('.category-card');
    const catSlug = categoryCard ? categoryCard.querySelector('.card').getAttribute('data-slug') : null;
    const delBtn = subRow.querySelector('[data-delete]');
    
    if (!delBtn || !catSlug) return;
    
    let armed=false, timer=null;
    function disarm(){ 
      armed=false; 
      delBtn.classList.remove('danger'); 
      delBtn.innerHTML='<svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>'; 
      if (timer){ clearTimeout(timer); timer=null; } 
    }
    
    delBtn.addEventListener('click', async (e)=>{
      e.stopPropagation();
      if (!armed){ 
        armed=true; 
        delBtn.classList.add('danger'); 
        delBtn.innerHTML='בטוח?'; 
        timer=setTimeout(disarm,5000);
        return; 
      }
      const res = await postJSON('<?= url('subcats/delete') ?>', { category: catSlug, slug: subSlug });
      if(!res.ok){ 
        alert('שגיאה: '+(res.error||'')); 
        disarm(); 
        return; 
      }
      subRow.remove();
    });
    
    // ביטול הzbrojení בלחיצה מחוץ לכפתור
    document.addEventListener('click', (ev)=>{ if (!delBtn.contains(ev.target)) disarm(); });
  });

  // תמיכה בתפריט נפתח  
  console.log('Looking for menu buttons...');
  const menuButtons = document.querySelectorAll('[data-menu-toggle]');
  console.log('Found', menuButtons.length, 'menu buttons');
  
  menuButtons.forEach((btn, index) => {
    console.log('Setting up button', index, btn);
    btn.addEventListener('click', (e) => {
      console.log('Button clicked!', e.target);
      e.stopPropagation();
      const dropdown = btn.nextElementSibling;
      console.log('Dropdown:', dropdown);
      const isOpen = dropdown && dropdown.style.display === 'block';
      console.log('Is open:', isOpen);
      
      // סגור את כל התפריטים הפתוחים
      document.querySelectorAll('[data-menu-dropdown]').forEach(d => d.style.display = 'none');
      
      // פתח/סגור את התפריט הנוכחי
      if (dropdown && !isOpen) {
        dropdown.style.display = 'block';
        console.log('Opening dropdown');
      }
    });
  });
  
  // סגירת תפריטים בלחיצה מחוץ להם
  document.addEventListener('click', () => {
    document.querySelectorAll('[data-menu-dropdown]').forEach(d => d.style.display = 'none');
  });
</script>
</body>
</html>