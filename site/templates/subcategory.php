<?php
/** @var Kirby\Cms\Page $page */
header('Content-Type: text/html; charset=utf-8');

// $page אמור להיות תת־קטגוריה (template: subcategory)
$sub = $page;
$cat = $page->parent();
$catSlug = $cat?->slug();
$subSlug = $sub?->slug();

use Kirby\Toolkit\F;
function progress_read_sub(): array {
  $file = kirby()->root('content').'/.flashcards/progress.json';
  return file_exists($file) ? (json_decode(F::read($file), true) ?: []) : [];
}

// פונקציה להצגת כותרות Cloze עם קווים תחתונים
function display_cloze_title($title, $type) {
  if ($type === 'cloze') {
    return preg_replace('/\{\{\s*\d+\s*\}\}/', '____', $title);
  }
  return $title;
}
$progress = progress_read_sub();

$cards = $sub->children()->filterBy('intendedTemplate','card');
$count = $cards->count();

// סטטיסטיקות בסיסיות
$seen = 0; $correct = 0; $dueToday = 0; $today = time();
foreach ($cards as $c) {
  $row = $progress[$c->id()] ?? null;
  if ($row) {
    $seen    += (int)($row['seen']    ?? 0);
    $correct += (int)($row['correct'] ?? 0);
    if (!empty($row['dueAt']) && strtotime($row['dueAt']) <= $today) $dueToday++;
  }
}
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?= snippet('global-head') ?>
  <title><?= html($cat?->title()) ?> · <?= html($sub?->title()) ?></title>
  <style>
    .list { display:flex; flex-direction:column; gap:10px; }
    .rowcard{
      border:1px solid var(--stroke); border-radius:14px; background:#fff; box-shadow:var(--shadow);
      padding:12px; display:grid; grid-template-columns: 1fr auto; gap:10px; align-items:center;
    }
    .qmeta{ display:flex; flex-direction:column; gap:6px; min-width:0; }
    .qtitle{ font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .qstats{ color:var(--ink-muted); font-size:13px; display:flex; gap:12px; flex-wrap:wrap; }
    .rowactions{ display:flex; gap:6px; align-items:center; }
    .editpane{ display:none; grid-column:1 / -1; border-top:1px dashed var(--stroke); padding-top:10px; }
    .editpane.show{ display:block; }
    .editpane textarea{ width:100%; min-height:120px; border:1px solid var(--stroke); border-radius:10px; padding:10px; }
  </style>
</head>
<body>
  <main class="container">
    <header class="topbar">
      <h1><?= html($sub?->title()) ?></h1>
      <nav class="nav">
        <a class="btn ghost" href="<?= url('flashcards/'.$catSlug) ?>">← חזרה לקטגוריה</a>
        <a class="btn" href="<?= url('flashcards/add') . '?category=' . urlencode($catSlug) . '&subcategory=' . urlencode($subSlug) ?>">הוסף כרטיס</a>
        <a class="btn" href="<?= url('flashcards/test') . '?category=' . urlencode($catSlug) . '&subcategory=' . urlencode($subSlug) . '&auto=1' ?>">מבחן בתת־קטגוריה</a>
      </nav>
    </header>

    <section class="panel">
      <div class="row">
        <div class="kpi">סה״כ כרטיסים: <strong><?= $count ?></strong></div>
        <div class="kpi">נסקרו (סה״כ): <strong><?= $seen ?></strong></div>
        <div class="kpi">נכונות (סה״כ): <strong><?= $correct ?></strong></div>
        <div class="kpi">Due היום: <strong><?= $dueToday ?></strong></div>
      </div>
    </section>

    <section>
      <h2>כרטיסים</h2>
      <div class="list" id="cardList">
        <?php foreach ($cards as $c):
          $row = $progress[$c->id()] ?? ['seen'=>0,'correct'=>0,'box'=>3,'easiness'=>2.5];
          // שימוש בכותרת הכרטיס במקום השאלה, עם עיבוד Cloze
          $rawTitle = $c->title()->value();
          $cardType = $c->type()->value() ?: 'free';
          $title = $rawTitle !== '' ? display_cloze_title($rawTitle, $cardType) : 'כרטיס ללא כותרת';
        ?>
        <div class="rowcard" data-id="<?= html($c->id()) ?>">
          <div class="qmeta">
            <div class="qtitle"><?= html($title) ?></div>
            <div class="qstats">
              <span>נראה: <?= (int)($row['seen'] ?? 0) ?></span>
              <span>נכון: <?= (int)($row['correct'] ?? 0) ?></span>
              <span>EF: <?= isset($row['easiness']) ? round((float)$row['easiness'],2) : 2.5 ?></span>
              <span>קופסה: <?= (int)($row['box'] ?? 3) ?></span>
            </div>
          </div>
          <div class="rowactions">
            <a class="btn small" href="<?= url('flashcards/add') . '?id=' . urlencode($c->id()) ?>">עריכה</a>
            <button class="btn small danger" data-delete>מחק</button>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if ($count === 0): ?>
          <div class="muted">אין עדיין כרטיסים כאן.</div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <script>
    const $$ = (s,ctx=document)=>Array.from(ctx.querySelectorAll(s));
    async function postJSON(url, payload){
      try{
        const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const t = await r.text(); try{ return JSON.parse(t); }catch{return {ok:false,error:t||r.statusText||('HTTP '+r.status)}}
      }catch(e){ return {ok:false,error:e.message||'Network error'} }
    }

    document.addEventListener('click', async (ev)=>{
      const row = ev.target.closest('[data-id]'); if(!row) return;
      const id  = row.getAttribute('data-id');

      if (ev.target.matches('[data-delete]')){
        if (ev.target.dataset.armed === '1'){
          ev.target.disabled = true;
          const res = await postJSON('<?= url('cards/delete') ?>', { id });
          if (res.ok) row.remove();
          else {
            ev.target.disabled = false; ev.target.textContent = 'מחק'; ev.target.dataset.armed = '0';
            alert('שגיאה: ' + (res.error||''));
          }
        } else {
          ev.target.dataset.armed = '1';
          const old = ev.target.textContent;
          ev.target.textContent = 'בטוח?';
          setTimeout(()=>{ ev.target.dataset.armed='0'; ev.target.textContent=old; }, 2200);
        }
      }
    });
  </script>
</body>
</html>