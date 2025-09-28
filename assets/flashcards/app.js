(function(){
  async function j(url, opts) {
    try {
      const r = await fetch(url, opts);
      const t = await r.text();
      try { return JSON.parse(t); } catch { return { ok:false, error: t || r.statusText || ('HTTP '+r.status) }; }
    } catch(e){ return { ok:false, error: e.message || 'Network error' }; }
  }

  const API = {
    categories: () => j(`/categories`),
    addCategory: (title) =>
      j(`/categories/add`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ title }) // ← זה המפתח הנכון
      }),
    deleteCategory: (slug) =>
      j(`/categories/delete`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ slug })
      }),

    listCards: (category) => j(`/cards${category ? `?category=${encodeURIComponent(category)}` : ''}`),
    getCard: (id) => j(`/card?id=${encodeURIComponent(id)}`),

    addCard: (payload) =>
      j(`/cards/add`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      }),
    updateCard: (payload) =>
      j(`/cards/update`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      }),
    deleteCard: (id) =>
      j(`/cards/delete`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id })
      }),

    getProgress: () => j(`/flashcards/progress`),
    postProgress: (delta) =>
      j(`/flashcards/progress`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(delta)
      }),
  };

  const $=s=>document.querySelector(s);
  const view = $('#view');
  const state={ route:{name:'home',params:{}}, categories:[], cards:[], progress:{}, srs:{boxes:5,startBox:3} };

  function parseRoute(){
    const hash=location.hash.slice(2);
    if(!hash) return {name:'home',params:{}};
    const [path,q]=hash.split('?'); const parts=path.split('/').filter(Boolean);
    const params=Object.fromEntries(new URLSearchParams(q||''));
    if(parts[0]==='add') return {name:'add',params};
    if(parts[0]==='test') return {name:'test',params};
    if(parts[0]==='category'&&parts[1]) return {name:'category',params:{slug:parts[1]}};
    return {name:'home',params:{}};
  }
  window.addEventListener('hashchange', ()=>{ state.route=parseRoute(); render(); });

  function levelColor(score){ return score>=70?'high':score>=40?'mid':'low'; }
  function catStats(slug){
    const cards=state.cards.filter(c=>c.category===slug);
    const total=cards.length; let sumBoxes=0,solved=0;
    cards.forEach(c=>{
      const p=state.progress[c.id]||c.stats||{box:state.srs.startBox,seen:0,correct:0};
      sumBoxes+=p.box||state.srs.startBox;
      if((p.box||0)>=state.srs.boxes-1) solved++;
    });
    const avg= total? Math.round((sumBoxes/(total*state.srs.boxes))*100):0;
    return { total, solved, score: avg };
  }

  async function loadAll(){
    const cats=await API.categories(); state.categories=cats.ok?cats.categories:[];
    const all=await API.listCards(); state.cards=all.ok?all.cards:[];
    const prog=await API.getProgress(); state.progress=prog.ok?(prog.progress||{}) : {};
  }

  async function Home(){
    const greet = `<p class="muted">שלום! איזה יופי שחזרת. כל כרטיס מקדם אותך ✅</p>`;
    const rows = state.categories.map(cat => { const s=catStats(cat.slug);
      return `<div class="catrow">
        <div class="row"><span class="pill">${s.solved}/${s.total}</span>&nbsp;<strong>${cat.title}</strong></div>
        <div class="meter" data-level="${levelColor(s.score)}" title="${s.score}%"></div>
        <a class="btn" href="#/add?category=${encodeURIComponent(cat.slug)}">הוסף כרטיסיה</a>
        <a class="btn" href="#/test?category=${encodeURIComponent(cat.slug)}">מבחן</a>
        <button class="btn ghost" data-del="${cat.slug}">מחק</button>
      </div>`; }).join('');
    const addToggle = `<details><summary class="btn">הוסף קטגוריה חדשה</summary>
      <div class="row space" style="margin-top:8px">
        <input class="grow" id="newCatTitle" placeholder="שם קטגוריה" />
        <button class="btn" id="createCat">צור</button>
      </div><p class="muted" id="catMsg"></p></details>`;
    view.innerHTML = `<section class="panel">
      ${greet}
      <div class="row space" style="margin-bottom:8px"><h3>קטגוריות</h3><a class="btn" href="#/test">בחינה על הכל</a></div>
      <div class="list">${rows || '<p class="muted">אין קטגוריות עדיין.</p>'}</div>
      <div style="margin-top:12px">${addToggle}</div>
    </section>`;
    view.querySelectorAll('[data-del]').forEach(btn=>btn.addEventListener('click', async ()=>{
      const slug=btn.getAttribute('data-del'); if(!confirm('למחוק את הקטגוריה וכל הכרטיסיות שבה?')) return;
      const res=await API.deleteCategory(slug); if(!res.ok) return alert(res.error||'שגיאה');
      await loadAll(); render();
    }));
    $('#createCat')?.addEventListener('click', async ()=>{
      const input=$('#newCatTitle'); const title=(input?.value||'').trim(); const msg=$('#catMsg');
      if(!title){ msg.textContent='נא להזין שם'; input?.focus(); return; }
      msg.textContent='שומר…';
      const res=await API.addCategory(title);
      msg.textContent=res.ok?'נוצר ✅':('שגיאה: '+(res.error||'לא ידוע'));
      if(res.ok){ input.value=''; await loadAll(); render(); }
    });
  }

  async function Add(){
    const cats = state.categories.map(c=>`<option value="${c.slug}">${c.title}</option>`).join('');
    const initCat = state.route.params.category || (state.categories[0]?.slug || '');
    view.innerHTML = `<section class="panel">
      <h3>הוספת כרטיס</h3>
      <div class="row"><label>קטגוריה</label><select id="cat">${cats}</select></div>
      <div class="row"><label>סוג השאלה</label>
        <select id="qtype">
          <option value="free">שאלה פתוחה</option>
          <option value="mc">אמריקאית</option>
          <option value="tf">נכון/לא נכון</option>
        </select>
      </div>
      <div class="row"><label>שאלה</label></div>
      <div id="q" class="rte" contenteditable="true" dir="rtl"></div>
      <div class="row" id="ansWrap"><label>תשובה</label></div>
      <div id="a" class="rte" contenteditable="true" dir="rtl"></div>
      <div class="row" style="margin-top:10px"><button class="btn" id="save">הוסף</button></div>
      <p class="muted" id="msg"></p>
    </section>`;
    $('#cat').value = initCat;
    $('#qtype').addEventListener('change', ()=>{
      const t=$('#qtype').value; const aw=$('#ansWrap');
      if(t==='free'){ aw.innerHTML='<label>תשובה</label>'; $('#a').style.display='block'; }
      else { aw.innerHTML='<label>אפשרויות/פתרון (יגיע בהמשך)</label>'; $('#a').style.display='none'; }
    });
    $('#save').addEventListener('click', async ()=>{
      const payload={ category: $('#cat').value, type: $('#qtype').value, question: $('#q').innerHTML.trim(), answer: $('#a').innerHTML.trim() };
      $('#msg').textContent='שומר…';
      const res=await API.addCard(payload);
      $('#msg').textContent = res.ok ? 'נשמר ✅' : ('שגיאה: '+(res.error||'לא ידוע'));
    });
  }

  function nextIndex(idx, len, dir){ if(dir>0) return (idx+1)%len; return (idx-1+len)%len; }
  async function Test(){
    const filter = state.route.params.category||null;
    const pool = state.cards.filter(c=>!filter || c.category===filter);
    if(pool.length===0){ view.innerHTML='<section class="panel"><p class="muted">אין כרטיסיות לבדיקה.</p></section>'; return; }
    for(let i=pool.length-1;i>0;i--){ const j=Math.floor(Math.random()*(i+1)); [pool[i],pool[j]]=[pool[j],pool[i]]; }
    let idx=0, flipped=false;
    function renderCard(){
      const c=pool[idx];
      const html = `<section class="panel test-wrap">
        <div class="flipcard" id="flip"><div>${flipped ? (c.answer || '<em class="muted">—</em>') : c.question}</div></div>
        <div class="controls">
          <button class="btn" data-score="0">טעיתי</button>
          <button class="btn" data-score="0.5">חלקית</button>
          <button class="btn" data-score="1">צדקתי</button>
        </div>
        <div class="arrowbar">
          <button class="btn" id="prev">‹</button>
          <span class="muted">${idx+1} / ${pool.length}</span>
          <button class="btn" id="next">›</button>
        </div>
      </section>`;
      view.innerHTML=html;
      $('#flip').onclick=()=>{ flipped=!flipped; renderCard(); };
      document.querySelectorAll('[data-score]').forEach(b=>b.onclick=async ()=>{
        const score=parseFloat(b.getAttribute('data-score'));
        const cur = state.progress[c.id] || c.stats || {box:state.srs.startBox, seen:0, correct:0};
        const nextBox = Math.max(0, Math.min(state.srs.boxes-1, cur.box + (score>=1?+1:(score<=0?-1:0))));
        await API.postProgress({ id:c.id, delta:{ seen:1, correct:(score>=1?1:0), box:nextBox } });
        idx = nextIndex(idx, pool.length, +1);
        flipped=false; renderCard();
      });
      $('#prev').onclick=()=>{ idx = nextIndex(idx, pool.length, -1); flipped=false; renderCard(); };
      $('#next').onclick=()=>{ idx = nextIndex(idx, pool.length, +1); flipped=false; renderCard(); };
    }
    renderCard();
  }

  async function Category(){
    const slug = state.route.params.slug;
    const cards = state.cards.filter(c=>c.category===slug);
    const rows = cards.map(c=>`<div class="catrow"><div>${c.question}</div>
      <div class="meter" data-level="${c.stats?.box>=4?'high':(c.stats?.box>=2?'mid':'low')}"></div>
      <button class="btn ghost" data-edit="${c.id}">ערוך</button>
      <button class="btn ghost" data-del="${c.id}">מחק</button>
    </div>`).join('');
    view.innerHTML = `<section class="panel">
      <div class="row space"><h3>קטגוריה</h3><a class="btn" href="#/add?category=${encodeURIComponent(slug)}">הוסף כרטיס</a></div>
      <div class="list">${rows || '<p class="muted">אין כרטיסיות.</p>'}</div>
    </section>`;
    view.querySelectorAll('[data-del]').forEach(btn=>btn.onclick=async ()=>{
      const id=btn.getAttribute('data-del'); if(!confirm('למחוק כרטיס?')) return;
      const res=await API.deleteCard(id); if(!res.ok) return alert(res.error||'שגיאה'); await loadAll(); render();
    });
  }

  async function render(){
    await loadAll();
    const r = state.route = parseRoute();
    if (r.name==='home') return Home();
    if (r.name==='add') return Add();
    if (r.name==='test') return Test();
    if (r.name==='category') return Category();
    return Home();
  }

  render();
})();
