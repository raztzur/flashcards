<?php
/** @var Kirby\Cms\Page $page */
header('Content-Type: text/html; charset=utf-8');
$root = page('flashcards');
$cats = $root ? $root->children()->filterBy('intendedTemplate','category') : [];
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>הוספת כרטיס</title>
  <style>
    :root{ --stroke:#000; --bg:#fff; --fg:#000; --radius:16px; }
    *{ box-sizing:border-box; }
    html,body{ margin:0; padding:0; background:var(--bg); color:var(--fg);
      font-family:system-ui, -apple-system, Segoe UI, Roboto; }
    .container{ padding:16px; max-width:900px; margin:0 auto; }
    .topbar{ display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .btn{ border:1px solid var(--stroke); border-radius:12px; padding:8px 12px; background:#fff; cursor:pointer; }
    .btn.ghost{ background:transparent; }
    .row{ display:flex; gap:10px; align-items:center; margin:10px 0; }
    .field{ border:1px solid var(--stroke); border-radius:12px; padding:10px; }
    input[type="text"], select{ border:1px solid var(--stroke); border-radius:12px; padding:8px 10px; width:100%; background:#fff; color:#000; }
    label{ font-weight:600; }
    .toolbar{ display:flex; gap:6px; margin:6px 0; }
    .rte{ border:1px solid var(--stroke); border-radius:12px; min-height:120px; padding:10px; background:#fff; }
    .muted{ opacity:.7; }
    .options{ display:grid; gap:8px; }
    .optrow{ display:grid; grid-template-columns: 1fr auto auto; gap:8px; align-items:center; }
    .optrow input[type="text"]{ width:100%; }
    .section{ border:1px solid var(--stroke); border-radius:16px; padding:12px; margin:12px 0; }
    .actions{ display:flex; gap:10px; margin-top:12px; align-items:center; }
  </style>
</head>
<body>
  <main class="container">
    <header class="topbar">
      <h1>הוספת כרטיס</h1>
      <nav><a class="btn ghost" href="<?= url('flashcards') ?>">← חזרה לבית</a></nav>
    </header>

    <section class="section">
      <div class="row">
        <div style="flex:1">
          <label for="cat">קטגוריה</label>
          <select id="cat" class="field" required>
            <?php foreach ($cats as $c): ?>
              <option value="<?= html($c->slug()) ?>"><?= html($c->title()) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="flex:1">
          <label for="qtype">סוג השאלה</label>
          <select id="qtype" class="field">
            <option value="free">שאלה פתוחה</option>
            <option value="mc">אמריקאית</option>
            <option value="tf">נכון / לא נכון</option>
          </select>
        </div>
      </div>
    </section>

    <section class="section">
      <label>השאלה</label>
      <div class="toolbar">
        <button class="btn" type="button" data-cmd="bold"><b>B</b></button>
        <button class="btn" type="button" data-cmd="italic"><i>I</i></button>
        <button class="btn" type="button" data-cmd="underline"><u>U</u></button>
      </div>
      <div id="q" class="rte" contenteditable="true" dir="rtl"></div>
    </section>

    <section id="freeSection" class="section">
      <label>התשובה</label>
      <div class="toolbar">
        <button class="btn" type="button" data-cmd="bold" data-target="a"><b>B</b></button>
        <button class="btn" type="button" data-cmd="italic" data-target="a"><i>I</i></button>
        <button class="btn" type="button" data-cmd="underline" data-target="a"><u>U</u></button>
      </div>
      <div id="a" class="rte" contenteditable="true" dir="rtl"></div>
    </section>

    <section id="mcSection" class="section" style="display:none">
      <div class="row" style="justify-content:space-between">
        <label>אפשרויות (סמן את הנכונה)</label>
        <button type="button" class="btn" id="addOpt">הוסף אפשרות</button>
      </div>
      <div class="options" id="opts"></div>
      <p class="muted">נשמר כ־JSON בשדה התשובה.</p>
    </section>

    <section id="tfSection" class="section" style="display:none">
      <label>בחירה</label>
      <div class="row">
        <label><input type="radio" name="tf" value="true" checked> נכון</label>
        <label><input type="radio" name="tf" value="false"> לא נכון</label>
      </div>
      <p class="muted">נשמר כ־JSON בשדה התשובה.</p>
    </section>

    <section class="actions">
      <button id="save" class="btn">שמירה</button>
      <span id="msg" class="muted" aria-live="polite"></span>
    </section>
  </main>

  <script>
    const $ = s => document.querySelector(s);
    const catEl=$('#cat'), typeEl=$('#qtype'), qEl=$('#q'), aEl=$('#a');
    const freeSec=$('#freeSection'), mcSec=$('#mcSection'), tfSec=$('#tfSection');
    const optsEl=$('#opts'), msgEl=$('#msg');

    // בחירת קטגוריה אם הגעת עם ?category=...
    const urlParams=new URLSearchParams(location.search);
    const initCat=urlParams.get('category'); if(initCat) catEl.value=initCat;

    // עיצוב בסיסי
    function bindToolbar(scope){
      scope.addEventListener('click', (e)=>{
        const btn=e.target.closest('button[data-cmd]'); if(!btn) return;
        const cmd=btn.getAttribute('data-cmd');
        const target=btn.getAttribute('data-target')==='a' ? aEl : qEl;
        target.focus();
        document.execCommand(cmd,false,null);
      });
    }
    bindToolbar(document);

    function setTypeVisibility(){
      const t=typeEl.value;
      freeSec.style.display=(t==='free')?'':'none';
      mcSec.style.display=(t==='mc')?'':'none';
      tfSec.style.display=(t==='tf')?'':'none';
    }
    typeEl.addEventListener('change', setTypeVisibility); setTypeVisibility();

    function addOption(text='',correct=false){
      const row=document.createElement('div'); row.className='optrow';
      row.innerHTML=`
        <input type="text" class="field" placeholder="תשובה..." value="${text.replace(/"/g,'&quot;')}" dir="rtl">
        <label class="row" style="justify-content:center; gap:6px">
          <input type="radio" name="mc-correct" ${correct?'checked':''}> נכונה
        </label>
        <button type="button" class="btn ghost" aria-label="מחק">מחק</button>
      `;
      row.querySelector('input[type="radio"]').addEventListener('change', ()=>{
        document.querySelectorAll('input[name="mc-correct"]').forEach(r=>{ if(r!==row.querySelector('input[type="radio"]')) r.checked=false; });
      });
      row.querySelector('button').addEventListener('click', ()=>row.remove());
      optsEl.appendChild(row);
    }
    addOption(); addOption();
    $('#addOpt').addEventListener('click', ()=>addOption());

    async function api(path, payload){
      try{
        const r=await fetch(path,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        const t=await r.text(); try{return JSON.parse(t);}catch{return {ok:false,error:t||r.statusText||('HTTP '+r.status)}}
      }catch(e){return {ok:false,error:e.message||'Network error'};}
    }

    function buildAnswerByType(){
      const t=typeEl.value;
      if(t==='free') return aEl.innerHTML.trim();
      if(t==='mc'){
        const rows=[...optsEl.querySelectorAll('.optrow')];
        const options=rows.map(row=>{
          const text=row.querySelector('input[type="text"]').value.trim();
          const correct=row.querySelector('input[type="radio"]').checked;
          return {text,correct};
        }).filter(o=>o.text!=='');
        if(options.length===0) return '';
        return JSON.stringify({type:'mc',options});
      }
      if(t==='tf'){
        const val=document.querySelector('input[name="tf"]:checked')?.value==='true';
        return JSON.stringify({type:'tf',value:val});
      }
      return '';
    }

    // שמירה → מעבר לעמוד הקטגוריה שנבחרה
    $('#save').addEventListener('click', async ()=>{
      msgEl.textContent='';
      const category=catEl.value;
      const type=typeEl.value;
      const question=qEl.innerHTML.trim();
      const answer=buildAnswerByType();

      if(!category){ msgEl.textContent='בחר קטגוריה'; return; }
      if(!question){ msgEl.textContent='כתוב שאלה'; return; }
      if(type==='free' && !answer){ msgEl.textContent='כתוב תשובה'; return; }

      msgEl.textContent='שומר…';
      const res=await api('<?= url('cards/add') ?>',{category,type,question,answer});
      if(!res.ok){ msgEl.textContent='שגיאה: '+(res.error||'לא ידוע'); return; }

      // מעבר לעמוד הקטגוריה שנבחרה
      window.location.href = '<?= url('flashcards') ?>/' + encodeURIComponent(category);
    });
  </script>
</body>
</html>
