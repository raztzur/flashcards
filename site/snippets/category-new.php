<?php
// שליפת רשימת אייקונים מתוך תיקיית assets/icons
$iconsDir = kirby()->root('index') . '/assets/icons';
$icons = [];
if (is_dir($iconsDir)) {
  foreach (glob($iconsDir . '/*.svg') as $file) {
    $icons[] = [
      'name' => basename($file, '.svg'),
      'svg'  => file_get_contents($file)
    ];
  }
  // נגביל ל-24 אם יש יותר
  if (count($icons) > 24) $icons = array_slice($icons, 0, 24);
}
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>יצירת קטגוריה</title>
  <style>
    :root{ --stroke:#000; --bg:#fff; --fg:#000; --muted:#666; }
    *{ box-sizing:border-box; }
    html,body{ margin:0; padding:0; background:#fff; color:#000; font-family:system-ui,-apple-system,Segoe UI,Roboto; }
    .container{ padding:16px; max-width:800px; margin:0 auto; }
    .topbar{ display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
    .btn{ border:1px solid var(--stroke); border-radius:12px; padding:8px 12px; background:#fff; cursor:pointer; text-decoration:none; color:#000; }
    .panel{ border:1px solid var(--stroke); border-radius:16px; padding:16px; }
    .field{ display:grid; gap:8px; margin-bottom:14px; }
    label{ font-weight:600; }
    input[type="text"]{ border:1px solid var(--stroke); border-radius:10px; padding:10px; }
    .swatches{ display:grid; grid-template-columns: repeat(8, 1fr); gap:8px; }
    .swatch{ width:100%; aspect-ratio: 6/4; border:1px solid var(--stroke); border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
    .swatch.selected{ outline:2px solid #000; }
    .icons{ display:grid; grid-template-columns: repeat(8, 1fr); gap:8px; margin-top:6px; }
    .iconopt{ border:1px solid var(--stroke); border-radius:10px; background:#fff; height:56px; display:flex; align-items:center; justify-content:center; cursor:pointer; }
    .iconopt.selected{ outline:2px solid #000; }
    .row{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .muted{ color:var(--muted); }
  </style>
</head>
<body>
  <main class="container">
    <header class="topbar">
      <h1 style="margin:0;">קטגוריה חדשה</h1>
      <nav><a class="btn" href="<?= url('flashcards') ?>">← חזרה</a></nav>
    </header>

    <section class="panel">
      <form id="catForm">
        <div class="field">
          <label for="title">שם הקטגוריה</label>
          <input type="text" id="title" required placeholder="למשל: אטומים, DNA, חומצות אמינו">
        </div>

        <div class="field">
          <label>רקע / צבע</label>
          <div class="swatches" id="swatches"></div>
          <small class="muted">צבעים רוויים. ניתן לשנות אחר כך.</small>
        </div>

        <div class="field">
          <label>אייקון</label>
          <?php if (empty($icons)): ?>
            <p class="muted">לא נמצאו אייקונים בתיקייה <code>assets/icons</code>. ניתן להעלות עד 24 קבצי SVG ולהרענן.</p>
          <?php else: ?>
            <div class="icons" id="icons">
              <?php foreach ($icons as $ico): ?>
                <button type="button" class="iconopt" data-name="<?= html($ico['name']) ?>">
                  <?= $ico['svg'] ?>
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="row">
          <button class="btn" type="submit">שמירה</button>
          <span id="msg" class="muted" aria-live="polite"></span>
        </div>
      </form>
    </section>
  </main>

  <script>
    const PALETTE = [
      '#FF4D4F','#FF7A45','#FAAD14','#FADB14','#13C2C2','#36CFC9','#40A9FF','#597EF7',
      '#9254DE','#F759AB','#D9363E','#D46B08','#D48806','#7CB305','#08979C','#0E77FF',
      '#2F54EB','#722ED1','#EB2F96','#52C41A','#73D13D','#FFC53D','#FFA940','#5CDBD3'
    ];

    const swBox = document.getElementById('swatches');
    let selectedColor = PALETTE[0];
    PALETTE.forEach(col=>{
      const b = document.createElement('button');
      b.type='button'; b.className='swatch'; b.style.background=col;
      b.title = col;
      if(col===selectedColor) b.classList.add('selected');
      b.addEventListener('click', ()=>{
        swBox.querySelectorAll('.swatch').forEach(x=>x.classList.remove('selected'));
        b.classList.add('selected');
        selectedColor = col;
      });
      swBox.appendChild(b);
    });

    let selectedIcon = '';
    const iconsWrap = document.getElementById('icons');
    if (iconsWrap){
      iconsWrap.querySelectorAll('.iconopt').forEach(el=>{
        el.addEventListener('click', ()=>{
          iconsWrap.querySelectorAll('.iconopt').forEach(x=>x.classList.remove('selected'));
          el.classList.add('selected');
          selectedIcon = el.getAttribute('data-name') || '';
        });
      });
    }

    async function postJSON(url, payload){
      try{
        const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const t = await r.text(); try{ return JSON.parse(t);}catch{return {ok:false,error:t||r.statusText||('HTTP '+r.status)}}
      }catch(e){ return {ok:false,error:e.message||'Network error'} }
    }

    const form = document.getElementById('catForm');
    const msg  = document.getElementById('msg');
    const title= document.getElementById('title');

    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const t = (title.value||'').trim();
      if (!t){ title.focus(); return; }
      msg.textContent='שומר…';

      const res = await postJSON('<?= url('categories/add') ?>', {
        title: t,
        background: selectedColor,
        icon: selectedIcon
      });

      if(!res.ok){ msg.textContent = 'שגיאה: ' + (res.error||''); return; }

      // בהתאם ל"חוויית המשכיות": נשארים/חוזרים לבית אחרי יצירה
      location.href = '<?= url('flashcards') ?>';
    });
  </script>
</body>
</html>