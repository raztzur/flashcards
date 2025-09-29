<?php
/** @var Kirby\Cms\Page $page */
use Kirby\Toolkit\F;

header('Content-Type: text/html; charset=utf-8');

$root = page('flashcards');
$allCards = $root ? $root->children()->filterBy('intendedTemplate','category')->children()->filterBy('intendedTemplate','card') : [];
$totalCards = $allCards->count();

$storage = kirby()->root('content') . '/.flashcards';
if (!is_dir($storage)) { @mkdir($storage, 0775, true); }
$progressFile = $storage . '/progress.json';
$progress = file_exists($progressFile) ? (json_decode(F::read($progressFile), true) ?: []) : [];

$today = date('Y-m-d');
$reviewedToday=0; $correctToday=0;
$easeVals = [];
$boxes = [1=>0,2=>0,3=>0,4=>0,5=>0];

foreach ($allCards as $c){
  $row = $progress[$c->id()] ?? null;
  if ($row){
    if (isset($row['easiness'])) $easeVals[] = (float)$row['easiness'];
    $box = (int)($row['box'] ?? 3); if ($box<1||$box>5) $box=3; $boxes[$box]++;
    if (!empty($row['updatedAt']) && date('Y-m-d', strtotime($row['updatedAt'])) === $today){
      $reviewedToday++;
      if (!empty($row['lastQuality']) && (int)$row['lastQuality'] >= 4) $correctToday++;
    }
  }
}
$avgEase = count($easeVals) ? round(array_sum($easeVals)/count($easeVals),2) : 2.5;

// בניית היסטורמה של EF (1.3–2.5)
$bins = [
  ['label'=>'קשה (1.3–1.7)','from'=>1.3,'to'=>1.7,'count'=>0],
  ['label'=>'בינוני (1.7–2.1)','from'=>1.7,'to'=>2.1,'count'=>0],
  ['label'=>'קל (2.1–2.5)','from'=>2.1,'to'=>2.5,'count'=>0],
];
foreach ($easeVals as $ef){
  foreach ($bins as &$b){
    if ($ef >= $b['from'] && $ef < $b['to'] + 0.0001){ $b['count']++; break; }
  }
}
unset($b);
?>
<!doctype html><html lang="he" dir="rtl">
<head>
  <meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>סטטיסטיקות</title><?= snippet('global-head') ?>
</head><body><main class="container">

<header class="topbar">
  <h1>סטטיסטיקות</h1>
  <nav class="nav"><a class="btn" href="<?= url('flashcards') ?>">← חזרה</a></nav>
</header>

<section class="stats-filters">
  <button class="btn small" aria-pressed="true">היום</button>
  <button class="btn small">7 ימים</button>
  <button class="btn small">30 ימים</button>
</section>

<section class="stats-grid">
  <div class="kpi-card">
    <div class="kpi-label">כרטיסים נסקרו</div>
    <div class="kpi-value">34</div>
    <div class="kpi-trend">+12% מהשבוע שעבר</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">דיוק</div>
    <div class="kpi-value">78%</div>
    <div class="kpi-trend">-3% מהיום</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">לתרגול היום</div>
    <div class="kpi-value">18</div>
  </div>
</section>

<section class="panel" style="margin-top:16px">
  <table class="stats-table">
    <thead><tr>
      <th>קטגוריה</th><th>תתי־קטגוריות</th><th>כרטיסים</th><th>דיוק</th><th>Due</th>
    </tr></thead>
    <tbody>
      <tr><td>ביוכימיה</td><td>5</td><td>120</td><td><span class="badge">82%</span></td><td>9</td></tr>
      <tr><td>פיזיולוגיה</td><td>3</td><td>73</td><td><span class="badge">75%</span></td><td>6</td></tr>
    </tbody>
  </table>
</section>
</main>

  <script>
    // נתונים מה-PHP
    const EF_BINS = <?= json_encode($bins, JSON_UNESCAPED_UNICODE) ?>;
    const BOXES = <?= json_encode($boxes, JSON_UNESCAPED_UNICODE) ?>;

    function drawBarChart(canvas, labels, values){
      const ctx = canvas.getContext('2d');
      const W = canvas.width, H = canvas.height;
      ctx.clearRect(0,0,W,H);

      const maxVal = Math.max(1, ...values);
      const pad = 32;
      const innerW = W - pad*2;
      const innerH = H - pad*2;

      // axes
      ctx.strokeStyle = '#000';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(pad, pad); ctx.lineTo(pad, H - pad); ctx.lineTo(W - pad, H - pad);
      ctx.stroke();

      const n = values.length;
      const gap = 12;
      const barW = (innerW - gap*(n-1)) / n;

      ctx.fillStyle = '#000';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'top';

      values.forEach((v, i) => {
        const x = pad + i*(barW + gap);
        const h = (v / maxVal) * (innerH - 20);
        const y = (H - pad) - h;
        // bar
        ctx.fillRect(x, y, barW, h);
        // label
        ctx.save();
        ctx.translate(x + barW/2, H - pad + 4);
        ctx.rotate(-Math.PI/12);
        ctx.fillText(labels[i], 0, 0);
        ctx.restore();
        // value on top
        ctx.textBaseline = 'bottom';
        ctx.fillText(String(v), x + barW/2, y - 4);
        ctx.textBaseline = 'top';
      });
    }

    // EF chart
    (function(){
      const labels = EF_BINS.map(b=>b.label);
      const values = EF_BINS.map(b=>b.count);
      const c = document.getElementById('efChart');
      // התאמה לרזולוציה (HiDPI)
      const dpr = window.devicePixelRatio || 1;
      c.width = c.clientWidth * dpr;
      c.height = c.clientHeight * dpr;
      const ctx = c.getContext('2d'); ctx.scale(dpr,dpr);
      drawBarChart(c, labels, values);
    })();

    // Box chart
    (function(){
      const labels = Object.keys(BOXES).map(k=>'Box '+k);
      const values = Object.values(BOXES);
      const c = document.getElementById('boxChart');
      const dpr = window.devicePixelRatio || 1;
      c.width = c.clientWidth * dpr;
      c.height = c.clientHeight * dpr;
      const ctx = c.getContext('2d'); ctx.scale(dpr,dpr);
      drawBarChart(c, labels, values);
    })();
  </script>
</body>
</html>