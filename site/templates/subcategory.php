<?php
/** @var Kirby\Cms\Page $page */
use Kirby\Toolkit\F;

try {
  header('Content-Type: text/html; charset=utf-8');

  // $page אמור להיות תת־קטגוריה (template: subcategory)
  $sub = $page;
  $cat = $page->parent();
  $catSlug = $cat?->slug();
  $subSlug = $sub?->slug();

  function progress_read_sub(): array {
    try {
      $file = kirby()->root('content').'/.flashcards/progress.json';
      return file_exists($file) ? (json_decode(F::read($file), true) ?: []) : [];
    } catch (\Throwable $e) {
      error_log('Error reading progress: ' . $e->getMessage());
      return [];
    }
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
$efSum = 0.0; $efCount = 0;
foreach ($cards as $c) {
  $row = $progress[$c->id()] ?? null;
  if ($row) {
    $seen    += (int)($row['seen']    ?? 0);
    $correct += (int)($row['correct'] ?? 0);
    if (!empty($row['dueAt']) && strtotime($row['dueAt']) <= $today) $dueToday++;
    if (isset($row['easiness'])) { $efSum += (float)$row['easiness']; $efCount++; }
  }
}
$avgEf = $efCount ? ($efSum / $efCount) : null;
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?= snippet('global-head') ?>
  <title><?= html($cat?->title()) ?> · <?= html($sub?->title()) ?></title>
  <style>
    .list {
      display:flex; flex-direction:column; gap:0;
      border:1px solid var(--stroke); border-radius:10px; overflow:hidden; background:#fff;
    }
    .rowcard{
      display:grid; grid-template-columns: 1fr 240px auto; gap:6px; align-items:center;
      padding:6px 8px; border-bottom:1px solid var(--stroke);
      background:#fff; /* table-like rows */
    }
    .rowcard:last-child{ border-bottom:none; }
    .qtitle{ font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; font-size:14px; }
  .qstats{ color:var(--ink-muted); font-size:12px; display:flex; gap:12px; flex-wrap:wrap; justify-content:flex-start; }
    .rowactions{ display:flex; gap:4px; align-items:center; justify-content:flex-start; }

    

    @media (max-width: 880px){
      .rowcard{ grid-template-columns: 1fr auto; row-gap:4px; }
      .qstats{ grid-column: 1 / -1; }
    }
    .editpane{ display:none; grid-column:1 / -1; border-top:1px dashed var(--stroke); padding-top:10px; }
    .editpane.show{ display:block; }
    .editpane textarea{ width:100%; min-height:120px; border:1px solid var(--stroke); border-radius:10px; padding:10px; }

    /* Floating Add Card button */
    .fab-add{
      position:fixed; bottom:20px; left:20px; z-index:900;
      width:56px; height:56px; border-radius:50%;
      display:flex; align-items:center; justify-content:center;
      font-size:28px; line-height:1; text-decoration:none;
      background: var(--brand, #4f46e5); color:#fff; border:none;
      box-shadow: var(--shadow, 0 6px 18px rgba(0,0,0,.15));
    }
    .fab-add:hover{ filter:brightness(.95); }
    @media (max-width:480px){
      .fab-add{ width:52px; height:52px; font-size:26px; bottom:16px; left:16px; }
    }
    .fab-add svg{ width:28px; height:28px; display:block; stroke: currentColor; fill: none; }
  </style>
</head>
<body>
  <main class="container">
    <header class="topbar">
      <h1><?= html($sub?->title()) ?></h1>
      <nav class="nav">
        <a class="btn ghost" href="<?= url('flashcards/'.$catSlug) ?>">← חזרה לקטגוריה</a>
        <a class="btn" href="<?= url('flashcards/add') . '?category=' . urlencode($catSlug) . '&subcategory=' . urlencode($subSlug) ?>">הוסף כרטיסייה</a>
        <a class="btn" href="<?= url('flashcards/test') . '?category=' . urlencode($catSlug) . '&subcategory=' . urlencode($subSlug) . '&auto=1' ?>">מבחן בתת־קטגוריה</a>
      </nav>
    </header>

    <section class="panel">
      <div class="row">
        <div class="kpi">סה״כ כרטיסיות: <strong><?= $count ?></strong></div>
        <div class="kpi">נסקרו (סה״כ): <strong><?= $seen ?></strong></div>
        <div class="kpi">נכונות (סה״כ): <strong><?= $correct ?></strong></div>
        <div class="kpi">Due היום: <strong><?= $dueToday ?></strong></div>
        <?php if ($avgEf !== null): ?>
          <div class="kpi">ממוצע EF: <strong><?= round($avgEf, 2) ?></strong></div>
        <?php endif; ?>
      </div>
    </section>

    <section>
      <h2>כרטיסיות</h2>
      <div class="list" id="cardList">
        <?php foreach ($cards as $c):
          $row = $progress[$c->id()] ?? ['seen'=>0,'correct'=>0,'box'=>3,'easiness'=>2.5];
          // שימוש בכותרת הכרטיסייה במקום הטקסט, עם עיבוד Cloze
          $rawTitle = $c->title()->value();
          $cardType = $c->type()->value() ?: 'free';
          $title = $rawTitle !== '' ? display_cloze_title($rawTitle, $cardType) : 'כרטיסייה ללא כותרת';
        ?>
        <div class="rowcard" data-id="<?= html($c->id()) ?>">
          <div class="qtitle"><?= html($title) ?></div>
          <div class="qstats">
            <?php $seenRow = (int)($row['seen'] ?? 0); $correctRow = (int)($row['correct'] ?? 0); $efRow = isset($row['easiness']) ? round((float)$row['easiness'],2) : 2.5; ?>
            <span><?= $seenRow ?>/<?= $correctRow ?></span>
            <span>EF: <?= $efRow ?></span>
          </div>
          <div class="rowactions">
            <button class="icon-btn" data-preview title="תצוגה מקדימה" aria-label="תצוגה מקדימה">
              <svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3.5"/></svg>
            </button>
            <a class="icon-btn" href="<?= url('flashcards/add') . '?id=' . urlencode($c->id()) ?>" title="עריכה" aria-label="עריכה">
              <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
            </a>
            <button class="icon-btn danger" data-delete title="מחק" aria-label="מחק">
              <svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if ($count === 0): ?>
          <div class="muted">אין עדיין כרטיסיות כאן.</div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <!-- Floating Add Card button -->
  <a
    class="fab-add"
    href="<?= url('flashcards/add') . '?category=' . urlencode($catSlug) . '&subcategory=' . urlencode($subSlug) ?>"
    title="הוסף כרטיסייה"
    aria-label="הוסף כרטיסייה"
  >
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-width="2" />
    </svg>
  </a>

  <!-- modal לתצוגה מקדימה -->
  <div id="previewModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; padding:20px; box-sizing:border-box;">
    <div style="background:white; border-radius:12px; max-width:var(--container-w); width:100%; margin:0 auto; max-height:90vh; overflow-y:auto; padding:20px; position:relative;">
      <button id="closePreview" style="position:absolute; top:15px; left:15px; background:none; border:none; font-size:20px; cursor:pointer;">✕</button>
      <div id="previewContent">
        <!-- תוכן הpreview יוכנס כאן -->
      </div>
    </div>
  </div>

  <script>
    const $$ = (s,ctx=document)=>Array.from(ctx.querySelectorAll(s));
    const $ = (s,ctx=document)=>ctx.querySelector(s);
    
    async function postJSON(url, payload){
      try{
        const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const t = await r.text(); try{ return JSON.parse(t); }catch{return {ok:false,error:t||r.statusText||('HTTP '+r.status)}}
      }catch(e){ return {ok:false,error:e.message||'Network error'} }
    }

    // משתנים למודל preview
    const previewModal = $('#previewModal');
    const closePreviewBtn = $('#closePreview');
    const previewContent = $('#previewContent');

    // פונקציה לטעינת נתוני כרטיס
    async function loadCardData(cardId) {
      const baseApi = `<?= url('api/card') ?>`;
      const urlApi = `${baseApi}/${encodeURI(cardId)}`; // encode URI, keep slashes
      try {
        const response = await fetch(urlApi);
        if (!response.ok) throw new Error(`HTTP ${response.status} ${response.statusText}`);
        return await response.json();
      } catch (error) {
        console.warn('Primary API failed, trying fallback /card?id=…', error);
        // Fallback to legacy endpoint that accepts id as query param
        try {
          const fallback = `<?= url('card') ?>?id=${encodeURIComponent(cardId)}`;
          const r = await fetch(fallback);
          if (!r.ok) throw new Error(`HTTP ${r.status} ${r.statusText}`);
          const j = await r.json();
          // Adapt to the expected shape { id, type, question, answer, title }
          if (j && j.ok && j.card) {
            return {
              id: j.card.id,
              type: j.card.type || 'free',
              question: j.card.question_raw || '',
              answer: j.card.answer_raw || '',
              title: ''
            };
          }
          throw new Error('Unexpected fallback payload');
        } catch (err2) {
          console.error('Error loading card (both endpoints):', err2);
          return { __error: String(err2 && err2.message || err2 || 'Unknown error') };
        }
      }
    }

    // פונקציה ליצירת preview HTML בהתאמה לעמוד המבחן (מצב גלוי)
    function createPreviewHTML(card) {
      const { type, question, answer } = card;
      const escapeHtml = s => (s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]));
      const renderClozeQuestion = html => (html||'').replace(/\{\{\s*(\d+)\s*\}\}/g, (_m, num) => `<span class="cloze-blank" data-id="${num}"><sup>${num}</sup><input type="text" inputmode="text" autocomplete="off" /></span>`);

      let qHtml = (type === 'cloze') ? renderClozeQuestion(question) : question;
      let body = `
        <div class="qa-container test-deck">
          <div class="test-card" tabindex="0" aria-live="polite">
            <div class="question-section">
              <div class="ck-content">${qHtml}</div>
            </div>
            <div class="interaction-area">
      `;

      if (type === 'free') {
        body += `
              <div class="free-interaction">
                <div class="answer-display ck-content">${answer || ''}</div>
              </div>
        `;
      } else if (type === 'mc') {
        let options = [];
        try { options = (JSON.parse(answer||'{}').options) || []; } catch(e) { options = []; }
        const optsHtml = options.map(o => `<div class="mc-option ${o.correct ? 'correct' : ''}">${escapeHtml(o.text||'')}</div>`).join('');
        body += `
              <div class="mc-interaction">
                <div class="mc-options">${optsHtml}</div>
                <div class="result-display result-correct">התשובה הנכונה מסומנת בירוק</div>
              </div>
        `;
      } else if (type === 'tf') {
        let correctVal = true; try { const j = JSON.parse(answer||'{}'); correctVal = !!j.value; } catch(e) {}
        const tTrueClass = correctVal ? 'tf-option result-correct' : 'tf-option';
        const tFalseClass = !correctVal ? 'tf-option result-correct' : 'tf-option';
        body += `
              <div class="tf-interaction">
                <div class="tf-buttons">
                  <button class="btn ${tTrueClass}">נכון</button>
                  <button class="btn ${tFalseClass}">לא נכון</button>
                </div>
                <div class="result-display result-correct">זו התשובה הנכונה</div>
              </div>
        `;
      } else if (type === 'cloze') {
        let correctAnswers = {};
        try {
          const ans = JSON.parse(answer||'{}');
          if (ans && Array.isArray(ans.blanks)) {
            ans.blanks.forEach(b=>{ if (b && b.id != null) correctAnswers[b.id] = (b.answers||[])[0] || ''; });
          }
        } catch(e) {}
        let full = question;
        Object.keys(correctAnswers).forEach(id=>{
          const corr = String(correctAnswers[id]||'');
          const tag = `<span style="background:#d4edda;color:#155724;padding:2px 6px;border-radius:4px;font-weight:bold;">${escapeHtml(corr)}</span>`;
          const re = new RegExp(`\\{\\{\\s*${id}\\s*\\}}`, 'g');
          full = full.replace(re, tag);
        });
        body += `
              <div class="cloze-interaction">
                <div class="result-display result-correct">
                  <div style="margin-bottom:8px;"><strong>התשובה המלאה:</strong></div>
                  <div class="ck-content" style="text-align:right;">${full}</div>
                </div>
              </div>
        `;
      } else if (type === 'label') {
        body += `
              <div><em>תצוגה מקדימה לסוג "תיוג" במצב גלוי אינה נתמכת כאן</em></div>
        `;
      }

      body += `
            </div>
          </div>
        </div>
      `;

      return body;
    }

    document.addEventListener('click', async (ev)=>{
      const previewBtn = ev.target.closest('[data-preview]');
      const deleteBtn  = ev.target.closest('[data-delete]');
      if (!previewBtn && !deleteBtn) return;

      const row = ev.target.closest('[data-id]');
      if (!row) return;
      const id  = row.getAttribute('data-id');

      if (previewBtn){
        // הצגת loading
        previewContent.innerHTML = '<div style="text-align:center; padding:40px;"><p>טוען...</p></div>';
        previewModal.style.display = 'flex';
        previewModal.style.alignItems = 'center';
        previewModal.style.justifyContent = 'center';

        // טעינת נתוני הכרטיס
        const cardData = await loadCardData(id);
        if (cardData && !cardData.__error) {
          previewContent.innerHTML = createPreviewHTML(cardData);
        } else {
          const errText = cardData && cardData.__error ? ` (${cardData.__error})` : '';
          previewContent.innerHTML = `<div style="text-align:center; padding:40px; color:#999;"><p>שגיאה בטעינת הכרטיסייה${errText}</p></div>`;
        }
      }

      if (deleteBtn){
        ev.preventDefault();
        // מחיקה מידית ללא אישור
        deleteBtn.classList.add('processing');
        deleteBtn.style.pointerEvents = 'none';
        const prevOpacity = row.style.opacity;
        const prevPointer = row.style.pointerEvents;
        row.style.opacity = '0.5';
        row.style.pointerEvents = 'none';
        const res = await postJSON('<?= url('cards/delete') ?>', { id });
        if (res.ok) {
          row.remove();
        } else {
          // שחזור מצב במידה ונכשלה המחיקה
          deleteBtn.classList.remove('processing');
          deleteBtn.style.pointerEvents = '';
          row.style.opacity = prevOpacity;
          row.style.pointerEvents = prevPointer;
          alert('שגיאה: ' + (res.error||''));
        }
      }
    });

    // סגירת מודל preview
    closePreviewBtn.addEventListener('click', () => {
      previewModal.style.display = 'none';
    });

    previewModal.addEventListener('click', (e) => {
      if (e.target === previewModal) {
        previewModal.style.display = 'none';
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && previewModal.style.display !== 'none') {
        previewModal.style.display = 'none';
      }
    });

    // וידוא שכל הכפתורים במצב רגיל בטעינת הדף
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.icon-btn.danger').forEach(btn => {
        btn.disabled = false;
        btn.classList.remove('processing');
        btn.style.opacity = '';
        btn.style.pointerEvents = '';
      });
    });
  </script>
</body>
</html>
<?php
} catch (\Throwable $e) {
  error_log('Subcategory template error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
  echo '<h1>שגיאה בטעינת התת-קטגוריה</h1>';
  echo '<p>פרטים: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>