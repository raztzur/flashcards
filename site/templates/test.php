<?php
/** @var Kirby\Cms\Page $page */
header('Content-Type: text/html; charset=utf-8');

$root = page('flashcards');
$cats = $root ? $root->children()->filterBy('intendedTemplate', 'category') : [];
$initialCat = get('category'); // /flashcards/test?category=<slug>&auto=1
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>מבחן</title>
  <link rel="manifest" href="/assets/pwa/manifest.json">
  <meta name="theme-color" content="#ffffff">
  <style>
    :root{ --stroke:#000; --bg:#fff; --fg:#000; --radius:16px; --muted:#666; }
    *{ box-sizing:border-box; }
    html,body{ margin:0; padding:0; background:#fff; color:#000;
      font-family:system-ui,-apple-system,Segoe UI,Roboto; }
    .container{ padding:16px; max-width:1100px; margin:0 auto; }
    .topbar{ display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
    .btn{ border:1px solid var(--stroke); border-radius:12px; padding:8px 12px; background:#fff; cursor:pointer; }
    .btn.ghost{ background:transparent; }
    .muted{ color:var(--muted); }
    .row{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .section{ border:1px solid var(--stroke); border-radius:16px; padding:12px; margin:12px 0; }
    .progressbar{ height:8px; background:#f2f2f2; border:1px solid var(--stroke); border-radius:999px; overflow:hidden; }
    .progressbar > div{ height:100%; background:#000; width:0%; transition:width .25s; }
    .sessionmeta{ display:flex; justify-content:space-between; align-items:center; gap:10px; margin:6px 0 0; }
    .sessionmeta .badge{ border:1px solid var(--stroke); border-radius:999px; padding:4px 8px; font-size:12px; }

    .stage{ display:flex; align-items:center; justify-content:center; min-height:48vh; }
    .card{ border:1px solid var(--stroke); border-radius:20px; width:100%; max-width:780px; min-height:260px;
      padding:18px; position:relative; perspective:1000px; background:#fff; box-shadow: 0 2px 0 #000; }
    .face{ position:absolute; inset:0; padding:18px; backface-visibility:hidden; transition:transform .5s; }
    .question{ background:#fff; border-radius:18px; }
    .answer{ background:#fff; border-radius:18px; transform:rotateY(180deg); }
    .flipped .question{ transform:rotateY(180deg); }
    .flipped .answer{ transform:rotateY(360deg); }

    .meta{ display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
    .typebadge{ border:1px solid var(--stroke); border-radius:999px; padding:4px 8px; font-size:12px; }
    .counter{ font-variant-numeric: tabular-nums; }
    .ktext :where(p,ul,ol){ margin:0 0 .6em; }
    .actions{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

    .opt{ border:1px solid var(--stroke); border-radius:10px; padding:10px; margin:6px 0; display:flex; align-items:center; gap:8px; }
    .opt input{ transform:scale(1.2); }

    .scorebar{ display:flex; gap:8px; flex-wrap:wrap; }
    .score{ border:1px solid var(--stroke); border-radius:999px; padding:8px 12px; }
    .score.bad{ background:#ffe3e3; }
    .score.mid{ background:#fff7cc; }
    .score.good{ background:#dfffe3; }

    .navarrows{ display:flex; gap:8px; }
    .navbtn{ border:1px solid var(--stroke); border-radius:10px; padding:6px 10px; }

    .card.swipe-right{ animation: swipeRight .35s ease-out; }
    .card.swipe-left{ animation: swipeLeft .35s ease-out; }
    @keyframes swipeRight{ to{ transform: translateX(30px) rotate(-2deg); opacity:.9; } }
    @keyframes swipeLeft{ to{ transform: translateX(-30px) rotate(2deg); opacity:.9; } }

    @media (max-width:640px){ .card{ min-height:300px; } }
  </style>
</head>
<body>
  <main class="container">
    <header class="topbar">
      <h1>מבחן</h1>
      <nav><a class="btn ghost" href="<?= url('flashcards') ?>">← חזרה</a></nav>
    </header>

    <section class="section" id="setup">
      <div class="row">
        <div>
          <label for="cat">קטגוריה</label><br>
          <select id="cat">
            <option value="">כל הקטגוריות</option>
            <?php foreach ($cats as $c): ?>
              <option value="<?= html($c->slug()) ?>" <?= $initialCat === $c->slug() ? 'selected' : '' ?>>
                <?= html($c->title()) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="count">כמות שאלות</label><br>
          <select id="count">
            <?php foreach ([5,10,15,20,30,50] as $n): ?>
              <option value="<?= $n ?>"><?= $n ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label>&nbsp;</label><br><button id="start" class="btn">התחל</button></div>
      </div>
      <p class="muted" style="margin-top:6px">* קודם Due להיום, ואז השלמה רנדומלית משוקללת.</p>
      <div id="setupMsg" class="muted" aria-live="polite"></div>
    </section>

    <section id="sessionHud" style="display:none">
      <div class="progressbar"><div id="pbar"></div></div>
      <div class="sessionmeta">
        <span class="badge" id="hudCat">כללי</span>
        <span class="muted">Space=הפוך, ←/→ ניווט, 1/2/3 דירוג, Backspace=Undo</span>
        <span class="badge" id="timer">00:00</span>
      </div>
    </section>

    <section class="stage" id="stage" style="display:none">
      <div class="card" id="card">
        <div class="face question ktext" id="qFace">
          <div class="meta"><span class="typebadge" id="typeBadge">—</span><span class="counter" id="counter">0/0</span></div>
          <div id="qHtml">השאלה...</div>
          <div id="mcArea" style="margin-top:12px; display:none"></div>
          <div id="tfArea" style="margin-top:12px; display:none">
            <label class="opt"><input type="radio" name="tf" value="true"> נכון</label>
            <label class="opt"><input type="radio" name="tf" value="false"> לא נכון</label>
          </div>
          <div class="actions" style="margin-top:12px">
            <button class="btn" id="flip">הצג תשובה (Space)</button>
            <div class="navarrows"><button class="navbtn" id="prev">← הקודם</button><button class="navbtn" id="next">הבא →</button></div>
          </div>
        </div>
        <div class="face answer ktext" id="aFace">
          <div class="meta"><span>תשובה</span><span class="counter" id="counter2">0/0</span></div>
          <div id="aHtml">התשובה...</div>
          <div class="actions" style="margin-top:12px; justify-content:space-between">
            <div class="scorebar">
              <button class="score bad"  id="markWrong">1 · טעיתי</button>
              <button class="score mid"  id="markPartial">2 · חלקית</button>
              <button class="score good" id="markRight">3 · צדקתי</button>
            </div>
            <div class="row">
              <button class="btn ghost" id="undo">בטל (Backspace)</button>
              <div class="navarrows"><button class="navbtn" id="prev2">← הקודם</button><button class="navbtn" id="next2">הבא →</button></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="finish" class="section" style="display:none">
      <h3>סיימת את הסשן 🎉</h3>
      <div class="row">
        <button class="btn" id="restart">סשן חדש</button>
        <a class="btn ghost" href="<?= url('flashcards') ?>">חזרה לבית</a>
      </div>
    </section>
  </main>

  <script>
    if ('serviceWorker' in navigator){
      window.addEventListener('load', ()=> navigator.serviceWorker.register('/assets/pwa/sw.js').catch(()=>{}));
    }

    const $ = s => document.querySelector(s);
    const setup=$('#setup'), stage=$('#stage'), finish=$('#finish'), hud=$('#sessionHud');
    const catSel=$('#cat'), countSel=$('#count'), startBtn=$('#start'), setupMsg=$('#setupMsg');
    const pbar=$('#pbar'), timerEl=$('#timer'), hudCat=$('#hudCat');
    const cardEl=$('#card'), qHtml=$('#qHtml'), aHtml=$('#aHtml');
    const mcArea=$('#mcArea'), tfArea=$('#tfArea');
    const typeBadge=$('#typeBadge'), counter=$('#counter'), counter2=$('#counter2');
    const flipBtn=$('#flip'), prev=$('#prev'), next=$('#next'), prev2=$('#prev2'), next2=$('#next2');
    const markRight = $('#markRight'), markWrong = $('#markWrong'), markPartial = $('#markPartial');
    const undoBtn = $('#undo');

    async function api(path, opts){ const r=await fetch(path, opts); const t=await r.text(); try{ return JSON.parse(t);}catch{ return {ok:false,error:t||r.statusText||('HTTP '+r.status)} } }
    const getCards = (cat) => api(cat?`/cards?category=${encodeURIComponent(cat)}`:'/cards');
    const getProgress = () => api('/flashcards/progress');
    const postQuality = (id, quality) => api('/flashcards/progress', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id, quality }) });
    const putProgress = (id, row) => api('/flashcards/progress/put', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id, row }) });

    function buildWeights(cards, progress){
      const now = Date.now();
      return cards.map((c,idx)=>{
        const p = (progress[c.id] || {seen:0, correct:0, box:3, updatedAt:null});
        const box = Math.max(1, Math.min(5, parseInt(p.box||3)));
        const seen = Math.max(0, parseInt(p.seen||0));
        const corr = Math.max(0, parseInt(p.correct||0));
        const acc = seen>0 ? corr/seen : 0;
        const w_box = (6 - box) / 5;
        const w_acc = 1 - acc;
        let w_recent = 0.5;
        if (p.updatedAt){ const days = Math.max(0,(now - Date.parse(p.updatedAt))/(1000*60*60*24)); w_recent = Math.max(0, Math.min(1, days/30)); }
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
    function pickDueFirst(cards, progress, wanted){
      const now = Date.now(); const due=[], notDue=[];
      for (const c of cards){
        const p = progress[c.id];
        if (p && p.dueAt && Date.parse(p.dueAt) <= now) due.push(c);
        else notDue.push(c);
      }
      if (due.length >= wanted) return due.sort(()=>Math.random()-0.5).slice(0,wanted);
      const remaining = wanted - due.length;
      const w = buildWeights(notDue, progress);
      const items = w.map(x=>notDue[x.idx]);
      const weights = w.map(x=>x.weight);
      const extra = weightedSample(items, weights, remaining);
      return [...due.sort(()=>Math.random()-0.5), ...extra];
    }

    let deck=[], pos=0, flipped=false, startTs=0, timerInt=null;
    let progressCache = {}, undoStack = [];

    function setPbar(){ const pct = deck.length ? (pos/deck.length)*100 : 0; pbar.style.width = pct.toFixed(1)+'%'; }
    function tickTimer(){ const s=Math.max(0,Math.floor((Date.now()-startTs)/1000)); const mm=String(Math.floor(s/60)).padStart(2,'0'); const ss=String(s%60).padStart(2,'0'); timerEl.textContent=`${mm}:${ss}`; }

    function renderCard(){
      if (pos >= deck.length){ stage.style.display='none'; finish.style.display=''; clearInterval(timerInt); timerInt=null; pbar.style.width='100%'; return; }
      const c = deck[pos]; const type = c.type || 'free';
      typeBadge.textContent = (type==='free' ? 'שאלה פתוחה' : (type==='mc' ? 'אמריקאית' : 'נכון/לא נכון'));

      qHtml.innerHTML = c.question || '';
      mcArea.innerHTML=''; mcArea.style.display='none'; tfArea.style.display='none';

      let answerDisplay = '';
      if (type==='free'){ answerDisplay = c.answer || ''; }
      else {
        try{
          const obj = JSON.parse(c.answer || '{}');
          if (type==='mc' && obj?.options){
            mcArea.style.display='';
            obj.options.forEach((opt,i)=>{
              const row = document.createElement('label'); row.className='opt';
              row.innerHTML = `<input type="radio" name="mc" value="${i}"><span>${escapeHtml(opt.text||'')}</span>`;
              mcArea.appendChild(row);
            });
            const corrects = obj.options.map((o,i)=>o.correct ? (i+1)+'. '+o.text : null).filter(Boolean);
            answerDisplay = '<strong>תשובה נכונה:</strong><br>' + (corrects.join('<br>') || '—');
          } else if (type==='tf' && typeof obj.value==='boolean'){
            tfArea.style.display=''; answerDisplay = '<strong>התשובה:</strong> ' + (obj.value ? 'נכון' : 'לא נכון');
          } else { answerDisplay = c.answer || ''; }
        }catch(e){ answerDisplay = c.answer || ''; }
      }
      aHtml.innerHTML = answerDisplay;

      counter.textContent = (pos+1)+'/'+deck.length;
      counter2.textContent = counter.textContent;

      flipped=false; cardEl.classList.remove('flipped'); setPbar();
    }
    function escapeHtml(s){ return (s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
    function goPrev(){ if (pos>0){ pos--; renderCard(); } }
    function goNext(){ if (pos<deck.length-1){ pos++; renderCard(); } else { pos=deck.length; renderCard(); } }
    function flip(){ flipped=!flipped; cardEl.classList.toggle('flipped', flipped); }

    async function applyQuality(quality, swipe){
      const c = deck[pos];
      const prevRow = progressCache[c.id] ? {...progressCache[c.id]} : null;
      // שלח
      const res = await postQuality(c.id, quality);
      if (res.ok && res.progress){
        progressCache[c.id] = res.progress;
        progressCache[c.id].lastQuality = quality;
        undoStack.push({ id:c.id, row: prevRow }); // נשמור לשחזור
        if (undoStack.length > 50) undoStack.shift();
      }
      if (swipe==='right'){ cardEl.classList.add('swipe-right'); setTimeout(()=>cardEl.classList.remove('swipe-right'), 350); }
      if (swipe==='left'){  cardEl.classList.add('swipe-left');  setTimeout(()=>cardEl.classList.remove('swipe-left'), 350); }
      goNext();
    }

    async function undo(){
      const last = undoStack.pop();
      if (!last) return;
      if (last.row === null){
        // מחיקה — אם לא הייתה רשומה קודם נרוקן
        await putProgress(last.id, {});
        delete progressCache[last.id];
      } else {
        await putProgress(last.id, last.row);
        progressCache[last.id] = last.row;
      }
      // נחזור צעד אם אפשר
      if (pos > 0) { pos--; renderCard(); }
    }

    // מחוות מגע
    (function bindSwipe(){
      let startX=0, moved=false;
      cardEl.addEventListener('touchstart', e=>{ const t=e.touches[0]; startX=t.clientX; moved=false; }, {passive:true});
      cardEl.addEventListener('touchmove', e=>{ const t=e.touches[0]; if(Math.abs(t.clientX-startX)>20) moved=true; }, {passive:true});
      cardEl.addEventListener('touchend', e=>{
        if(!moved){ flip(); return; }
        const dx = (e.changedTouches[0].clientX - startX);
        if (dx > 30) applyQuality(5,'right');
        else if (dx < -30) applyQuality(2,'left');
      }, {passive:true});
    })();

    document.addEventListener('keydown', e=>{
      if (finish.style.display==='') return;
      if (e.key === ' ') { e.preventDefault(); flip(); }
      if (e.key === 'ArrowLeft')  { e.preventDefault(); goPrev(); }
      if (e.key === 'ArrowRight') { e.preventDefault(); goNext(); }
      if (flipped){
        if (e.key === '1') { e.preventDefault(); applyQuality(2,'left'); }
        if (e.key === '2') { e.preventDefault(); applyQuality(3); }
        if (e.key === '3') { e.preventDefault(); applyQuality(5,'right'); }
      }
      if (e.key === 'Backspace'){ e.preventDefault(); undo(); }
    });

    flipBtn.addEventListener('click', flip);
    prev.addEventListener('click', goPrev); next.addEventListener('click', goNext);
    prev2.addEventListener('click', goPrev); next2.addEventListener('click', goNext);
    markWrong.addEventListener('click', ()=>applyQuality(2,'left'));
    markPartial.addEventListener('click', ()=>applyQuality(3));
    markRight.addEventListener('click', ()=>applyQuality(5,'right'));
    undoBtn.addEventListener('click', undo);
    $('#restart').addEventListener('click', ()=>{ finish.style.display='none'; setup.style.display=''; hud.style.display='none'; pbar.style.width='0%'; });

    startBtn.addEventListener('click', async ()=>{
      setupMsg.textContent = 'טוען…';
      const cat = catSel.value || '';
      const wanted = parseInt(countSel.value,10) || 10;
      hudCat.textContent = cat ? ('קטגוריה: ' + cat) : 'כללי';

      const [cardsRes, progRes] = await Promise.all([ getCards(cat), getProgress() ]);
      if (!cardsRes.ok){ setupMsg.textContent = 'שגיאה: ' + (cardsRes.error || 'לא ידוע'); return; }
      if (!progRes.ok){ setupMsg.textContent = 'שגיאה: ' + (progRes.error || 'לא ידוע'); return; }

      let cards = cardsRes.cards || [];
      if (cards.length===0){ setupMsg.textContent = 'אין כרטיסיות מתאימות.'; return; }
      cards.forEach((c,i)=> c._idx=i);

      progressCache = progRes.progress || {};
      const pick = pickDueFirst(cards, progressCache, wanted);
      deck = pick.sort(()=>Math.random()-0.5);
      pos=0;

      setup.style.display='none'; finish.style.display='none'; stage.style.display=''; hud.style.display='';
      startTs = Date.now(); tickTimer(); if (timerInt) clearInterval(timerInt); timerInt=setInterval(tickTimer,1000);
      setPbar(); renderCard(); setupMsg.textContent='';
    });

    (function initFromQuery(){
      const qp = new URLSearchParams(location.search);
      if (qp.get('auto') === '1'){ startBtn.click(); }
    })();
  </script>
</body>
</html>