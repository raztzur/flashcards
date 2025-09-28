<?php
/** @var Kirby\Cms\Page $page */
use Kirby\Toolkit\F;

header('Content-Type: text/html; charset=utf-8');

$cards = $page->children()->filterBy('intendedTemplate', 'card');

// progress
$storage = kirby()->root('content') . '/.flashcards';
if (!is_dir($storage)) { @mkdir($storage, 0775, true); }
$progressFile = $storage . '/progress.json';
$progress = file_exists($progressFile) ? json_decode(F::read($progressFile), true) : [];

$total = $cards->count();
$due = 0; $seen=0; $correct=0; $avgEase=0; $eCount=0; $todayReviewed=0;
$today = date('Y-m-d');

foreach ($cards as $c) {
  $id = $c->id();
  $row = $progress[$id] ?? null;
  if ($row) {
    if (!empty($row['dueAt']) && strtotime($row['dueAt']) <= time()) $due++;
    $seen += (int)($row['seen'] ?? 0);
    $correct += (int)($row['correct'] ?? 0);
    if (isset($row['easiness'])) { $avgEase += (float)$row['easiness']; $eCount++; }
    if (!empty($row['updatedAt']) && date('Y-m-d', strtotime($row['updatedAt'])) === $today) $todayReviewed++;
  }
}
$avgEase = $eCount ? round($avgEase/$eCount,2) : 2.5;

function cardLevel(array $row): string {
  $b = (int)($row['box'] ?? 3);
  if ($b >= 4) return 'high';
  if ($b >= 2) return 'mid';
  return 'low';
}
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= html($page->title()) ?> — קטגוריה</title>
  <style>
    :root{ --stroke:#000; --bg:#fff; --fg:#000; --radius:16px; --muted:#666; }
    *{ box-sizing:border-box; }
    html,body{ margin:0; padding:0; background:var(--bg); color:var(--fg);
      font-family:system-ui, -apple-system, Segoe UI, Roboto; }
    .container{ padding:16px; max-width:1100px; margin:0 auto; }
    .topbar{ display:flex; gap:12px; align-items:center; justify-content:space-between; }
    .nav{ display:flex; gap:8px; flex-wrap:wrap; }
    .btn{ border:1px solid var(--stroke); border-radius:12px; padding:8px 12px; background:#fff; cursor:pointer; text-decoration:none; color:#000; }
    .panel{ border:1px solid var(--stroke); border-radius:16px; padding:12px; margin-top:12px; }
    .row{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .meter{ width:18px; height:18px; border-radius:50%; border:1px solid var(--stroke); background:#eee; }
    .meter[data-level="low"]{ background:#ffd6d6; }
    .meter[data-level="mid"]{ background:#fff4c2; }
    .meter[data-level="high"]{ background:#d6ffd9; }
    .list{ display:grid; gap:8px; margin-top:12px; }
    .item{ display:grid; grid-template-columns: 1fr auto auto; gap:8px; align-items:center;
      border:1px solid var(--stroke); border-radius:12px; padding:10px; }
    .muted{ color:var(--muted); }
    @media (max-width:720px){ .item{ grid-template-columns: 1fr auto; } }
    .btn.icon{ padding:6px; display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; }
    svg{ width:18px; height:18px; }
  </style>
</head>
<body>
  <main class="container">
    <header class="topbar">
      <div class="row">
        <a class="btn" href="<?= url('flashcards') ?>">← חזרה</a>
        <h1 style="margin:0;"><?= html($page->title()) ?></h1>
      </div>
      <nav class="nav">
        <a class="btn" href="<?= url('flashcards/add') . '?category=' . urlencode($page->slug()) ?>">הוסף שאלה</a>
        <a class="btn" href="<?= url('flashcards/test') . '?category=' . urlencode($page->slug()) . '&auto=1' ?>">מבחן בקטגוריה</a>
      </nav>
    </header>

    <section class="panel">
      <h3 style="margin:0 0 8px 0;">סטטיסטיקות קטגוריה</h3>
      <div class="row">
        <div class="btn">כרטיסים: <strong><?= $total ?></strong></div>
        <div class="btn">Due היום: <strong><?= $due ?></strong></div>
        <div class="btn">ניסיונות מצטבר: <strong><?= $seen ?></strong></div>
        <div class="btn">נכונים מצטבר: <strong><?= $correct ?></strong></div>
        <div class="btn" title="EF — גבוה=קל">מדד קלות ממוצע: <strong><?= $avgEase ?></strong></div>
        <div class="btn">נסקרו היום: <strong><?= $todayReviewed ?></strong></div>
      </div>

      <div class="list" id="cardsList">
        <?php if ($total === 0): ?>
          <p class="muted">אין כרטיסיות בקטגוריה זו עדיין.</p>
        <?php else: ?>
          <?php foreach ($cards as $c): ?>
            <?php
              $id = $c->id();
              $row = $progress[$id] ?? [
                'seen' => (int)$c->seen()->or(0)->value(),
                'correct' => (int)$c->correct()->or(0)->value(),
                'box' => (int)$c->box()->or(3)->value(),
              ];
              $clevel = (function($row){ $b=(int)($row['box']??3); return $b>=4?'high':($b>=2?'mid':'low'); })($row);
              $qhtml = $c->question()->kirbytext()->value();
              $qtext = strip_tags($qhtml);
              if (mb_strlen($qtext) > 140) $qtext = mb_substr($qtext, 0, 140) . '…';
            ?>
            <div class="item" data-id="<?= html($id) ?>">
              <div><?= $qtext !== '' ? html($qtext) : '<em class="muted">—</em>' ?></div>
              <div class="meter" data-level="<?= $clevel ?>"></div>
              <div class="row">
                <a class="btn icon" href="<?= url('flashcards/add') . '?category=' . urlencode($page->slug()) . '&edit=' . urlencode($id) ?>" title="עריכה">✎</a>
                <button class="btn icon" data-del title="מחיקה">🗑️</button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <script>
    async function api(path, opts){
      try{
        const r = await fetch(path, opts);
        const t = await r.text();
        try { return JSON.parse(t); } catch { return { ok:false, error: t || r.statusText || ('HTTP '+r.status) }; }
      } catch(e){ return { ok:false, error: e.message || 'Network error' }; }
    }
    async function deleteCard(id){
      return api('/cards/delete', {
        method: 'POST',
        headers: { 'Content-Type':'application/json' },
        body: JSON.stringify({ id })
      });
    }

    document.querySelectorAll('[data-del]').forEach(btn => {
      let armed = false, timer = null;
      const disarm = () => { armed=false; btn.classList.remove('danger'); btn.textContent='🗑️'; if (timer){ clearTimeout(timer); timer=null; } };
      btn.addEventListener('click', async () => {
        const row = btn.closest('.item');
        const id = row?.getAttribute('data-id');
        if (!id) return;
        if (!armed){ armed=true; btn.classList.add('danger'); btn.textContent='בטוח?'; timer=setTimeout(disarm,3000); return; }
        btn.disabled = true;
        const del = await deleteCard(id);
        if (!del.ok){ alert('שגיאה במחיקה: ' + (del.error || 'unknown')); btn.disabled=false; disarm(); return; }
        row.remove();
      });
      document.addEventListener('click', (ev)=>{ if (!btn.contains(ev.target)) disarm(); });
    });
  </script>
</body>
</html>