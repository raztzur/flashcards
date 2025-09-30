<?php
/** @var Kirby\Cms\Page $page */
header('Content-Type: text/html; charset=utf-8');

$root = page('flashcards');
$initialCat = get('category'); // /flashcards/test?category=<slug>&auto=1
$initialSub = get('subcategory') ?? get('sub');
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>מבחן</title>
  <?= snippet('global-head') ?>
  <style>
    /* CSS variables are now defined in the main style.css file */
    
    .qa{ display:flex; flex-direction:column; gap:12px; }
    .question-area{
      min-height: var(--question-h);
      max-height: var(--question-h);
      overflow:auto;
      /* ללא מסגרת/רקע - שאלה "ערומה" בתוך ה-div */
      padding:0; border:0; background:transparent;
    }
    .answer-box{
      border:1px solid var(--stroke);
      border-radius:12px;
      padding:12px;
      background:#fff;
      height:var(--answer-h);       /* גובה קבוע גם כשהתוכן מוסתר */
      overflow:hidden;              /* התוכן יגלול בתוך הקופסה */
    }

    /* תוכן התשובה מגלול בתוך הקופסה הקבועה */
    #aHtml{ max-height:100%; overflow:auto; }

    /* סגנונות לכפתורי איכות מסומנים */
    .quality-bad.selected { 
      background-color: #dc3545; 
      color: white; 
      border-color: #c82333; 
      box-shadow: 0 0 8px rgba(220, 53, 69, 0.5);
    }
    .quality-mid.selected { 
      background-color: #ffc107; 
      color: #212529; 
      border-color: #e0a800; 
      box-shadow: 0 0 8px rgba(255, 193, 7, 0.5);
    }
    .quality-good.selected { 
      background-color: #28a745; 
      color: white; 
      border-color: #1e7e34; 
      box-shadow: 0 0 8px rgba(40, 167, 69, 0.5);
    }

    /* מוסתר אבל שומר על הגובה של הקופסה */
    .answer-hidden{ visibility:hidden; pointer-events:none; }
  </style>
</head>
<body>
<main class="container">

  <header class="topbar test-header">
    <h1>מבחן</h1>
    <div class="test-controls">
      <a href="<?= url('flashcards') ?>" class="btn">← חזרה</a>
    </div>
  </header>

  <!-- שלב ההגדרות -->
  <section id="setup" class="test-deck" aria-label="הגדרת מבחן">
    <div class="field">
      <label for="cat">קטגוריה</label>
      <select id="cat">
        <option value="">כל הקטגוריות</option>
      </select>
    </div>

    <div class="field">
      <label for="sub">תת־קטגוריה</label>
      <select id="sub" disabled>
        <option value="">— בחרי קטגוריה תחילה —</option>
      </select>
    </div>

    <div class="field">
      <label>כמה כרטיסיות?</label>
      <div id="sizeChips" class="test-size" role="group" aria-label="מספר כרטיסיות לסשן">
        <button class="chip" data-size="5">5</button>
        <button class="chip" data-size="10" aria-pressed="true">10</button>
        <button class="chip" data-size="15">15</button>
        <button class="chip" data-size="20">20</button>
        <button class="chip" data-size="30">30</button>
        <button class="chip" data-size="50">50</button>
      </div>
    </div>

    <div class="actions">
      <button id="start" class="btn">התחילי מבחן</button>
      <span id="setupMsg" class="form-msg" aria-live="polite"></span>
    </div>
  </section>

  <!-- תצוגת HUD -->
  <section id="hud" class="panel" style="display:none" aria-label="סטטוס מבחן">
    <div class="test-info">
      <span id="hudCat">כללי</span>
      <span>זמן: <strong id="timer">00:00</strong></span>
      <span>התקדמות: <strong id="counter">0/0</strong></span>
    </div>
    <div style="height:8px; background:#f0f2f7; border:1px solid var(--stroke); border-radius:999px; overflow:hidden;">
      <div id="pbar" style="height:100%; width:0%; background:#111;"></div>
    </div>
  </section>

  <!-- שלב הבחינה -->
  <section id="stage" style="display:none" aria-label="כרטיסייה">
    <!-- קופסת תוכן הכרטיסייה -->
    <div class="qa-container test-deck">
      <div id="card" class="test-card" tabindex="0" aria-live="polite">
        <!-- אזור השאלה -->
        <div class="question-section">
          <div id="qHtml" class="ck-content"><p>—</p></div>
        </div>
        
        <!-- אזור האינטראקציה - משתנה לפי סוג השאלה -->
        <div id="interactionArea" class="interaction-area">
          
          <!-- עבור שאלה פתוחה -->
          <div id="freeArea" class="free-interaction" style="display:none;">
            <button id="flip" class="btn">הצג תשובה</button>
            <div id="aHtml" class="answer-display ck-content answer-hidden">
              <button class="hide-answer-btn" id="hideAnswerBtn">✕</button>
              <p>—</p>
            </div>
          </div>
          
          <!-- עבור שאלה אמריקאית -->
          <div id="mcArea" class="mc-interaction" style="display:none;">
            <div class="mc-options"></div>
            <button id="mcSubmit" class="btn" disabled>בדוק תשובה</button>
            <div id="mcResult" class="result-display" style="display:none;"></div>
          </div>
          
          <!-- עבור נכון/לא נכון -->
          <div id="tfArea" class="tf-interaction" style="display:none;">
            <div class="tf-buttons">
              <button id="tfTrue" class="btn tf-option">נכון</button>
              <button id="tfFalse" class="btn tf-option">לא נכון</button>
            </div>
            <button id="tfSubmit" class="btn" disabled>בדוק תשובה</button>
            <div id="tfResult" class="result-display" style="display:none;"></div>
          </div>
          
          <!-- עבור השלמה (Cloze) -->
          <div id="clozeArea" class="cloze-interaction" style="display:none;">
            <button id="clozeSubmit" class="btn">בדוק תשובות</button>
            <div id="clozeResult" class="result-display" style="display:none;"></div>
          </div>
          
          <!-- עבור תיוג על תמונה (Label) -->
          <div id="labelArea" class="label-interaction" style="display:none;">
            <div id="labelCanvas" style="position:relative; max-width:100%; border:1px solid var(--stroke); border-radius:8px; overflow:hidden; background:#fff;">
              <img id="labelImg" src="" alt="תמונה לתיוג" style="max-width:100%; display:block;">
              <div id="labelOverlay" style="position:absolute; inset:0; pointer-events:none;"></div>
            </div>
            <button id="labelSubmit" class="btn" style="margin-top:10px;">בדוק תשובות</button>
            <div id="labelResult" class="result-display" style="display:none;"></div>
          </div>
          
        </div>
      </div>
    </div>

    <!-- קופסת בקרות נפרדת -->
    <div class="controls-container">
      <div class="test-nav">
        <button id="prev" class="btn ghost">→ הקודם</button>
        <!-- כפתורי איכות - יופיעו רק לשאלות פתוחות -->
        <div id="qualitySection" class="quality-row" style="opacity:0; pointer-events:none;">
          <button id="markWrong" class="btn quality-bad" disabled>טעיתי</button>
          <button id="markPartial" class="btn quality-mid" disabled>חלקית</button>
          <button id="markRight" class="btn quality-good" disabled>צדקתי</button>
        </div>
        <button id="next" class="btn">הבא ←</button>
      </div>
    </div>
  </section>

  <!-- סיום -->
  <section id="finish" style="display:none" aria-label="סיכום מבחן">
    <!-- קופסת תוכן הסיום בגובה 620px -->
    <div class="qa-container test-deck finish-container">
      <div class="test-card-stats">
        <h2>כל הכבוד! סיימת סשן</h2>
        
        <!-- סטטיסטיקות מפורטות -->
        <div style="margin: 24px 0; padding: 20px; background: #f8f9fa; border-radius: 12px; max-width: 400px; margin-left: auto; margin-right: auto;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; text-align: center;">
            <div>
              <div style="font-size: 24px; font-weight: bold; color: #2e7d32;" id="totalCards">0</div>
              <div style="font-size: 14px; color: #666;">כרטיסיות נבדקו</div>
            </div>
            <div>
              <div style="font-size: 24px; font-weight: bold; color: #1976d2;" id="avgQuality">-</div>
              <div style="font-size: 14px; color: #666;">ציון ממוצע</div>
            </div>
            <div>
              <div style="font-size: 20px; font-weight: bold; color: #f57c00;" id="timeSpent">00:00</div>
              <div style="font-size: 14px; color: #666;">זמן סשן</div>
            </div>
            <div>
              <div style="font-size: 20px; font-weight: bold; color: #7b1fa2;" id="cardsPerMin">0</div>
              <div style="font-size: 14px; color: #666;">כרטיסיות לדקה</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- קופסת בקרות נפרדת -->
    <div class="controls-container">
      <div class="test-nav">
        <button id="backToCards" class="btn ghost">→ חזרה לכרטיסים</button>
        <button id="restartFromFinish" class="btn">סשן חדש</button>
        <button id="exitSession" class="btn ghost">יציאה</button>
      </div>
    </div>
  </section>

</main>

<script>
(function(){
  /* =========================
     Cloze: {{n}} -> קו תחתון עם מספר
     ========================= */
  function clozeTokenHTML(n){
    n = String(n).trim();
    return `<span class="cloze-blank" data-id="${n}"><sup>${n}</sup><input type="text" inputmode="text" autocomplete="off" /></span>`;
  }
  function renderClozeQuestion(html){
    return (html || '').replace(/\{\{\s*(\d+)\s*\}\}/g, (_m, num) => clozeTokenHTML(num));
  }

  /* =========================
     עזרי DOM / API
     ========================= */
  const $  = s => document.querySelector(s);
  const $$ = s => Array.from(document.querySelectorAll(s));
  async function api(path, opts){
    const r = await fetch(path, opts);
    const t = await r.text();
    try { return JSON.parse(t); } catch { return { ok:false, error: t || r.statusText || ('HTTP '+r.status) }; }
  }
function escapeHtml(s){
  return (s || '').replace(/[&<>"']/g, m => ({
    '&':'&amp;',
    '<':'&lt;',
    '>':'&gt;',
    '"':'&quot;',
    "'":'&#39;'
  })[m]);
}
  /* =========================
     Elements
     ========================= */
  const setup    = $('#setup');
  const stage    = $('#stage');
  const finish   = $('#finish');
  const hud      = $('#hud');

  const catSel   = $('#cat');
  const subSel   = $('#sub');
  const sizeChips= $('#sizeChips');
  const startBtn = $('#start');
  const setupMsg = $('#setupMsg');

  const timerEl  = $('#timer');
  const hudCat   = $('#hudCat');
  const pbar     = $('#pbar');
  const counter  = $('#counter');
  const totalCards = $('#totalCards');
  const avgQuality = $('#avgQuality');
  const timeSpent = $('#timeSpent');
  const cardsPerMin = $('#cardsPerMin');

  const cardEl   = $('#card');      // מעטפת הכרטיס
  const qHtml    = $('#qHtml');     // אזור השאלה
  
  // אזורי אינטראקציה לסוגי שאלות שונים
  const freeArea = $('#freeArea');  
  const mcArea   = $('#mcArea');    
  const tfArea   = $('#tfArea');    
  const clozeArea= $('#clozeArea'); 
  const labelArea= $('#labelArea'); 
  
  // כפתורים וקלטים
  const flipBtn  = $('#flip');      
  const mcSubmit = $('#mcSubmit');
  const tfTrue   = $('#tfTrue');
  const tfFalse  = $('#tfFalse');
  const tfSubmit = $('#tfSubmit');
  const clozeSubmit = $('#clozeSubmit');
  const labelSubmit = $('#labelSubmit');
  const hideAnswerBtn = $('#hideAnswerBtn');
  
  // תוצאות
  const aHtml    = $('#aHtml');     
  const mcResult = $('#mcResult');
  const tfResult = $('#tfResult');
  const clozeResult = $('#clozeResult');
  const labelResult = $('#labelResult');
  
  // עבור תיוג על תמונה
  const labelImg = $('#labelImg');
  const labelOverlay = $('#labelOverlay');
  
  // ניווט ובקרה
  const prevBtn  = $('#prev');
  const nextBtn  = $('#next');
  const qualitySection = $('#qualitySection');
  const markRight= $('#markRight');
  const markWrong= $('#markWrong');
  const markPartial=$('#markPartial');
  const backToCards=$('#backToCards');
  const restartFromFinish=$('#restartFromFinish');
  const exitSession=$('#exitSession');



  /* =========================
     טעינת קטגוריות/תתי־קטגוריות למסכים
     ========================= */
  async function loadCategories(){
    const res = await api('<?= url('categories') ?>');
    if (!res.ok){ setupMsg.textContent = 'שגיאה בטעינת קטגוריות'; return; }
    const opts = ['<option value="">כל הקטגוריות</option>']
      .concat((res.categories||[]).map(c => `<option value="${c.slug}">${c.title}</option>`));
    catSel.innerHTML = opts.join('');

    // ברירות מחדל מה־URL (נשלחות מהשרת לתוך העמוד)
    const initCat = <?= json_encode($initialCat ?? '') ?>;
    const initSub = <?= json_encode($initialSub ?? '') ?>;
    if (initCat) {
      catSel.value = initCat;
      await loadSubcats(initCat);
      if (initSub) subSel.value = initSub;
    }
  }
  async function loadSubcats(cat){
    if (!cat){ subSel.innerHTML = '<option value="">— בחרי קטגוריה תחילה —</option>'; subSel.disabled = true; return; }
    subSel.disabled = true; subSel.innerHTML = '<option value="">טוען…</option>';
    const res = await api('<?= url('subcats') ?>?category='+encodeURIComponent(cat));
    if (!res.ok){ subSel.innerHTML = '<option value="">שגיאה</option>'; return; }
    const list = res.subcategories || [];
    const opts = ['<option value="">כל תתי־הקטגוריות</option>']
      .concat(list.map(s => `<option value="${s.slug}">${s.title}</option>`));
    subSel.innerHTML = opts.join('');
    subSel.disabled = false;
  }
  catSel.addEventListener('change', () => loadSubcats(catSel.value));

  /* =========================
     בחירת גודל חפיסה
     ========================= */
  let wanted = 10;
  sizeChips.addEventListener('click', (e)=>{
    const chip = e.target.closest('.chip'); if (!chip) return;
    wanted = parseInt(chip.dataset.size,10) || 10;
    sizeChips.querySelectorAll('.chip').forEach(c => c.setAttribute('aria-pressed','false'));
    chip.setAttribute('aria-pressed','true');
  });

  /* =========================
     בחירת קלפים בעדיפות Due/טעויות
     ========================= */
  function buildWeights(cards, progress){
    const now = Date.now();
    return cards.map((c,idx)=>{
      const p = (progress[c.id] || {seen:0, correct:0, box:3, updatedAt:null});
      const box = Math.max(1, Math.min(5, parseInt(p.box||3))); // 1 קשה ← עדיפות
      const seen = Math.max(0, parseInt(p.seen||0));
      const corr = Math.max(0, parseInt(p.correct||0));
      const acc  = seen>0 ? corr/seen : 0;
      const w_box = (6 - box) / 5;     // נמוך → משקל גבוה
      const w_acc = 1 - acc;           // שגיאות יותר → משקל גבוה
      let w_recent = 0.5;
      if (p.updatedAt){
        const days = Math.max(0,(now - Date.parse(p.updatedAt))/(1000*60*60*24));
        w_recent = Math.max(0, Math.min(1, days/30));
      }
      return { idx, weight: 0.6*w_box + 0.35*w_acc + 0.05*w_recent };
    });
  }
  function weightedSample(items, weights, n){
    const pool = items.map((it,i)=>({it, w: Math.max(0.0001, weights[i])}));
    const res=[]; n = Math.min(n, pool.length);
    for(let k=0;k<n;k++){
      const sum = pool.reduce((s,p)=>s+p.w,0); let r = Math.random()*sum, pick=0;
      for(let i=0;i<pool.length;i++){ r -= pool[i].w; if(r<=0){ pick=i; break; } }
      res.push(pool[pick].it); pool.splice(pick,1);
    }
    return res;
  }
  function pickDueFirst(cards, progress, n){
    const now = Date.now(), due=[], notDue=[];
    for (const c of cards){
      const p = progress[c.id];
      if (p && p.dueAt && Date.parse(p.dueAt) <= now) due.push(c);
      else notDue.push(c);
    }
    if (due.length >= n) return due.sort(()=>Math.random()-0.5).slice(0,n);
    const remain = n - due.length;
    const w = buildWeights(notDue, progress);
    const items = w.map(x=>notDue[x.idx]);
    const weights = w.map(x=>x.weight);
    const extra = weightedSample(items, weights, remain);
    return [...due.sort(()=>Math.random()-0.5), ...extra];
  }

  /* =========================
     מצב סשן
     ========================= */
  let deck=[], pos=0, startTs=0, timerInt=null, progressCache={}, undoStack=[];
  
  // שמירת מצב מלא של כל כרטיסייה (תשובות + אם כבר נבדק)
  let cardStates = {};
  let selectedQuality = null; // לשמירת איכות נבחרת בשאלות פתוחות
  
  // פונקציות ייעודיות לcloze
  function saveClozeState() {
    const currentCard = deck[pos];
    if (!currentCard) return;
    
    const cardKey = `${pos}_${currentCard.id || pos}`;
    const inputs = qHtml.querySelectorAll('.cloze-blank input');
    const userAnswers = {};
    
    inputs.forEach(input => {
      const blankId = input.parentElement.dataset.id;
      userAnswers[blankId] = input.value.trim();
    });
    
    const clozeSubmitElement = document.getElementById('clozeSubmit');
    const clozeResultElement = document.getElementById('clozeResult');
    
    let state = cardStates[cardKey] || {
      type: 'cloze',
      hasAnswered: false,
      isSubmitted: false,
      userAnswers: {}
    };
    
    state.userAnswers = userAnswers;
    
    // בדיקה אם השאלה הוגשה
    if (clozeSubmitElement && clozeSubmitElement.style.display === 'none' && 
        clozeResultElement && clozeResultElement.innerHTML.trim()) {
      state.isSubmitted = true;
      state.resultHTML = clozeResultElement.innerHTML;
      state.resultClass = clozeResultElement.className;
    }
    
    cardStates[cardKey] = state;
    console.log('Cloze saved:', cardKey, state);
  }
  
  function loadClozeState() {
    const currentCard = deck[pos];
    if (!currentCard) return;
    
    const cardKey = `${pos}_${currentCard.id || pos}`;
    const savedState = cardStates[cardKey];
    
    console.log('Loading cloze state:', cardKey, savedState);
    
    if (!savedState) return;
    
    const inputs = qHtml.querySelectorAll('.cloze-blank input');
    const clozeSubmitBtn = document.getElementById('clozeSubmit');
    const clozeResultDiv = document.getElementById('clozeResult');
    
    // שחזור התשובות בכל מקרה
    inputs.forEach(input => {
      const blankId = input.parentElement.dataset.id;
      if (savedState.userAnswers && savedState.userAnswers[blankId] !== undefined) {
        input.value = savedState.userAnswers[blankId];
      }
    });
    
    // אם השאלה הוגשה - הצגת מצב submitted
    if (savedState.isSubmitted && savedState.resultHTML) {
      inputs.forEach(input => input.disabled = true);
      if (clozeResultDiv) {
        clozeResultDiv.innerHTML = savedState.resultHTML;
        clozeResultDiv.className = savedState.resultClass || 'result-display';
        clozeResultDiv.style.display = '';
      }
      if (clozeSubmitBtn) {
        clozeSubmitBtn.style.display = 'none';
      }
      console.log('Cloze loaded as submitted');
    } else {
      console.log('Cloze loaded as draft');
    }
  }
  
  // פונקציות שמירה וטעינה של מצב כרטיסים
  function saveCardState() {
    const currentCard = deck[pos];
    if (!currentCard) return;
    
    const cardKey = `${pos}_${currentCard.id || pos}`;
    const type = currentCard.type || 'free';
    
    // אל תשמור מצב עבור cloze - זה מטופל בנפרד
    if (type === 'cloze') return;
    
    // התחלה עם מצב קיים או יצירת מצב חדש
    let state = cardStates[cardKey] || {
      type: type,
      hasAnswered: false,
      isSubmitted: false
    };
    
    // עדכון הtype למקרה של מצב חדש
    state.type = type;
    
    switch(type) {
      case 'free':
        state.answerVisible = !aHtml.classList.contains('answer-hidden');
        state.selectedQuality = selectedQuality; // שמירת איכות נבחרת
        break;
        
      case 'mc':
        const selectedMC = mcArea.querySelector('.mc-option.selected');
        if (selectedMC) {
          state.selectedIndex = parseInt(selectedMC.dataset.index);
          state.isSubmitted = mcSubmit.style.display === 'none';
          state.isCorrect = selectedMC.dataset.correct === 'true';
        }
        break;
        
      case 'tf':
        if (selectedTFAnswer !== null) {
          state.selectedAnswer = selectedTFAnswer;
          state.isSubmitted = tfSubmit.style.display === 'none';
          const correctAnswer = tfTrue.dataset.correct === 'true';
          state.isCorrect = selectedTFAnswer === correctAnswer;
          if (state.isSubmitted) {
            state.resultMessage = tfResult.innerHTML;
          }
        }
        break;
        
      case 'cloze':
        const inputs = qHtml.querySelectorAll('.cloze-blank input');
        const userAnswers = {};
        inputs.forEach(input => {
          const blankId = input.parentElement.dataset.id;
          userAnswers[blankId] = input.value.trim();
        });
        
        const clozeSubmitElement = document.getElementById('clozeSubmit');
        const clozeResultElement = document.getElementById('clozeResult');
        
        state.userAnswers = userAnswers;
        state.isSubmitted = (clozeSubmitElement && clozeSubmitElement.style.display === 'none') || 
                          (clozeResultElement && clozeResultElement.style.display !== 'none');
        if (state.isSubmitted && clozeResultElement && clozeResultElement.innerHTML) {
          state.resultHTML = clozeResultElement.innerHTML;
          state.resultClass = clozeResultElement.className;
        }
        console.log('Cloze save - userAnswers:', userAnswers, 'isSubmitted:', state.isSubmitted, 
                   'submitDisplay:', clozeSubmitElement ? clozeSubmitElement.style.display : 'null', 
                   'resultDisplay:', clozeResultElement ? clozeResultElement.style.display : 'null');
        break;
    }
    
    cardStates[cardKey] = state;
    console.log('Saved state for card', cardKey, state);
  }

  function loadCardState() {
    const currentCard = deck[pos];
    if (!currentCard) return;
    
    const cardKey = `${pos}_${currentCard.id || pos}`;
    const savedState = cardStates[cardKey];
    
    console.log('Loading state for card', cardKey, savedState);
    
    if (!savedState) return;
    
    const type = currentCard.type || 'free';
    
    switch(type) {
      case 'free':
        if (savedState.answerVisible) {
          aHtml.classList.remove('answer-hidden');
          flipBtn.style.display = 'none'; // הסתר את הכפתור הראשי
          showQualityButtons();
          
          // שחזור איכות נבחרת
          if (savedState.selectedQuality !== null && savedState.selectedQuality !== undefined) {
            selectedQuality = savedState.selectedQuality;
            clearQualitySelection();
            
            // סימון הכפתור הנכון
            if (selectedQuality === 2) markWrong.classList.add('selected');
            else if (selectedQuality === 3) markPartial.classList.add('selected');
            else if (selectedQuality === 4) markRight.classList.add('selected');
          }
        }
        break;
        
      case 'mc':
        if (savedState.selectedIndex !== undefined) {
          setTimeout(() => {
            const option = mcArea.querySelector(`[data-index="${savedState.selectedIndex}"]`);
            if (option) {
              option.classList.add('selected');
              mcSubmit.disabled = false;
              
              if (savedState.isSubmitted) {
                const allOptions = mcArea.querySelectorAll('.mc-option');
                allOptions.forEach(opt => {
                  opt.onclick = null;
                  opt.style.pointerEvents = 'none';
                  if (opt.dataset.correct === 'true') {
                    opt.classList.add('correct');
                  } else if (opt.classList.contains('selected') && opt.dataset.correct !== 'true') {
                    opt.classList.add('incorrect');
                  }
                });
                
                mcResult.innerHTML = savedState.isCorrect ? 
                  '<strong style="color: green;">✓ תשובה נכונה!</strong>' : 
                  '<strong style="color: red;">✗ תשובה שגויה</strong>';
                mcResult.className = 'result-display ' + (savedState.isCorrect ? 'result-correct' : 'result-incorrect');
                mcResult.style.display = '';
                mcSubmit.style.display = 'none';
              }
            }
          }, 50);
        }
        break;
        
      case 'tf':
        if (savedState.selectedAnswer !== undefined) {
          selectedTFAnswer = savedState.selectedAnswer;
          
          setTimeout(() => {
            if (savedState.selectedAnswer) {
              tfTrue.classList.add('selected');
            } else {
              tfFalse.classList.add('selected');
            }
            
            tfSubmit.disabled = false;
            
            if (savedState.isSubmitted) {
              if (savedState.selectedAnswer) {
                tfTrue.classList.add(savedState.isCorrect ? 'result-correct' : 'result-incorrect');
                tfFalse.classList.remove('selected');
              } else {
                tfFalse.classList.add(savedState.isCorrect ? 'result-correct' : 'result-incorrect');
                tfTrue.classList.remove('selected');
              }
              
              tfTrue.onclick = null;
              tfFalse.onclick = null;
              tfTrue.style.pointerEvents = 'none';
              tfFalse.style.pointerEvents = 'none';
              tfSubmit.style.display = 'none';
              
              tfResult.innerHTML = savedState.resultMessage || (savedState.isCorrect ? 
                '<strong style="color: green;">✓ תשובה נכונה!</strong>' : 
                '<strong style="color: red;">✗ תשובה שגויה</strong>');
              tfResult.className = 'result-display ' + (savedState.isCorrect ? 'result-correct' : 'result-incorrect');
              tfResult.style.display = '';
            }
          }, 50);
        }
        break;
    }
  }

  function setPbar(){ const pct = deck.length ? (pos/deck.length)*100 : 0; pbar.style.width = pct.toFixed(1)+'%'; }
  function tickTimer(){
    const s=Math.max(0,Math.floor((Date.now()-startTs)/1000));
    const mm=String(Math.floor(s/60)).padStart(2,'0'); const ss=String(s%60).padStart(2,'0');
    timerEl.textContent=`${mm}:${ss}`;
  }

  /* =========================
     רינדור כרטיס
     ========================= */
  function renderCard(){
    if (pos >= deck.length){
      stage.style.display='none';
      finish.style.display='';
      clearInterval(timerInt); timerInt=null;
      pbar.style.width='100%';
      
      // חישוב סטטיסטיקות
      const sessionTime = Math.floor((Date.now() - startTs) / 1000);
      const minutes = Math.floor(sessionTime / 60);
      const seconds = sessionTime % 60;
      const timeStr = `${minutes}:${seconds.toString().padStart(2, '0')}`;
      
      // חישוב ציון ממוצע מהמצבים השמורים
      let totalQuality = 0;
      let qualityCount = 0;
      for (const state of Object.values(cardStates)) {
        if (state.selectedQuality !== null && state.selectedQuality !== undefined) {
          totalQuality += state.selectedQuality;
          qualityCount++;
        }
      }
      const avgQualityValue = qualityCount > 0 ? (totalQuality / qualityCount).toFixed(1) : '-';
      
      // כרטיסיות לדקה
      const cardsPerMinValue = minutes > 0 ? Math.round(deck.length / minutes) : deck.length;
      
      // עדכון התצוגה
      totalCards.textContent = String(deck.length);
      avgQuality.textContent = avgQualityValue;
      timeSpent.textContent = timeStr;
      cardsPerMin.textContent = String(cardsPerMinValue);
      
      return;
    }
    const c = deck[pos]; 
    const type = c.type || 'free';

    // הצגת השאלה
    if (type === 'cloze') {
      qHtml.innerHTML = renderClozeQuestion(c.question || '');
    } else {
      qHtml.innerHTML = c.question || '';
    }

    // איפוס כל האזורים
    hideAllInteractionAreas();
    hideQualityButtons(); // איפוס כפתורי האיכות
    
    // הצגת האזור המתאים לסוג השאלה
    switch(type) {
      case 'free':
        renderFreeQuestion(c);
        break;
      case 'mc':
        renderMCQuestion(c);
        break;
      case 'tf':
        renderTFQuestion(c);
        break;
      case 'cloze':
        renderClozeQuestion2(c);
        break;
      case 'label':
        renderLabelQuestion(c);
        break;
      default:
        renderFreeQuestion(c);
    }

    // טעינת מצב שמור אחרי רינדור - רק לסוגים שלא cloze
    if (type !== 'cloze') {
      setTimeout(() => loadCardState(), 100);
    }

    // מונים/פס התקדמות
    counter.textContent = (pos+1)+'/'+deck.length;
    setPbar();
  }

  function hideAllInteractionAreas() {
    freeArea.style.display = 'none';
    mcArea.style.display = 'none'; 
    tfArea.style.display = 'none';
    clozeArea.style.display = 'none';
    labelArea.style.display = 'none';
  }

  // שאלה פתוחה - נשארת כמו קודם
  function renderFreeQuestion(c) {
    freeArea.style.display = '';
    aHtml.innerHTML = c.answer || '';
    aHtml.classList.add('answer-hidden');
    freeArea.classList.remove('answer-shown'); // וודא שהכפתור flip נראה
    flipBtn.style.display = ''; // איפוס מפורש של הכפתור
    selectedQuality = null; // איפוס בחירת איכות
    clearQualitySelection(); // איפוס צבעים
  }

  // שאלה אמריקאית
  function renderMCQuestion(c) {
    mcArea.style.display = '';
    let options = [];
    try {
      const answerObj = JSON.parse(c.answer || '{}');
      options = answerObj.options || [];
    } catch(e) {
      console.error('שגיאה בפענוח אפשרויות:', e);
      return;
    }

    const optionsContainer = mcArea.querySelector('.mc-options');
    optionsContainer.innerHTML = '';
    
    options.forEach((opt, i) => {
      const optDiv = document.createElement('div');
      optDiv.className = 'mc-option';
      optDiv.textContent = opt.text || '';
      optDiv.dataset.index = i;
      optDiv.dataset.correct = opt.correct || false;
      optDiv.style.pointerEvents = 'auto'; // איפוס לחיצות
      
      optDiv.onclick = () => {
        // הסרת בחירה קודמת
        optionsContainer.querySelectorAll('.mc-option').forEach(el => el.classList.remove('selected'));
        // בחירת האפשרות הנוכחית
        optDiv.classList.add('selected');
        mcSubmit.disabled = false;
        saveCardState(); // שמירת הבחירה
      };
      
      optionsContainer.appendChild(optDiv);
    });
    
    mcSubmit.disabled = true;
    mcSubmit.style.display = 'block'; // וידוא שהכפתור מופיע
    mcResult.style.display = 'none';
  }

  // נכון/לא נכון
  function renderTFQuestion(c) {
    tfArea.style.display = '';
    selectedTFAnswer = null; // איפוס בחירה
    
    let correctAnswer = true;
    try {
      const answerObj = JSON.parse(c.answer || '{}');
      correctAnswer = answerObj.value === true;
    } catch(e) {
      console.error('שגיאה בפענוח תשובת נכון/לא נכון:', e);
    }

    // שמירת התשובה הנכונה
    tfTrue.dataset.correct = correctAnswer;
    tfFalse.dataset.correct = !correctAnswer;
    
    // איפוס מצב הכפתורים
    tfTrue.classList.remove('correct', 'incorrect', 'selected');
    tfFalse.classList.remove('correct', 'incorrect', 'selected');
    tfTrue.style.pointerEvents = 'auto';
    tfFalse.style.pointerEvents = 'auto';
    
    tfSubmit.disabled = true;
    tfSubmit.style.display = 'block';
    tfResult.style.display = 'none';
  }

  // השלמה (Cloze)
  function renderClozeQuestion2(c) {
    clozeArea.style.display = '';
    
    const inputs = qHtml.querySelectorAll('.cloze-blank input');
    
    // הגדרת event listeners לשמירה אוטומטית
    inputs.forEach(input => {
      // הסרת listeners קיימים
      input.removeEventListener('input', saveClozeState);
      // הוספת listener חדש
      input.addEventListener('input', saveClozeState);
      
      // איפוס מצב
      input.disabled = false;
      input.style.backgroundColor = '';
      input.style.borderColor = '';
      input.style.color = '';
    });
    
    // הצגת כפתור וכיסוי תוצאה
    clozeSubmit.style.display = 'block';
    clozeResult.style.display = 'none';
    
    // טעינת מצב שמור
    setTimeout(() => loadClozeState(), 50);
  }

  // תיוג על תמונה (Label)
  function renderLabelQuestion(c) {
    labelArea.style.display = '';
    
    let answerData = null;
    try {
      answerData = JSON.parse(c.answer || '{}');
    } catch(e) {
      console.error('שגיאה בפענוח נתוני תיוג:', e);
      return;
    }
    
    if (!answerData.image) {
      labelResult.innerHTML = '<div class="muted">לא נמצאה תמונה לשאלה זו</div>';
      labelResult.style.display = 'block';
      return;
    }
    
    // טעינת התמונה
    labelImg.src = answerData.image;
    labelImg.onload = () => {
      renderLabelItems(answerData.items || []);
    };
    
    // איפוס מצב
    labelSubmit.style.display = 'block';
    labelResult.style.display = 'none';
  }
  
  function renderLabelItems(items) {
    labelOverlay.innerHTML = '';
    if (!labelImg.naturalWidth) return;

    const W = labelImg.clientWidth;
    const H = labelImg.clientHeight;

    items.forEach(item => {
      // נקודת העוגן
      const ax = Math.round(item.anchor.x * W);
      const ay = Math.round(item.anchor.y * H);
      
      // תיבת התשובה (מרכז התיבה)
      const boxX = Math.round(item.box.x * W);
      const boxY = Math.round(item.box.y * H);
      const boxW = Math.round(item.box.w * W);
      const boxH = Math.round(item.box.h * H);
      const bx = boxX + boxW / 2; // מרכז התיבה X
      const by = boxY + boxH / 2; // מרכז התיבה Y
      
      // קו מהנקודה לתיבה
      const line = document.createElement('div');
      line.style.position = 'absolute';
      line.style.pointerEvents = 'none';
      line.style.borderTop = '2px solid #2563eb';
      line.style.zIndex = '1';
      line.style.transformOrigin = '0 0';
      
      const length = Math.hypot(bx - ax, by - ay);
      const angle = Math.atan2(by - ay, bx - ax) * 180 / Math.PI;
      
      line.style.left = ax + 'px';
      line.style.top = ay + 'px';
      line.style.width = length + 'px';
      line.style.transform = `rotate(${angle}deg)`;
      labelOverlay.appendChild(line);

      // תיבת התשובה
      const box = document.createElement('div');
      box.className = 'label-answer-box';
      box.dataset.id = String(item.id);
      box.style.position = 'absolute';
      box.style.left = boxX + 'px';
      box.style.top = boxY + 'px';
      box.style.width = boxW + 'px';
      box.style.height = boxH + 'px';
      box.style.border = '2px solid #2563eb';
      box.style.background = 'rgba(255,255,255,0.95)';
      box.style.borderRadius = '8px';
      box.style.padding = '8px';
      box.style.cursor = 'pointer';
      box.style.pointerEvents = 'auto';
      box.style.display = 'flex';
      box.style.flexDirection = 'column';
      box.style.zIndex = '2';
      
      const label = document.createElement('div');
      label.style.fontWeight = 'bold';
      label.style.marginBottom = '4px';
      label.style.fontSize = '12px';
      label.textContent = `${item.id}`;
      
      const input = document.createElement('input');
      input.type = 'text';
      input.placeholder = 'תשובה...';
      input.style.border = '1px solid #ddd';
      input.style.borderRadius = '4px';
      input.style.padding = '4px';
      input.style.fontSize = '12px';
      input.style.textAlign = 'right';
      input.dir = 'rtl';
      
      box.appendChild(label);
      box.appendChild(input);
      labelOverlay.appendChild(box);
      
      // נקודת העוגן
      const anchor = document.createElement('div');
      anchor.style.position = 'absolute';
      anchor.style.left = (ax - 4) + 'px';
      anchor.style.top = (ay - 4) + 'px';
      anchor.style.width = '8px';
      anchor.style.height = '8px';
      anchor.style.background = '#dc2626';
      anchor.style.borderRadius = '50%';
      anchor.style.zIndex = '3';
      labelOverlay.appendChild(anchor);
    });
  }
      
      const input = document.createElement('input');
      input.type = 'text';
      input.placeholder = 'תשובה...';
      input.style.border = '1px solid #ddd';
      input.style.borderRadius = '4px';
      input.style.padding = '4px';
      input.style.fontSize = '12px';
      input.style.textAlign = 'right';
      input.dir = 'rtl';
      
      box.appendChild(label);
      box.appendChild(input);
      labelOverlay.appendChild(box);
      
      // נקודת העוגן
      const anchor = document.createElement('div');
      anchor.style.position = 'absolute';
      anchor.style.left = (ax - 4) + 'px';
      anchor.style.top = (ay - 4) + 'px';
      anchor.style.width = '8px';
      anchor.style.height = '8px';
      anchor.style.background = '#dc2626';
      anchor.style.borderRadius = '50%';
      anchor.style.zIndex = '3';
      labelOverlay.appendChild(anchor);
    });
  }

  let selectedTFAnswer = null;

  function selectTFAnswer(answer) {
    selectedTFAnswer = answer;
    
    // סימון בחירה
    tfTrue.classList.remove('selected');
    tfFalse.classList.remove('selected');
    
    if (answer) {
      tfTrue.classList.add('selected');
    } else {
      tfFalse.classList.add('selected');
    }
    
    tfSubmit.disabled = false;
    saveCardState(); // שמירת הבחירה
  }

  function submitTFAnswer() {
    if (selectedTFAnswer === null) return;
    
    const correctAnswer = tfTrue.dataset.correct === 'true';
    const isCorrect = selectedTFAnswer === correctAnswer;
    
    // שמירת הבחירה והוספת צבע התוצאה
    // הכפתור שנבחר שומר על ה-selected + מקבל צבע לפי תוצאה
    if (selectedTFAnswer) {
      // נבחר "נכון"
      tfTrue.classList.add(isCorrect ? 'result-correct' : 'result-incorrect');
      tfFalse.classList.remove('selected'); // הסרת selected מהכפתור השני
    } else {
      // נבחר "לא נכון"  
      tfFalse.classList.add(isCorrect ? 'result-correct' : 'result-incorrect');
      tfTrue.classList.remove('selected'); // הסרת selected מהכפתור השני
    }
    
    // השבתת כפתורים
    tfTrue.onclick = null;
    tfFalse.onclick = null;
    tfTrue.style.pointerEvents = 'none';
    tfFalse.style.pointerEvents = 'none';
    tfSubmit.style.display = 'none';
    
    // הצגת תוצאה
    tfResult.innerHTML = isCorrect ? 
      '<strong style="color: green;">✓ תשובה נכונה!</strong>' : 
      '<strong style="color: red;">✗ תשובה שגויה</strong>';
    tfResult.className = 'result-display ' + (isCorrect ? 'result-correct' : 'result-incorrect');
    tfResult.style.display = '';
  }

  function showQualityButtons() {
    qualitySection.style.opacity = '1';
    qualitySection.style.pointerEvents = 'auto';
    markWrong.disabled = false;
    markPartial.disabled = false;
    markRight.disabled = false;
    selectedQuality = null; // איפוס בחירה
    clearQualitySelection(); // איפוס צבעים
  }
  
  function hideQualityButtons() {
    qualitySection.style.opacity = '0';
    qualitySection.style.pointerEvents = 'none';
    markWrong.disabled = true;
    markPartial.disabled = true;
    markRight.disabled = true;
    selectedQuality = null; // איפוס בחירה
    clearQualitySelection(); // איפוס צבעים
  }

  function clearQualitySelection() {
    markWrong.classList.remove('selected');
    markPartial.classList.remove('selected'); 
    markRight.classList.remove('selected');
  }

  function selectQuality(quality, button) {
    if (selectedQuality === quality) {
      // לחיצה חוזרת - ביטול הבחירה
      selectedQuality = null;
      clearQualitySelection();
    } else {
      // בחירה חדשה
      selectedQuality = quality;
      clearQualitySelection();
      button.classList.add('selected');
    }
    saveCardState(); // שמירת המצב
  }  function handleSpaceKey() {
    // פעולה שונה לכל סוג שאלה
    const currentCard = deck[pos];
    if (!currentCard) return;
    
    const type = currentCard.type || 'free';
    
    switch(type) {
      case 'free':
        if (freeArea.style.display !== 'none') {
          flipBtn.click();
        }
        break;
      case 'mc':
        if (mcArea.style.display !== 'none' && !mcSubmit.disabled) {
          mcSubmit.click();
        }
        break;
      case 'tf':
        if (tfArea.style.display !== 'none' && !tfSubmit.disabled) {
          tfSubmit.click();
        }
        break;
      case 'cloze':
        if (clozeArea.style.display !== 'none' && clozeSubmit.style.display !== 'none') {
          clozeSubmit.click();
        }
        break;
    }
  }

  function handleClozeSubmission(c) {
    const inputs = qHtml.querySelectorAll('.cloze-blank input');
    let correctAnswers = {};
    let userAnswers = {};
    let correctCount = 0;
    let totalCount = inputs.length;
    
    // פענוח התשובות הנכונות מה-JSON
    try {
      const answerObj = JSON.parse(c.answer || '{}');
      if (answerObj.blanks && Array.isArray(answerObj.blanks)) {
        // המבנה החדש: {"blanks": [{"id": 1, "answers": ["תשובה"]}, ...]}
        answerObj.blanks.forEach(blank => {
          if (blank.answers && blank.answers.length > 0) {
            correctAnswers[blank.id] = blank.answers[0]; // התשובה הראשונה
          }
        });
      } else {
        // המבנה הישן: {"1": "תשובה", "2": "תשובה"}
        correctAnswers = answerObj;
      }
    } catch(e) {
      console.error('שגיאה בפענוח תשובות Cloze:', e);
    }
    
    // איסוף תשובות המשתמש
    const cardKey = `${pos}_${c.id || pos}`; // מפתח לשמירת תשובות
    inputs.forEach(input => {
      const blankId = input.parentElement.dataset.id;
      const userAnswer = input.value.trim();
      userAnswers[blankId] = userAnswer;
      
      // בדיקת נכונות - רק אם המשתמש כתב משהו
      const correctAnswer = correctAnswers[blankId] || '';
      let isCorrect = false;
      
      if (userAnswer === '') {
        // לא מילא כלום - תמיד שגוי
        isCorrect = false;
      } else {
        // השוואת התשובה
        isCorrect = userAnswer.toLowerCase() === correctAnswer.toLowerCase();
      }
      
      if (isCorrect) correctCount++;
      
      // עיצוב השדה לפי התוצאה
      if (userAnswer === '') {
        // שדה ריק - צבע אזהרה
        input.style.backgroundColor = '#fff3cd';
        input.style.borderColor = '#ffc107';
        input.style.color = '#856404';
      } else if (isCorrect) {
        // תשובה נכונה
        input.style.backgroundColor = '#d4edda';
        input.style.borderColor = '#28a745';
        input.style.color = '#155724';
      } else {
        // תשובה שגויה
        input.style.backgroundColor = '#f8d7da';
        input.style.borderColor = '#dc3545';
        input.style.color = '#721c24';
      }
      
      input.disabled = true; // נעילת השדה
    });
    
    // יצירת המשפט המלא עם התשובות הנכונות
    // נתחיל מהשאלה המקורית (לפני הפיכתה לשדות קלט)
    let originalQuestion = c.question || '';
    
    // החלפת כל {{n}} בתשובות עם צבע מתאים
    Object.keys(correctAnswers).forEach(blankId => {
      const correctAnswer = correctAnswers[blankId];
      const userAnswer = userAnswers[blankId] || '';
      
      // בדיקת נכונות מחדש (כולל בדיקת שדה ריק)
      let isCorrect = false;
      if (userAnswer !== '' && userAnswer.toLowerCase() === correctAnswer.toLowerCase()) {
        isCorrect = true;
      }
      
      // מציאת הסימון {{n}} והחלפתו
      const regex = new RegExp(`\\{\\{\\s*${blankId}\\s*\\}\\}`, 'g');
      
      let displayText;
      if (userAnswer === '') {
        // לא השיב - התשובה הנכונה בצהוב
        displayText = `<span style="background: #fff3cd; color: #856404; padding: 2px 6px; border-radius: 4px; font-weight: bold;">${escapeHtml(correctAnswer)}</span> <small style="color: #6c757d;">(לא השבת)</small>`;
      } else if (isCorrect) {
        // תשובה נכונה - הצגת התשובה בירוק
        displayText = `<span style="background: #d4edda; color: #155724; padding: 2px 6px; border-radius: 4px; font-weight: bold;">${escapeHtml(correctAnswer)}</span> <small style="color: #28a745;">✓</small>`;
      } else {
        // תשובה שגויה - הצגת תשובת המשתמש באדום + התשובה הנכונה בירוק
        displayText = `<span style="background: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 4px; text-decoration: line-through;">${escapeHtml(userAnswer)}</span> → <span style="background: #d4edda; color: #155724; padding: 2px 6px; border-radius: 4px; font-weight: bold;">${escapeHtml(correctAnswer)}</span>`;
      }
      
      originalQuestion = originalQuestion.replace(regex, displayText);
    });
    
    console.log('Original question:', c.question);
    console.log('Processed question:', originalQuestion);
    console.log('Correct answers:', correctAnswers);
    console.log('User answers:', userAnswers);
    
    // הצגת התוצאה
    const isAllCorrect = correctCount === totalCount;
    const percentage = Math.round((correctCount / totalCount) * 100);
    
    // הצגת התוצאה באזור התוצאה (לא לשנות את השאלה עצמה)
    clozeResult.innerHTML = `
      <div style="margin-bottom: 12px;">
        <strong>התשובה המלאה:</strong><br>
        <div style="padding: 12px; background: #f8f9fa; border-radius: 8px; margin-top: 8px; text-align: right;">
          ${originalQuestion}
        </div>
      </div>
      <div>
        <strong>תוצאה:</strong> ${correctCount}/${totalCount} נכון (${percentage}%)
      </div>
    `;
    
    clozeResult.className = 'result-display ' + (isAllCorrect ? 'result-correct' : 'result-incorrect');
    clozeResult.style.display = '';
    
    clozeSubmit.style.display = 'none';
    
    // שמירת המצב המלא של הכרטיס
    const currentCard = deck[pos];
    cardStates[cardKey] = {
      userAnswers: userAnswers,
      isSubmitted: true,
      resultHTML: clozeResult.innerHTML,
      resultClass: clozeResult.className
    };
  }

  function handleSpaceKey() {
    const c = deck[pos];
    const type = c.type || 'free';
    
    switch(type) {
      case 'free':
        if (flipBtn) flipBtn.click();
        break;
      case 'mc':
        if (mcSubmit && !mcSubmit.disabled) mcSubmit.click();
        break;
      case 'cloze':
        if (clozeSubmit && clozeSubmit.style.display !== 'none') clozeSubmit.click();
        break;
      // עבור TF לא צריך - המשתמש צריך לבחור נכון/לא נכון
    }
  }

  /* =========================
     ניווט / איכות תשובה / Undo
     ========================= */
  function goPrev(){ 
    saveCardState(); // שמירת מצב נוכחי
    saveClozePartialAnswers(); // שמירת תשובות חלקיות לפני מעבר
    if (pos>0){ pos--; renderCard(); } 
  }
  function goNext(){ 
    // אם יש איכות נבחרת בשאלה פתוחה - שליחה לפני מעבר
    const currentCard = deck[pos];
    if (currentCard && currentCard.type === 'free' && selectedQuality !== null) {
      applyQuality(selectedQuality);
    }
    
    saveCardState(); // שמירת מצב נוכחי
    saveClozePartialAnswers(); // שמירת תשובות חלקיות לפני מעבר
    if (pos<deck.length-1){ pos++; renderCard(); } else { pos=deck.length; renderCard(); } 
  }

const postQuality = (id, quality) =>
  api('<?= url('flashcards/progress') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, quality })
  });

const putProgress = (id, row) =>
  api('<?= url('flashcards/progress/put') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, row })
  });

  async function applyQuality(quality){
    const c = deck[pos]; if (!c) return;
    const prevRow = progressCache[c.id] ? {...progressCache[c.id]} : null;
    const res = await postQuality(c.id, quality);
    if (res.ok && res.progress){
      progressCache[c.id] = res.progress;
      progressCache[c.id].lastQuality = quality;
      undoStack.push({ id:c.id, row: prevRow });
      if (undoStack.length > 100) undoStack.shift();
    }
    // הסרת המעבר האוטומטי - עכשיו רק עם כפתור "הבא"
    // goNext(); - הוסר!
  }
  async function undo(){
    const last = undoStack.pop(); if (!last) return;
    if (last.row === null){
      await putProgress(last.id, {});
      delete progressCache[last.id];
    } else {
      await putProgress(last.id, last.row);
      progressCache[last.id] = last.row;
    }
    if (pos > 0) { pos--; renderCard(); }
  }

  prevBtn.addEventListener('click', goPrev);
  nextBtn.addEventListener('click', goNext);
  markWrong.addEventListener('click', ()=>selectQuality(2, markWrong));
  markPartial.addEventListener('click', ()=>selectQuality(3, markPartial));
  markRight.addEventListener('click', ()=>selectQuality(4, markRight));

  // כפתורים בעמוד הסיום
  restartFromFinish.addEventListener('click', ()=>{
    finish.style.display='none';
    setup.style.display='';
    hud.style.display='none';
    pbar.style.width='0%';
  });

  exitSession.addEventListener('click', ()=>{
    // חזרה למקור - לקטגוריה/תת-קטגוריה שממנה הגיעו
    const urlParams = new URLSearchParams(location.search);
    const cat = urlParams.get('category');
    const sub = urlParams.get('subcategory') || urlParams.get('sub');
    
    if (cat && sub) {
      window.location.href = `<?= url('flashcards') ?>/${encodeURIComponent(cat)}/${encodeURIComponent(sub)}`;
    } else if (cat) {
      window.location.href = `<?= url('flashcards') ?>/${encodeURIComponent(cat)}`;
    } else {
      window.location.href = '<?= url('flashcards') ?>';
    }
  });

  // כפתור חזרה לכרטיסים מעמוד הסיום
  backToCards.addEventListener('click', ()=>{
    finish.style.display='none';
    stage.style.display='';
    hud.style.display='';
  });

  // פונקציה להסתרת תשובה (עבור הכפתור הקטן)
  function hideAnswer() {
    aHtml.classList.add('answer-hidden');
    freeArea.classList.remove('answer-shown'); // מסיר class כדי להציג את flip
    hideQualityButtons();
    saveCardState();
  }

  // Event listeners לסוגי שאלות שונים
  
  // שאלה פתוחה - רק כאן המשתמש מדרג בעצמו
  flipBtn.addEventListener('click', ()=>{
    if (aHtml.classList.contains('answer-hidden')) {
      aHtml.classList.remove('answer-hidden');
      freeArea.classList.add('answer-shown'); // מוסיף class כדי להסתיר את flip
      showQualityButtons(); // רק בשאלות פתוחות המשתמש מדרג
    } else {
      aHtml.classList.add('answer-hidden');
      freeArea.classList.remove('answer-shown'); // מסיר class כדי להציג את flip
      hideQualityButtons();
    }
    saveCardState(); // שמירת מצב אחרי שינוי
  });

  // כפתור קטן להסתרת תשובה
  hideAnswerBtn.addEventListener('click', hideAnswer);

  // שאלה אמריקאית
  mcSubmit.addEventListener('click', ()=>{
    const selected = mcArea.querySelector('.mc-option.selected');
    if (!selected) return;
    
    const isCorrect = selected.dataset.correct === 'true';
    const allOptions = mcArea.querySelectorAll('.mc-option');
    
    // סימון תשובות
    allOptions.forEach(opt => {
      opt.onclick = null; // השבתת לחיצות
      opt.style.pointerEvents = 'none';
      if (opt.dataset.correct === 'true') {
        opt.classList.add('correct');
      } else if (opt.classList.contains('selected') && !isCorrect) {
        opt.classList.add('incorrect');
      }
    });
    
    // הצגת תוצאה
    mcResult.innerHTML = isCorrect ? 
      '<strong style="color: green;">✓ תשובה נכונה!</strong>' : 
      '<strong style="color: red;">✗ תשובה שגויה</strong>';
    mcResult.className = 'result-display ' + (isCorrect ? 'result-correct' : 'result-incorrect');
    mcResult.style.display = '';
    
    mcSubmit.style.display = 'none';
    saveCardState(); // שמירת מצב אחרי שליחה
  });

  // נכון/לא נכון
  tfTrue.addEventListener('click', ()=>selectTFAnswer(true));
  tfFalse.addEventListener('click', ()=>selectTFAnswer(false));
  tfSubmit.addEventListener('click', ()=>{
    submitTFAnswer();
    saveCardState(); // שמירת מצב אחרי שליחה
  });

  // השלמה (Cloze)
  clozeSubmit.addEventListener('click', ()=>{
    const currentCard = deck[pos];
    handleClozeSubmission(currentCard);
    saveClozeState(); // שמירת מצב אחרי שליחה
  });
  
  // תיוג על תמונה (Label)
  labelSubmit.addEventListener('click', ()=>{
    const currentCard = deck[pos];
    handleLabelSubmission(currentCard);
  });
  
  function handleLabelSubmission(c) {
    let answerData = null;
    try {
      answerData = JSON.parse(c.answer || '{}');
    } catch(e) {
      console.error('שגיאה בפענוח נתוני תיוג:', e);
      return;
    }
    
    const items = answerData.items || [];
    let correctCount = 0;
    let totalCount = items.length;
    
    items.forEach(item => {
      const input = labelOverlay.querySelector(`[data-id="${item.id}"] input`);
      if (!input) return;
      
      const userAnswer = input.value.trim();
      const correctAnswers = item.answers || [];
      
      let isCorrect = false;
      if (userAnswer && correctAnswers.length > 0) {
        // בדיקה אם התשובה מתאימה לאחת מהתשובות הנכונות
        isCorrect = correctAnswers.some(correct => 
          userAnswer.toLowerCase() === correct.toLowerCase()
        );
      }
      
      if (isCorrect) {
        correctCount++;
        input.style.backgroundColor = '#d4edda';
        input.style.borderColor = '#28a745';
        input.style.color = '#155724';
      } else {
        input.style.backgroundColor = '#f8d7da';
        input.style.borderColor = '#dc3545';
        input.style.color = '#721c24';
      }
      
      input.disabled = true;
    });
    
    // הצגת תוצאות
    const percentage = totalCount > 0 ? Math.round((correctCount / totalCount) * 100) : 0;
    const resultClass = percentage >= 80 ? 'result-correct' : percentage >= 60 ? 'result-partial' : 'result-wrong';
    
    labelResult.innerHTML = `
      <div class="${resultClass}">
        <strong>${correctCount}/${totalCount} נכון (${percentage}%)</strong>
      </div>
    `;
    labelResult.style.display = 'block';
    labelSubmit.style.display = 'none';
    
    // קביעת איכות אוטומטית
    let quality = 2; // לא זכור
    if (percentage >= 90) quality = 5; // מושלם
    else if (percentage >= 80) quality = 4; // טוב
    else if (percentage >= 60) quality = 3; // בסדר
    
    selectQuality(quality);
  }
  
  // פונקציה לשמירת תשובות חלקיות בזמן הקלדה
  function saveClozePartialAnswers() {
    const currentCard = deck[pos];
    if (!currentCard || currentCard.type !== 'cloze') return;
    
    const cardKey = `${pos}_${currentCard.id || pos}`;
    const inputs = qHtml.querySelectorAll('.cloze-blank input');
    const userAnswers = {};
    
    inputs.forEach(input => {
      const blankId = input.parentElement.dataset.id;
      userAnswers[blankId] = input.value.trim();
    });
    
    // שמירת תשובות חלקיות (לא נשלח עדיין)
    cardStates[cardKey] = {
      userAnswers: userAnswers,
      isSubmitted: false
    };
  }

  document.addEventListener('keydown', e=>{
    if (finish.style.display==='') return;
    const ae = document.activeElement;
    const inForm = ae && (ae.tagName === 'INPUT' || ae.tagName === 'TEXTAREA' || ae.isContentEditable);
    if (inForm) return;

    // מקש רווח - פעולה שונה לכל סוג שאלה
    if (e.key === ' ') { 
      e.preventDefault(); 
      handleSpaceKey();
    }
    
    if (e.key === 'ArrowLeft')  { e.preventDefault(); goPrev(); }
    if (e.key === 'ArrowRight') { e.preventDefault(); goNext(); }
    
    // מקשי דירוג - רק אחרי שהמשתמש השיב
    if (qualitySection.style.display !== 'none'){
      if (e.key === '1') { e.preventDefault(); applyQuality(2); }
      if (e.key === '2') { e.preventDefault(); applyQuality(3); }
      if (e.key === '3') { e.preventDefault(); applyQuality(5); }
    }
    
    if (e.key === 'Backspace'){ e.preventDefault(); undo(); }
  });

  /* =========================
     התחלת סשן
     ========================= */
  async function startSession(){
    setupMsg.textContent = 'טוען…';
    const cat = catSel.value || '';
    const sub = subSel.value || '';
    
    // קבלת השמות הקריאים מהselect options
    const catName = cat ? catSel.selectedOptions[0]?.textContent || cat : '';
    const subName = sub ? subSel.selectedOptions[0]?.textContent || sub : '';
    
    hudCat.textContent = catName ? ('קטגוריה: ' + catName + (subName?(' > '+subName):'')) : 'כללי';

    // שליפת כרטיסים + פרוגרס
    const qs = cat ? (sub ? `?category=${encodeURIComponent(cat)}&subcategory=${encodeURIComponent(sub)}` : `?category=${encodeURIComponent(cat)}`) : '';
    const [cardsRes, progRes] = await Promise.all([
  api('<?= url('cards') ?>'+qs),
  api('<?= url('flashcards/progress') ?>')
]);
    if (!cardsRes.ok){ setupMsg.textContent = 'שגיאה: ' + (cardsRes.error || 'לא ידוע'); return; }
    if (!progRes.ok){ setupMsg.textContent = 'שגיאה: ' + (progRes.error || 'לא ידוע'); return; }

    let cards = cardsRes.cards || [];
    if (cards.length===0){ setupMsg.textContent = 'אין כרטיסיות מתאימות.'; return; }

    progressCache = progRes.progress || {};
    const pick = pickDueFirst(cards, progressCache, wanted);
    deck = pick.sort(()=>Math.random()-0.5);
    pos=0;

    setup.style.display='none';
    finish.style.display='none';
    stage.style.display='';
    hud.style.display='';

    startTs = Date.now(); timerEl.textContent='00:00';
    if (timerInt) clearInterval(timerInt);
    timerInt=setInterval(tickTimer,1000);

    setPbar();
    renderCard();
    setupMsg.textContent='';
  }

  startBtn.addEventListener('click', startSession);

  /* =========================
     Init
     ========================= */
  loadCategories().then(()=>{
    // תמיד להראות את מסך ההגדרות - המשתמש יבחר כמה שאלות רוצה
    // אפילו אם הגיע עם פרמטרים של קטגוריה/תת-קטגוריה
  });

})();
</script>

</body>
</html>