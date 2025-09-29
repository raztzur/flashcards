<?php
// שליפת רשימת אייקונים מתוך assets/icons (עד 24)
$iconsDir = kirby()->root('index') . '/assets/icons';
$icons = [];
if (is_dir($iconsDir)) {
  foreach (glob($iconsDir . '/*.svg') as $file) {
    $icons[] = [
      'name' => basename($file, '.svg'),
      'svg'  => file_get_contents($file)
    ];
  }
  if (count($icons) > 24) $icons = array_slice($icons, 0, 24);
}
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>קטגוריה חדשה</title>
  <?= snippet('global-head') ?>
</head>
<body>
  <main class="container">

    <header class="topbar">
      <h1>קטגוריה חדשה</h1>
      <nav class="nav">
        <a class="btn ghost" href="<?= url('flashcards') ?>">← חזרה</a>
      </nav>
    </header>

    <section class="form-panel">
      <form id="catForm" class="form-row" action="<?= url('categories/add') ?>" method="post">
        <div class="field" style="min-width:260px;">
          <label for="title">שם קטגוריה</label>
          <input type="text" id="title" name="title" required placeholder="למשל: ביוכימיה">
          <div class="helper">אפשר לערוך את השם אחר כך</div>
        </div>

        <div class="field" style="flex:1; min-width:260px;">
          <label>אייקון</label>
          <?php if (!empty($icons)): ?>
            <div id="icons" class="icon-picker">
              <?php foreach ($icons as $i => $ico): ?>
                <button type="button"
                        class="icon-chip iconopt<?= $i === 0 ? ' selected' : '' ?>"
                        data-name="<?= html($ico['name']) ?>"
                        title="<?= html($ico['name']) ?>">
                  <?= $ico['svg'] ?>
                </button>
              <?php endforeach; ?>
            </div>
            <div class="helper">ניתן לשנות אייקון גם לאחר יצירה</div>
          <?php else: ?>
            <div class="helper">לא נמצאו אייקונים בתיקייה <code>assets/icons</code></div>
          <?php endif; ?>
        </div>

        <div class="field" style="align-self:flex-end;">
          <button class="btn" type="submit">צור</button>
          <div id="msg" class="form-msg" aria-live="polite"></div>
        </div>
      </form>
    </section>

  </main>

  <script>
    // בחירת אייקון
    let selectedIcon = (()=>{
      const first = document.querySelector('#icons .iconopt');
      return first ? first.getAttribute('data-name') : '';
    })();

    const iconsWrap = document.getElementById('icons');
    if (iconsWrap){
      iconsWrap.addEventListener('click', (ev)=>{
        const btn = ev.target.closest('.iconopt'); if(!btn) return;
        iconsWrap.querySelectorAll('.iconopt').forEach(x=>x.classList.remove('selected'));
        btn.classList.add('selected');
        selectedIcon = btn.getAttribute('data-name') || '';
      });
    }

    // POST JSON עזר
    async function postJSON(url, payload){
      try{
        const r = await fetch(url, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(payload)
        });
        const t = await r.text();
        try { return JSON.parse(t); }
        catch { return { ok:false, error: t || r.statusText || ('HTTP '+r.status) }; }
      }catch(e){
        return { ok:false, error: e.message || 'Network error' };
      }
    }

    // טיפול בשליחה: יצירת קטגוריה → חזרה לעמוד הראשי
    const form = document.getElementById('catForm');
    const msg  = document.getElementById('msg');
    const title= document.getElementById('title');

    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      msg.textContent = '';

      const t = (title.value || '').trim();
      if (!t){ title.focus(); return; }

      msg.textContent = 'שומר…';
      const res = await postJSON('<?= url('categories/add') ?>', {
        title: t,
        icon: selectedIcon
      });

      if (!res.ok){
        msg.textContent = 'שגיאה: ' + (res.error || 'לא ידוע');
        msg.classList.add('error');
        return;
      }

      // המשכיות: חוזרים לעמוד הראשי לראות את הקטגוריה ברשימה
      window.location.href = '<?= url('flashcards') ?>';
    });
  </script>
</body>
</html>