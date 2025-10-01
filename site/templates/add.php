<?php
if (!function_exists('svg')) {
  function svg(string $name): string {
    $icons = [
      'para' => '<svg viewBox="0 0 24 24"><path d="M7 4h10M7 8h6M7 12h10M7 16h8"/></svg>',
      'h2'   => '<svg viewBox="0 0 24 24"><path d="M4 6v12M12 6v12M4 12h8M16 10h4l-4 6h4"/></svg>',
      'h3'   => '<svg viewBox="0 0 24 24"><path d="M4 6v12M12 6v12M4 12h8M16 10h4M16 14h4"/></svg>',
      'bold' => '<svg viewBox="0 0 24 24"><path d="M7 6h6a3 3 0 0 1 0 6H7zM7 12h7a3 3 0 0 1 0 6H7z"/></svg>',
      'italic'=> '<svg viewBox="0 0 24 24"><path d="M10 6h8M6 18h8M14 6l-4 12"/></svg>',
      'underline'=>'<svg viewBox="0 0 24 24"><path d="M7 4v7a5 5 0 0 0 10 0V4M6 20h12"/></svg>',
      'ul'   => '<svg viewBox="0 0 24 24"><circle cx="5" cy="7" r="1.5"/><circle cx="5" cy="12" r="1.5"/><circle cx="5" cy="17" r="1.5"/><path d="M9 7h10M9 12h10M9 17h10"/></svg>',
      'ol'   => '<svg viewBox="0 0 24 24"><path d="M5 7h2M5 12h2M5 17h2"/><path d="M9 7h10M9 12h10M9 17h10"/></svg>',
      'align-right'  => '<svg viewBox="0 0 24 24"><path d="M4 6h16M8 10h12M4 14h16M10 18h10"/></svg>',
      'align-center' => '<svg viewBox="0 0 24 24"><path d="M4 6h16M6 10h12M4 14h16M6 18h12"/></svg>',
      'align-left'   => '<svg viewBox="0 0 24 24"><path d="M4 6h16M4 10h12M4 14h16M4 18h10"/></svg>',
      'link' => '<svg viewBox="0 0 24 24"><path d="M10 7h4"/><path d="M7 12a5 5 0 0 1 5-5h1M17 12a5 5 0 0 1-5 5h-1"/></svg>',
      'image'=> '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8" cy="10" r="2"/><path d="M21 17l-6-6-5 5-3-3-4 4"/></svg>',
      'image-edit'=>'<svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M9 14l3-3 4 4"/><path d="M14 6h4M6 6h4"/></svg>',
      'table'=> '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M3 14h18M9 5v14M15 5v14"/></svg>',
      'row-plus'  => '<svg viewBox="0 0 24 24"><path d="M3 10h18M3 14h18"/><path d="M12 6v12"/><path d="M6 6h12"/></svg>',
      'row-minus' => '<svg viewBox="0 0 24 24"><path d="M3 10h18M3 14h18"/><path d="M6 6h12"/></svg>',
      'col-plus'  => '<svg viewBox="0 0 24 24"><path d="M9 5v14M15 5v14"/><path d="M5 12h14"/><path d="M5 6h14"/></svg>',
      'col-minus' => '<svg viewBox="0 0 24 24"><path d="M9 5v14M15 5v14"/><path d="M5 6h14"/></svg>',
      'eraser'    => '<svg viewBox="0 0 24 24"><path d="M20 16l-7-7a2 2 0 0 0-3 0L4 15l5 5h6l5-4z"/></svg>',
    ];
    return $icons[$name] ?? '';
  }
}
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
  <?= snippet('global-head') ?>
  <!-- CKEditor 5 Super-build (GPL use) -->
  <script>window.CKEDITOR_LICENSE_KEY = 'GPL';</script>
  <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/super-build/ckeditor.js"></script>
  <style>
    /* ===== Modal Styles ===== */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 1001;
      padding: 20px;
      box-sizing: border-box;
      align-items: center;
      justify-content: center;
    }
    
    .modal-content {
      background: white;
      border-radius: 12px;
      padding: 24px;
      position: relative;
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
      animation: modalFadeIn 0.2s ease-out;
    }
    
    @keyframes modalFadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
    
    .modal-close {
      position: absolute;
      top: 15px;
      left: 15px;
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
      color: #666;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background-color 0.2s;
    }
    
    .modal-close:hover {
      background-color: #f3f4f6;
    }

    .ck-editor, .ck.ck-editor { width: 100%; }
    .ck-editor__editable, .ck.ck-editor__editable { min-height: 160px; }
    textarea.rte { resize: none !important; width: 100%; }
    .ck.ck-content { max-height: none; }
    html[dir="rtl"] .ck.ck-content { direction: rtl; text-align: right; }
    /* Custom numeric font-size control for CKEditor toolbar */
    .ck-toolbar .ck-custom-size {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding-inline: 8px;
      margin-inline: 8px;
      border-inline: 1px solid transparent; /* no hard edge divider */
      background: transparent;
    }
    .ck-toolbar .ck-custom-size label {
      font-size: 12px;
      color: #667085;
    }
    .ck-toolbar .ck-custom-size input[type="number"] {
      width: 64px;
      height: 28px;
      border: 1px solid var(--stroke, #e7e9ef);
      border-radius: 8px;
      padding: 0 8px;
      background: #fff;
      font: inherit;
      direction: ltr;
    }
    .ck-toolbar .ck-custom-size .unit {
      font-size: 12px;
      color: #667085;
    }
  /* Normalize CKEditor Heading sizes and weight inside editor content */
  .ck.ck-content h1 { font-size: 24px; line-height: 1.2; font-weight: 400; }
  .ck.ck-content h2 { font-size: 20px; line-height: 1.2; font-weight: 400; }
  .ck.ck-content h3 { font-size: 18px; line-height: 1.2; font-weight: 400; }
  /* Tighten paragraph spacing inside editor/preview content */
  .ck.ck-content p { margin: 0 0 8px; }
  .ck.ck-content p:last-child { margin-bottom: 0; }
  </style>
  <title>הוספת כרטיסייה</title>
</head>
<body>
  <main class="container">
    <header class="topbar">
      <h1>הוספת כרטיסייה</h1>
      <a class="backbtn" href="<?= url('flashcards') ?>" aria-label="חזרה">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
      </a>
    </header>

    <!-- בחירת קטגוריה + תת־קטגוריה + סוג שאלה -->
    <section class="section">
      <div class="row">
        <div style="flex:1; min-width:260px">
          <label for="cat">קטגוריה</label>
          <div class="row" style="align-items:flex-start; gap:8px;">
            <select id="cat" class="field" required style="flex:1;min-width:220px">
              <option value="">בחר קטגוריה…</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= html($c->slug()) ?>"><?= html($c->title()) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="btn ghost" id="quickAddCat" title="הוספת קטגוריה מהירה" aria-label="הוספת קטגוריה">＋</button>
          </div>
          <div class="row" id="quickCatRow" style="display:none; gap:8px; margin-top:6px;">
            <input type="text" id="quickCatName" class="field" placeholder="שם קטגוריה…">
            <button type="button" class="btn" id="quickCatSave">שמור</button>
            <button type="button" class="btn ghost" id="quickCatCancel">בטל</button>
            <span class="muted" id="quickCatMsg"></span>
          </div>
        </div>

        <div style="flex:1; min-width:260px">
          <label for="sub">תת־קטגוריה</label>
          <div class="row" style="align-items:flex-start; gap:8px;">
            <select id="sub" class="field" required style="flex:1;min-width:220px">
              <option value="">בחר תת־קטגוריה…</option>
            </select>
            <button type="button" class="btn ghost" id="quickAddSub" title="הוספת תת־קטגוריה מהירה" aria-label="הוספת תת־קטגוריה">＋</button>
          </div>
          <div class="row" id="quickSubRow" style="display:none; gap:8px; margin-top:6px;">
            <input type="text" id="quickSubName" class="field" placeholder="שם תת־קטגוריה…">
            <button type="button" class="btn" id="quickSubSave">שמור</button>
            <button type="button" class="btn ghost" id="quickSubCancel">בטל</button>
            <span class="muted" id="quickSubMsg"></span>
          </div>
        </div>

        <div style="flex:1; min-width:220px">
          <label for="qtype">סוג השאלה</label>
          <select id="qtype" class="field">
            <option value="free">שאלה פתוחה</option>
            <option value="mc">אמריקאית</option>
            <option value="tf">נכון / לא נכון</option>
            <option value="cloze">השלמות (Cloze)</option>
            <option value="label">תיוג על תמונה</option>
          </select>
        </div>
      </div>
    </section>

    <!-- השאלה -->
    <section class="section">
      <label>השאלה</label>
      <textarea id="q" class="rte" aria-label="שאלה"></textarea>
    </section>

    <!-- תשובה — שאלה פתוחה -->
    <section id="freeSection" class="section">
      <label>התשובה</label>
      <textarea id="a" class="rte" aria-label="תשובה"></textarea>
    </section>

    <!-- תשובה — אמריקאית -->
    <section id="mcSection" class="section" style="display:none">
      <div class="row" style="justify-content:space-between">
        <label>אפשרויות (סמן את הנכונה)</label>
        <button type="button" class="btn" id="addOpt">הוסף אפשרות</button>
      </div>
      <div class="options" id="opts"></div>
      <p class="muted">המידע נשמר כ־JSON בשדה התשובה.</p>
    </section>

    <!-- תשובה — נכון/לא נכון -->
    <section id="tfSection" class="section" style="display:none">
      <label>בחירה</label>
      <div class="row">
        <label><input type="radio" name="tf" value="true" checked> נכון</label>
        <label><input type="radio" name="tf" value="false"> לא נכון</label>
      </div>
      <p class="muted">המידע נשמר כ־JSON בשדה התשובה.</p>
    </section>

    <!-- תשובה — Cloze (השלמות) -->
<section id="clozeSection" class="section" style="display:none">
  <div class="row" style="justify-content:space-between; align-items:center;">
    <label>השלמות במשפט</label>
    <button type="button" class="btn small" id="clozeAddBlank">הוסף blank</button>
  </div>
  <p class="muted">
    השתמשי בכפתור כדי להכניס ריקים לתוך השאלה. כל ריק מסומן בתבנית
    <code>{{1}}</code>, <code>{{2}}</code>… אפשר להזין כמה תשובות נכונות לכל ריק, מופרדות בפסיק.
  </p>

  <div id="clozeBlanksWrap">
    <table class="stats-table" style="margin-top:8px;">
      <thead>
        <tr>
          <th style="width:80px">#</th>
          <th>תשובות אפשריות (מופרדות בפסיק)</th>
          <th style="width:60px"></th>
        </tr>
      </thead>
      <tbody id="clozeBlanks"></tbody>
    </table>
  </div>
</section>

    <!-- תשובה — תיוג על תמונה -->
    <section id="labelSection" class="section" style="display:none">
      <label>תיוגים על תמונה</label>
      <div class="row" style="align-items:flex-start; gap:10px;">
        <label class="btn">
          העלאת תמונה
          <input type="file" accept="image/*" id="labelImageInput" style="display:none">
        </label>
        <input type="text" class="field" id="labelImageUrl" placeholder="או הדביקי כתובת תמונה…" style="max-width:420px">
        <button type="button" class="btn small" id="labelAddBox">הוסף תיבה</button>
        <span class="muted" id="labelMsg"></span>
      </div>

      <div id="labelCanvas" style="position:relative; border:1px dashed var(--stroke); background:#fff; max-width:100%; display:none; margin-top:10px;">
        <img id="labelImg" src="" alt="תמונה לתיוג" style="max-width:100%; display:block;">
        <!-- שכבת אובייקטים -->
        <div id="labelOverlay" style="position:absolute; inset:0; pointer-events:none;"></div>
      </div>

      <div class="muted" style="font-size:13px; line-height:1.4; margin-top:8px;">
        <strong>הוראות שימוש:</strong><br>
        • <strong>הוסף תיבה</strong> - יוצר תיבת תשובה חדשה<br>
        • <strong>לחץ על תיבה</strong> - בוחר תיבה (מסגרת כחולה)<br>
        • <strong>גרור הנקודה האדומה</strong> - מזיז את נקודת העוגן (מקור הקו)<br>
        • <strong>גרור תיבה</strong> - מזיז את התיבה<br>
        • <strong>גרור העיגול הכחול</strong> - משנה גודל תיבה<br>
        • <strong>לחיצה כפולה על תיבה</strong> - עורך תשובות<br>
        • <strong>×</strong> - מוחק תיבה
      </div>
    </section>

    <!-- פעולות -->
    <section class="actions">
      <button id="save" class="btn">שמירה</button>
      <button id="preview" class="btn ghost" type="button">👁️ תצוגה מקדימה</button>
      <span id="msg" class="muted" aria-live="polite" style="min-width:180px"></span>
    </section>
  </main>

  <!-- modal לתצוגה מקדימה -->
  <div id="previewModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; padding:20px; box-sizing:border-box;">
    <div style="background:white; border-radius:12px; max-width:var(--container-w); width:100%; margin:0 auto; max-height:90vh; overflow-y:auto; padding:20px; position:relative;">
      <button id="closePreview" style="position:absolute; top:15px; left:15px; background:none; border:none; font-size:20px; cursor:pointer;">✕</button>
      <div id="previewContent">
        <!-- תוכן הpreview יוכנס כאן -->
      </div>
    </div>
  </div>

  <!-- modal לעריכת תשובות תיוג -->
  <div id="editAnswersModal" class="modal-overlay">
    <div class="modal-content" style="max-width:500px; width:100%;">
      <button id="closeEditAnswers" class="modal-close">✕</button>
      <h3 style="margin:0 0 16px 0; text-align:center;">עריכת תשובות לתיבה <span id="editBoxId"></span></h3>
      <div style="margin-bottom:16px;">
        <label style="display:block; margin-bottom:8px; font-weight:600;">תשובות נכונות (מופרדות בפסיק):</label>
        <textarea id="editAnswersText" dir="rtl" style="width:100%; min-height:80px; padding:12px; border:1px solid var(--stroke); border-radius:8px; font-family:inherit; resize:vertical;" placeholder="למשל: מיטוכונדריה, mitochondria, בית כוח התא"></textarea>
      </div>
      <div style="display:flex; gap:12px; justify-content:flex-end;">
        <button id="cancelEditAnswers" class="btn ghost">ביטול</button>
        <button id="saveEditAnswers" class="btn">שמירה</button>
      </div>
    </div>
  </div>

  <!-- modal לאישור מחיקה -->
  <div id="confirmDeleteModal" class="modal-overlay">
    <div class="modal-content" style="max-width:400px; width:100%;">
      <h3 style="margin:0 0 16px 0; text-align:center; color:#dc2626;">מחיקת תיבה</h3>
      <p style="text-align:center; margin:0 0 20px 0;">האם אתה בטוח שברצונך למחוק את תיבה <strong id="deleteBoxId"></strong>?</p>
      <div style="display:flex; gap:12px; justify-content:center;">
        <button id="cancelDelete" class="btn ghost">ביטול</button>
        <button id="confirmDelete" class="btn" style="background:#dc2626; color:white;">מחק</button>
      </div>
    </div>
  </div>

  <script>
    const $  = s => document.querySelector(s);
    const $$ = s => Array.from(document.querySelectorAll(s));

    const catEl   = $('#cat');
    const subEl   = $('#sub');
    const typeEl  = $('#qtype');
    const qEl     = $('#q');
    const aEl     = $('#a');
    const freeSec = $('#freeSection');
    const mcSec   = $('#mcSection');
    const tfSec   = $('#tfSection');
    const clozeSec = $('#clozeSection');
    const labelSec = $('#labelSection');
    const optsEl  = $('#opts');
    const msgEl   = $('#msg');

    // Helper: refresh custom select UI after programmatic changes
    function refreshCustomSelect(selectEl){
      if (!selectEl) return;
      const next = selectEl.nextElementSibling;
      if (next && next.classList && next.classList.contains('custom-select')) {
        next.remove();
      }
      selectEl.removeAttribute('data-customized');
      selectEl.style.display = '';
      document.dispatchEvent(new Event('selectsAdded'));
    }

    // מצב עריכה
    let editId = null;
    let editMode = false;

    // הוספה מהירה: קטגוריה
    const quickCatRow   = $('#quickCatRow');
    const quickCatBtn   = $('#quickAddCat');
    const quickCatName  = $('#quickCatName');
    const quickCatSave  = $('#quickCatSave');
    const quickCatCancel= $('#quickCatCancel');
    const quickCatMsg   = $('#quickCatMsg');

    quickCatBtn.addEventListener('click', ()=>{
      quickCatRow.style.display = quickCatRow.style.display==='none'||!quickCatRow.style.display ? 'flex':'none';
      if (quickCatRow.style.display==='flex') quickCatName.focus();
    });
    quickCatCancel.addEventListener('click', ()=> quickCatRow.style.display='none');
    async function api(url, payload){
      try{
        const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const t = await r.text(); try{ return JSON.parse(t); }catch{return {ok:false,error:t||r.statusText||('HTTP '+r.status)}}
      }catch(e){ return {ok:false,error:e.message||'Network error'} }
    }
    quickCatSave.addEventListener('click', async ()=>{
      const title = (quickCatName.value||'').trim();
      if (!title){ quickCatMsg.textContent='יש להזין שם'; return; }
      quickCatMsg.textContent='יוצר…';
      const r = await api('<?= url('categories/add') ?>', { title });
      if (!r.ok){ quickCatMsg.textContent='שגיאה: '+(r.error||''); return; }
      const opt = document.createElement('option');
      opt.value = r.slug; opt.textContent = title;
      catEl.appendChild(opt); catEl.value = r.slug;
      quickCatName.value=''; quickCatMsg.textContent='נוצר'; quickCatRow.style.display='none';
  refreshCustomSelect(catEl);
  loadSubcats(r.slug);
    });

    // הוספה מהירה: תת־קטגוריה
    const quickSubRow   = $('#quickSubRow');
    const quickSubBtn   = $('#quickAddSub');
    const quickSubName  = $('#quickSubName');
    const quickSubSave  = $('#quickSubSave');
    const quickSubCancel= $('#quickSubCancel');
    const quickSubMsg   = $('#quickSubMsg');

    quickSubBtn.addEventListener('click', ()=>{
      if (!catEl.value){ msgEl.textContent='בחרי קודם קטגוריה'; return; }
      quickSubRow.style.display = quickSubRow.style.display==='none'||!quickSubRow.style.display ? 'flex':'none';
      if (quickSubRow.style.display==='flex') quickSubName.focus();
    });
    quickSubCancel.addEventListener('click', ()=> quickSubRow.style.display='none');
    quickSubSave.addEventListener('click', async ()=>{
      const title = (quickSubName.value||'').trim();
      if (!title){ quickSubMsg.textContent='יש להזין שם'; return; }
      if (!catEl.value){ quickSubMsg.textContent='בחרי קטגוריה'; return; }
      quickSubMsg.textContent='יוצר…';
      const r = await api('<?= url('subcats/add') ?>', { category: catEl.value, title });
      if (!r.ok){ quickSubMsg.textContent='שגיאה: '+(r.error||''); return; }
      const opt = document.createElement('option');
      opt.value = r.slug; opt.textContent = title;
      subEl.appendChild(opt); subEl.value = r.slug;
      quickSubName.value=''; quickSubMsg.textContent='נוצר'; quickSubRow.style.display='none';
      // Refresh custom UI after programmatic change
      if (typeof document !== 'undefined') {
        const next = subEl.nextElementSibling;
        if (next && next.classList && next.classList.contains('custom-select')) next.remove();
        subEl.removeAttribute('data-customized');
        subEl.style.display = '';
        document.dispatchEvent(new Event('selectsAdded'));
      }
    });

    // Event listener לשינוי קטגוריה - טעינת תת-קטגוריות
    catEl.addEventListener('change', async () => {
      // מצב טעינה ו-non-selectable עד לקבלת הרשימה
      if (subEl) {
        subEl.innerHTML = '<option value="">טוען תת־קטגוריות…</option>';
        subEl.disabled = true;
        // Update custom UI to reflect loading state
        const next = subEl.nextElementSibling;
        if (next && next.classList && next.classList.contains('custom-select')) next.remove();
        subEl.removeAttribute('data-customized');
        subEl.style.display = '';
        document.dispatchEvent(new Event('selectsAdded'));
      }
      await loadSubcats(catEl.value);
      msgEl.textContent = ''; // מנקה הודעות שגיאה קודמות
    });

    // CKEditor
    let editorQ=null, editorA=null;

    const ckToolbar = {
      items: [
        'heading',
        '|',
        'bold','italic','underline','fontSize',
        'fontColor','fontBackgroundColor',
        '|',
        'link',
        'specialCharacters',
        'bulletedList','numberedList',
        '|',
        'alignment',
        'outdent','indent',
        '|',
        'uploadImage','insertTable',
        '|',
        'undo','redo'
      ],
      shouldNotGroupWhenFull: true
    };

    // Aggressively remove all premium/collaboration plugins that trigger license or dependency errors
    const ckRemove = [
      // Collab / comments / track changes
      'RealTimeCollaborativeComments','RealTimeCollaborativeTrackChanges','RealTimeCollaborativeRevisionHistory',
      'PresenceList','Comments','CommentsRepository','Users',
      'TrackChanges','TrackChangesEditing','TrackChangesData',
      'RevisionHistory','AnnotationsUIs','Annotations',

      // Cloud storage & proofreading
      'CKBox','CKFinder','EasyImage','WProofreader',

      // Premium / AI / formatting bundles
      'AIAssistant','FormatPainter','SlashCommand','Template','MultiLevelList','CaseChange',

      // Import / Export
      'ExportPdf','ExportWord','WordExport','PdfExport','ImportWord','ImportPdf',

      // Navigation / outline / pagination / TOC
      'DocumentOutline','Pagination','TableOfContents',

      // Math / grammar
      'MathType','Grammar',

      // Office enhanced paste
      'PasteFromOfficeEnhanced'
    ];

    function waitForCk(){
      return new Promise((res, rej) => {
        let tries = 0;
        (function loop(){
          const ctor = (window.CKEDITOR && window.CKEDITOR.ClassicEditor) || window.ClassicEditor;
          if (ctor) return res(ctor);
          if (tries++ > 200) return rej(new Error('CKEditor failed to load'));
          setTimeout(loop, 50);
        })();
      });
    }

    function initEditor(elId, placeholder){
      const el = document.getElementById(elId);
      if (!el) return Promise.resolve(null);
      return waitForCk().then(EditorCtor => {
      return EditorCtor.create(el, {
        licenseKey: 'GPL',
        language: { ui: 'he', content: 'he' },
        placeholder,
        toolbar: ckToolbar,
        fontSize: {
          options: [ '10px', '12px', '14px', '16px', '18px', '24px', '32px', '48px' ]
        },
        removePlugins: ckRemove,
        image: {
          toolbar: [ 'imageTextAlternative','toggleImageCaption','imageStyle:inline','imageStyle:block','imageStyle:side','linkImage','resizeImage' ],
          insert: { integrations: [ 'uploadImage' ] }
        },
        simpleUpload: {
          uploadUrl: '<?= url('upload') ?>',
          withCredentials: false
        },
        table: {
          contentToolbar: [ 'tableColumn','tableRow','mergeTableCells' ]
        },
        specialCharacters: {
          categories: [
            'Essentials',
            'Text',
            'Latin',
            'Mathematical',
            'Currency',
            'Arrows',
            {
              name: 'סימנים מדעיים',
              items: [
                { title: 'α', character: 'α' },
                { title: 'β', character: 'β' },
                { title: 'γ', character: 'γ' },
                { title: 'Δ', character: 'Δ' },
                { title: 'π', character: 'π' },
                { title: 'μ', character: 'μ' },
                { title: 'Ω', character: 'Ω' },
                { title: '°', character: '°' },
                { title: '±', character: '±' },
                { title: '×', character: '×' },
                { title: '÷', character: '÷' },
                { title: '≤', character: '≤' },
                { title: '≥', character: '≥' },
                { title: '≈', character: '≈' },
                { title: '≠', character: '≠' },
                { title: '→', character: '→' },
                { title: '←', character: '←' },
                { title: '↔', character: '↔' },
                { title: '∞', character: '∞' },
                { title: '√', character: '√' },
                { title: '∑', character: '∑' },
                { title: '∫', character: '∫' }
              ]
            }
          ]
        },
        heading: {
          options: [
            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
            { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
            { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
          ]
        },
        htmlSupport: { allow: [ { name: /.*/, attributes: true, classes: true, styles: true } ] }
      }).then(ed=>{
          ed.editing.view.change( writer => {
            writer.setAttribute('dir','rtl', ed.editing.view.document.getRoot());
            writer.setStyle('text-align','right', ed.editing.view.document.getRoot());
          });
          
          // Set default format to Heading 1 for question editor without visual flicker
          if (elId === 'q') {
            const wrap = ed.ui?.view?.element;
            if (wrap) wrap.style.visibility = 'hidden';
            ed.model.change(writer => {
              const root = ed.model.document.getRoot();
              const first = root.getChild(0);
              // If CKEditor created an initial empty paragraph, rename it to heading1.
              if (first && first.is?.('element','paragraph') && first.childCount === 0) {
                writer.rename(first, 'heading1');
                writer.setSelection(first, 'in');
              } else if (root.childCount === 0) {
                // Truly empty root – create heading1 as the first block.
                const heading = writer.createElement('heading1');
                writer.insert(heading, root, 0);
                writer.setSelection(heading, 'in');
              }
            });
            // Reveal the editor after the model update has been applied
            setTimeout(() => { if (wrap) wrap.style.visibility = ''; }, 0);
          }
          
          // טיפול פשוט יותר בהדבקת טקסט - הסרת עיצוב חיצוני
          ed.editing.view.document.on('clipboardInput', (evt, data) => {
            // נשתמש באופציה הפשוטה של CKEditor להמרה לטקסט פשוט
            if (data.method === 'paste') {
              const clipboardData = data.dataTransfer;
              const plainText = clipboardData.getData('text/plain');
              
              if (plainText) {
                // נעצור את ההתנהגות הרגילה
                evt.stop();
                
                // נכניס את הטקסט הפשוט במקום
                ed.model.change(writer => {
                  const insertPosition = ed.model.document.selection.getFirstPosition();
                  writer.insertText(plainText, insertPosition);
                });
              }
            }
          }, { priority: 'high' });
          
          return ed;
        });
      }).catch(err => { 
        console.error(err);
        // fallback – נשאיר textarea רגיל
        el.removeAttribute('disabled');
        el.style.pointerEvents = 'auto';
        return null; 
      });
    }


document.addEventListener('DOMContentLoaded', async ()=>{
  const urlParams = new URLSearchParams(location.search);
  const initCat = urlParams.get('category');
  const initSub = urlParams.get('subcategory') || urlParams.get('sub');
  const initId  = urlParams.get('id');

  // אתחול עורך(ים)
  editorQ = await initEditor('q','כתבי את השאלה…');
  editorA = await initEditor('a','כתבי את התשובה…');

  // מיקוד על עורך השאלות אם זה כרטיסייה חדשה
  if (editorQ && !initId) {
    setTimeout(() => {
      editorQ.editing.view.focus();
    }, 100);
  }

  // סנכרון Cloze (השלמות)
  attachClozeSync();

  // אם באנו מערוץ "הוספה": בוחרים קטגוריה/תת־קטגוריה מראש
  if (initCat && !initId) {
    catEl.value = initCat;
    refreshCustomSelect(catEl);
    await loadSubcats(initCat, initSub || '');
  } else if (!initCat && initSub && !initId) {
    // מצב: יש תת־קטגוריה ב-URL אך אין קטגוריה – ננסה לאתר את קטגוריית האם
    try {
      const options = Array.from(catEl?.options || []);
      for (const opt of options) {
        const slug = opt.value;
        if (!slug) continue;
        const resp = await fetch('/subcats?category=' + encodeURIComponent(slug));
        if (!resp.ok) continue;
        const data = await resp.json();
        if (data && data.ok && Array.isArray(data.subcategories)) {
          const found = data.subcategories.find(s => s.slug === initSub);
          if (found) {
            catEl.value = slug;
            refreshCustomSelect(catEl);
            await loadSubcats(slug, initSub);
            break;
          }
        }
      }
    } catch (err) {
      console.warn('Auto-detect category for sub failed:', err);
    }
  }

  // מצב עריכה: טוען כרטיסייה וממלא שדות
  if (initId) {
    try {
      const r = await fetch('/card?id=' + encodeURIComponent(initId));
      const t = await r.text();
      const j = JSON.parse(t);
      if (!j.ok) throw new Error(j.error || 'Load failed');
      const c = j.card;
      editId   = c.id;
      editMode = true;

      // קבע קטגוריה ותת־קטגוריה
      if (c.category) {
        catEl.value = c.category;
        await loadSubcats(c.category, c.subcategory || '');
      }

      // סוג השאלה
      const ttype = (c.type || 'free');
      typeEl.value = ttype;
      setTypeVisibility();

      // תוכן שאלה/תשובה
      if (ttype === 'free') {
        if (editorQ) editorQ.setData(c.question_raw || ''); else qEl.value = c.question_raw || '';
        if (editorA) editorA.setData(c.answer_raw   || ''); else aEl.value = c.answer_raw   || '';
      } else if (ttype === 'mc') {
        if (editorQ) editorQ.setData(c.question_raw || ''); else qEl.value = c.question_raw || '';
        // פרש תשובות
        let data = null;
        try { data = JSON.parse(c.answer_raw || '{}'); } catch(e) { data = null; }
        optsEl.innerHTML = '';
        const arr = (data && Array.isArray(data.options)) ? data.options : [];
        if (arr.length) { arr.forEach(o => addOption(o.text || '', !!o.correct)); }
        else { addOption(); addOption(); }
      } else if (ttype === 'tf') {
        if (editorQ) editorQ.setData(c.question_raw || ''); else qEl.value = c.question_raw || '';
        let data = null;
        try { data = JSON.parse(c.answer_raw || '{}'); } catch(e) { data = null; }
        const val = !!(data && data.value);
        const radio = document.querySelector(`input[name="tf"][value="${val ? 'true':'false'}"]`);
        if (radio) radio.checked = true;
      }
      else if (ttype === 'cloze') {
  if (editorQ) editorQ.setData(c.question_raw || ''); else qEl.value = c.question_raw || '';
  let data = null;
  try { data = JSON.parse(c.answer_raw || '{}'); } catch(e) { data = null; }
  // בנה טבלת ריקים לפי השאלה
  syncClozeFromQuestion();
  if (data && Array.isArray(data.blanks)) {
    data.blanks.forEach(b=>{
      const tr = clozeBlanksTbody.querySelector(`tr[data-id="${b.id}"]`);
      if (!tr) {
        clozeBlanksTbody.appendChild(makeClozeRow(b.id, b.answers||[]));
      } else {
        tr.querySelector('input[type="text"]').value = (b.answers||[]).join(', ');
      }
    });
  }
}
      else if (ttype === 'label') {
        if (editorQ) editorQ.setData(c.question_raw || ''); else qEl.value = c.question_raw || '';
        let data = null;
        try { data = JSON.parse(c.answer_raw || '{}'); } catch(e) { data = null; }
        if (data && data.image) {
          labelImg.onload = ()=> { ensureCanvasVisible(); renderLabelOverlay(); };
          labelImg.src = data.image;
        }
        labelItems = (data && Array.isArray(data.items)) ? data.items : [];
        ensureCanvasVisible();
        renderLabelOverlay();
      }

      // טקסט הכפתור למצב עריכה
      const saveBtn = document.getElementById('save');
      if (saveBtn) saveBtn.textContent = 'עדכון';
    } catch (e) {
      console.error(e);
      msgEl.textContent = 'שגיאה בטעינת כרטיסייה לעריכה';
    }
  }

  setTypeVisibility();
});

    function setTypeVisibility(){
      const t = typeEl.value;
      freeSec.style.display  = (t === 'free')  ? '' : 'none';
      mcSec.style.display    = (t === 'mc')    ? '' : 'none';
      tfSec.style.display    = (t === 'tf')    ? '' : 'none';
      clozeSec.style.display = (t === 'cloze') ? '' : 'none';
      labelSec.style.display = (t === 'label') ? '' : 'none';
      if (t === 'mc' && optsEl && optsEl.children.length === 0){
        addOption(); addOption();
      }
      if (t === 'cloze'){ syncClozeFromQuestion(); }
    }
    typeEl.addEventListener('change', setTypeVisibility);

    // ===== CLOZE (השלמות) =====
    const clozeBlanksTbody = document.getElementById('clozeBlanks');
    const clozeAddBlankBtn = document.getElementById('clozeAddBlank');

    function parseClozeTokens(html){
      // מחזיר מזהי {{n}} לפי סדר הופעה, בלי כפילויות
      const ids = [];
      const re = /\{\{\s*(\d+)\s*\}\}/g;
      let m;
      while ((m = re.exec(html)) !== null) {
        const id = parseInt(m[1],10);
        if (!ids.includes(id)) ids.push(id);
      }
      return ids;
    }

function makeClozeRow(id, answers = []){
  const tr = document.createElement('tr');
  tr.dataset.id = String(id);
  tr.innerHTML = `
    <td><strong>${id}</strong></td>
    <td><input type="text" class="field" placeholder="למשל: מיטוכונדריה, mitochondria" value="${answers.join(', ')}"></td>
    <td style="text-align:center"><button type="button" class="btn small ghost">מחק</button></td>
  `;
  tr.querySelector('button').addEventListener('click', ()=> tr.remove());
  return tr;
}

 function syncClozeFromQuestion(){
  const html = (editorQ ? editorQ.getData() : qEl.value || '');
  const ids = parseClozeTokens(html);

  // שמור תשובות קיימות
  const current = {};
  clozeBlanksTbody.querySelectorAll('tr').forEach(tr=>{
    const id = parseInt(tr.dataset.id,10);
    const answers = tr.querySelector('input[type="text"]').value
      .split(',')
      .map(s=>s.trim())
      .filter(Boolean);
    current[id] = { answers };
  });

  // בנה מחדש לפי סדר הופעה
  clozeBlanksTbody.innerHTML = '';
  ids.forEach(id=>{
    const cfg = current[id] || { answers:[] };
    clozeBlanksTbody.appendChild(makeClozeRow(id, cfg.answers));
  });
}

if (clozeAddBlankBtn){
  clozeAddBlankBtn.addEventListener('click', ()=>{
    // חשב מזהה ריק חדש לפי הטוקנים הקיימים
    const html = (editorQ ? editorQ.getData() : qEl.value || '');
    const ids = parseClozeTokens(html);
    const next = (ids.length ? Math.max(...ids)+1 : 1);
    const token = `{{${next}}}`;

    // נשמור את הטקסט המסומן (אם יש) כדי להוסיף אוטומטית לתשובות
    let selectedText = '';

    if (editorQ){
      // שליפת תוכן הבחירה (כ־HTML) → הפשטה לטקסט
      const sel = editorQ.model.document.selection;
      const frag = editorQ.model.getSelectedContent( sel );
      const htmlFrag = editorQ.data.stringify( frag );
      const tmp = document.createElement('div');
      tmp.innerHTML = htmlFrag;
      selectedText = (tmp.textContent || '').trim();

      // הכנסת הטוקן (מחליף בחירה אם קיימת)
      editorQ.model.change( writer => {
        editorQ.model.insertContent( writer.createText( token ) );
      });
      editorQ.editing.view.focus();
    } else {
      // textarea fallback
      const start = qEl.selectionStart ?? qEl.value.length;
      const end   = qEl.selectionEnd   ?? qEl.value.length;
      selectedText = qEl.value.slice(start, end).trim();
      qEl.setRangeText(token, start, end, 'end');
      qEl.focus();
    }

    // עדכון טבלת ה־Cloze ולאחר מכן הזרקת הטקסט המסומן כתשובה לריק החדש (אם קיים)
    setTimeout(()=>{
      syncClozeFromQuestion();

      if (selectedText){
        const tr = clozeBlanksTbody.querySelector(`tr[data-id="${next}"]`);
        if (tr){
          const input = tr.querySelector('input[type="text"]');
          const current = input.value.trim();
          const parts = (current ? current.split(',') : []).map(s=>s.trim()).filter(Boolean);
          if (!parts.includes(selectedText)) parts.push(selectedText);
          input.value = parts.join(', ');
        }
      }
    }, 0);
  });
}

    // סנכרון על שינוי תוכן השאלה (כדי לעדכן רשימת הריקים)
    function attachClozeSync(){
      if (editorQ){
        editorQ.model.document.on('change:data', ()=> syncClozeFromQuestion());
      } else {
        qEl.addEventListener('input', syncClozeFromQuestion);
      }
    }

    // ===== LABEL (תיוגים על תמונה) =====
    const labelImageInput = document.getElementById('labelImageInput');
    const labelImageUrl   = document.getElementById('labelImageUrl');
    const labelCanvas     = document.getElementById('labelCanvas');
    const labelImg        = document.getElementById('labelImg');
    const labelOverlay    = document.getElementById('labelOverlay');
    const labelAddBoxBtn  = document.getElementById('labelAddBox');
    const labelMsg        = document.getElementById('labelMsg');

    let labelItems = []; // {id, anchor:{x,y}, box:{x,y,w,h}, answers:[], ignoreCase, regex}

    function norm(val, total){ return Math.max(0, Math.min(1, val / total)); }
    function denorm(val, total){ return Math.round(val * total); }

    function nextLabelId(){ return labelItems.length ? Math.max(...labelItems.map(i=>i.id))+1 : 1; }

    function renderLabelOverlay(){
      // נקה
      labelOverlay.innerHTML = '';
      if (!labelImg.naturalWidth) return;

      const W = labelImg.clientWidth;
      const H = labelImg.clientHeight;

      labelItems.forEach(item=>{
        // חישוב מיקומים
        const ax = denorm(item.anchor.x, W);
        const ay = denorm(item.anchor.y, H);
        
        // מיקום התיבה
        const boxLeft = denorm(item.box.x, W);
        const boxTop = denorm(item.box.y, H);
        const boxWidth = denorm(item.box.w, W);
        const boxHeight = denorm(item.box.h, H);
        
        // מרכז התיבה (לשם חיבור הקו)
        const boxCenterX = boxLeft + boxWidth / 2;
        const boxCenterY = boxTop + boxHeight / 2;
        
        // קו מהנקודה למרכז התיבה
        const line = document.createElement('div');
        line.style.position = 'absolute';
        line.style.pointerEvents = 'none';
        line.style.borderTop = '2px solid #2563eb';
        line.style.zIndex = '1';
        line.style.transformOrigin = '0 0';
        
        const length = Math.hypot(boxCenterX - ax, boxCenterY - ay);
        const angle = Math.atan2(boxCenterY - ay, boxCenterX - ax) * 180 / Math.PI;
        
        line.style.left = ax + 'px';
        line.style.top = ay + 'px';
        line.style.width = length + 'px';
        line.style.transform = `rotate(${angle}deg)`;
        labelOverlay.appendChild(line);

        // תיבה
        const box = document.createElement('div');
        box.className='label-box';
        box.dataset.id=String(item.id);
        box.style.position='absolute';
        box.style.left = boxLeft + 'px';
        box.style.top = boxTop + 'px';
        box.style.width = boxWidth + 'px';
        box.style.height = boxHeight + 'px';
        box.style.border='2px solid #2563eb';
        box.style.background='rgba(255,255,255,0.95)';
        box.style.borderRadius='8px';
        box.style.padding='8px';
        box.style.cursor='move';
        box.style.pointerEvents='auto';
        box.style.display='flex';
        box.style.flexDirection='column';
        box.style.zIndex='2';
        box.innerHTML = `
          <div style="font-weight:700; margin-bottom:0px; font-size:11px; color:#2563eb; position:absolute;">${item.id}</div>
          <div style="font-size:16px; color:#666; overflow:hidden; text-overflow:ellipsis; text-align:center; display:flex; align-items:center; justify-content:center; flex:1;">${(item.answers||[]).join(' / ') || 'לחץ פעמיים לעריכה'}</div>
          <div class="resize-handle" style="position:absolute; width:12px; height:12px; right:-6px; bottom:-6px; background:#2563eb; border-radius:50%; cursor:nwse-resize; border:2px solid white;"></div>
          <button class="delete-box" style="position:absolute; top:-6px; left:-6px; width:16px; height:16px; background:#dc2626; color:white; border:none; border-radius:50%; cursor:pointer; font-size:10px; display:flex; align-items:center; justify-content:center;" title="מחק תיבה">×</button>
        `;
        
        // סימון בחירה
        if (selectedLabelBox === item.id) {
          box.style.boxShadow = '0 0 0 3px rgba(37, 99, 235, 0.3)';
        }
        
        labelOverlay.appendChild(box);

        // בחירת תיבה
        box.addEventListener('click', (ev) => {
          if (ev.detail === 2) return; // מונע התנגשות עם double-click
          selectedLabelBox = selectedLabelBox === item.id ? null : item.id;
          renderLabelOverlay();
          ev.stopPropagation();
        });
        
        // גרירה
        box.addEventListener('mousedown', startDrag);
        // עריכה כפולה
        box.addEventListener('dblclick', ()=> editLabelAnswers(item.id));
        // מחיקת תיבה
        box.querySelector('.delete-box').addEventListener('click', (ev) => {
          ev.stopPropagation();
          showDeleteConfirmation(item.id);
        });
        // שינוי גודל
        box.querySelector('.resize-handle').addEventListener('mousedown', startResize);
        function startDrag(ev){
          ev.preventDefault();
          const startX = ev.clientX, startY = ev.clientY;
          const startLeft = denorm(item.box.x, W), startTop = denorm(item.box.y, H);
          function onMove(e){
            const dx=e.clientX-startX, dy=e.clientY-startY;
            const nx = norm(startLeft+dx, W), ny = norm(startTop+dy, H);
            item.box.x = Math.max(0, Math.min(1, nx));
            item.box.y = Math.max(0, Math.min(1, ny));
            renderLabelOverlay();
          }
          function onUp(){ window.removeEventListener('mousemove', onMove); window.removeEventListener('mouseup', onUp); }
          window.addEventListener('mousemove', onMove);
          window.addEventListener('mouseup', onUp);
        }
        function startResize(ev){
          ev.stopPropagation(); ev.preventDefault();
          const startX = ev.clientX, startY = ev.clientY;
          const startW = denorm(item.box.w, W), startH = denorm(item.box.h, H);
          function onMove(e){
            const dw=e.clientX-startX, dh=e.clientY-startY;
            const nw = Math.max(30, startW+dw);
            const nh = Math.max(24, startH+dh);
            item.box.w = Math.max(0, Math.min(1, norm(nw, W)));
            item.box.h = Math.max(0, Math.min(1, norm(nh, H)));
            renderLabelOverlay();
          }
          function onUp(){ window.removeEventListener('mousemove', onMove); window.removeEventListener('mouseup', onUp); }
          window.addEventListener('mousemove', onMove);
          window.addEventListener('mouseup', onUp);
        }
      });
      
      // הוספת נקודות עוגן
      labelItems.forEach(item => {
        const ax = denorm(item.anchor.x, W);
        const ay = denorm(item.anchor.y, H);
        
        const anchor = document.createElement('div');
        anchor.style.position = 'absolute';
        anchor.style.left = (ax - 6) + 'px';
        anchor.style.top = (ay - 6) + 'px';
        anchor.style.width = '12px';
        anchor.style.height = '12px';
        anchor.style.background = '#dc2626';
        anchor.style.border = '2px solid white';
        anchor.style.borderRadius = '50%';
        anchor.style.zIndex = '3';
        anchor.style.cursor = 'move';
        anchor.style.pointerEvents = 'auto';
        anchor.title = `גרור כדי להזיז עוגן ${item.id}`;
        anchor.dataset.itemId = String(item.id);
        
        // גרירת נקודת העוגן
        anchor.addEventListener('mousedown', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          
          const startX = ev.clientX;
          const startY = ev.clientY;
          const startAx = denorm(item.anchor.x, W);
          const startAy = denorm(item.anchor.y, H);
          
          function onMove(e) {
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            const newAx = startAx + dx;
            const newAy = startAy + dy;
            
            // עדכון מיקום העוגן
            item.anchor.x = Math.max(0, Math.min(1, norm(newAx, W)));
            item.anchor.y = Math.max(0, Math.min(1, norm(newAy, H)));
            renderLabelOverlay();
          }
          
          function onUp() {
            window.removeEventListener('mousemove', onMove);
            window.removeEventListener('mouseup', onUp);
          }
          
          window.addEventListener('mousemove', onMove);
          window.addEventListener('mouseup', onUp);
        });
        
        labelOverlay.appendChild(anchor);
      });
    }

    function editLabelAnswers(id){
      const item = labelItems.find(i=>i.id===id);
      if (!item) return;
      
      // פתיחת modal לעריכה
      const modal = document.getElementById('editAnswersModal');
      const boxIdSpan = document.getElementById('editBoxId');
      const textarea = document.getElementById('editAnswersText');
      
      boxIdSpan.textContent = id;
      textarea.value = (item.answers||[]).join(', ');
      modal.style.display = 'flex';
      textarea.focus();
      
      // פונקציה לשמירה
      const saveBtn = document.getElementById('saveEditAnswers');
      const cancelBtn = document.getElementById('cancelEditAnswers');
      const closeBtn = document.getElementById('closeEditAnswers');
      
      function closeModal() {
        modal.style.display = 'none';
      }
      
      function saveAnswers() {
        const val = textarea.value.trim();
        item.answers = val ? val.split(',').map(s=>s.trim()).filter(Boolean) : [];
        renderLabelOverlay();
        closeModal();
      }
      
      // הסרת event listeners קודמים
      saveBtn.replaceWith(saveBtn.cloneNode(true));
      cancelBtn.replaceWith(cancelBtn.cloneNode(true));
      closeBtn.replaceWith(closeBtn.cloneNode(true));
      
      // הוספת event listeners חדשים
      document.getElementById('saveEditAnswers').addEventListener('click', saveAnswers);
      document.getElementById('cancelEditAnswers').addEventListener('click', closeModal);
      document.getElementById('closeEditAnswers').addEventListener('click', closeModal);
      
      // סגירה בלחיצה על רקע
      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
      });
      
      // שמירה ב-Enter
      textarea.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.ctrlKey) {
          e.preventDefault();
          saveAnswers();
        }
      });
    }

    function showDeleteConfirmation(id) {
      const modal = document.getElementById('confirmDeleteModal');
      const boxIdSpan = document.getElementById('deleteBoxId');
      
      boxIdSpan.textContent = id;
      modal.style.display = 'flex';
      
      const confirmBtn = document.getElementById('confirmDelete');
      const cancelBtn = document.getElementById('cancelDelete');
      
      function closeModal() {
        modal.style.display = 'none';
      }
      
      function deleteBox() {
        const index = labelItems.findIndex(i => i.id === id);
        if (index > -1) {
          labelItems.splice(index, 1);
          if (selectedLabelBox === id) selectedLabelBox = null;
          renderLabelOverlay();
          labelMsg.textContent = `תיבה ${id} נמחקה`;
          setTimeout(() => labelMsg.textContent = '', 2000);
        }
        closeModal();
      }
      
      // הסרת event listeners קודמים
      confirmBtn.replaceWith(confirmBtn.cloneNode(true));
      cancelBtn.replaceWith(cancelBtn.cloneNode(true));
      
      // הוספת event listeners חדשים
      document.getElementById('confirmDelete').addEventListener('click', deleteBox);
      document.getElementById('cancelDelete').addEventListener('click', closeModal);
      
      // סגירה בלחיצה על רקע
      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
      });
    }

    function ensureCanvasVisible(){
      labelCanvas.style.display = 'block';
      // רינדור ליתר בטחון אחרי הטעינה
      setTimeout(renderLabelOverlay, 50);
    }

    if (labelImageInput){
      labelImageInput.addEventListener('change', async ()=>{
        const f = labelImageInput.files?.[0];
        if (!f) return;
        labelMsg.textContent='מעלה…';
        const fd = new FormData(); fd.append('image', f);
        try{
          const r = await fetch('<?= url('upload') ?>', { method:'POST', body: fd });
          const t = await r.text(); const j = JSON.parse(t);
          if (!j.ok || !j.url) throw new Error(j.error || 'Upload failed');
          labelImg.src = j.url; ensureCanvasVisible();
          labelMsg.textContent='נטען';
        }catch(e){
          labelMsg.textContent='שגיאה בהעלאה';
        } finally { labelImageInput.value=''; }
      });
    }

    if (labelImageUrl){
      labelImageUrl.addEventListener('change', ()=>{
        const url = labelImageUrl.value.trim();
        if (!url) return;
        labelImg.onload = ()=> { ensureCanvasVisible(); };
        labelImg.onerror = ()=> { labelMsg.textContent='שגיאה בטעינת תמונה'; };
        labelImg.src = url;
      });
    }

    if (labelAddBoxBtn){
      labelAddBoxBtn.addEventListener('click', ()=>{
        if (!labelImg.src){ labelMsg.textContent='ראשית יש לטעון תמונה'; return; }
        const id = nextLabelId();
        // תיבה חדשה במיקום טוב יותר
        const existingBoxes = labelItems.length;
        const yOffset = 0.1 + (existingBoxes * 0.15) % 0.7; // מרווח אנכי בין תיבות
        
        // חישוב גובה קבוע - 60 פיקסלים יחסית לגובה התמונה הנוכחי
        const currentImgHeight = labelImg.clientHeight || 400; // fallback אם אין גובה
        const fixedHeightInPixels = 60; // גובה רצוי בפיקסלים
        const heightRatio = fixedHeightInPixels / currentImgHeight;
        
        labelItems.push({
          id,
          anchor:{ x:0.3, y:0.3 }, // נקודת עוגן בשליש העליון השמאלי
          box:{ x:0.65, y:yOffset, w:0.25, h:heightRatio }, // תיבה בצד ימין - גובה קבוע של 60px
          answers:[],
          ignoreCase:true, regex:false
        });
        renderLabelOverlay();
        // הודעה עזר
        labelMsg.textContent = `נוספה תיבה ${id}. גרור את הנקודה האדומה כדי להזיז את העוגן.`;
        setTimeout(() => labelMsg.textContent = '', 3000);
      });
    }

    // מעקב אחר התיבה שנבחרה
    let selectedLabelBox = null;

    // עוגן: לחיצה על התמונה קובעת עוגן לתיבה שנבחרה
    labelCanvas?.addEventListener('click', (ev)=>{
      if (!labelItems.length) return;
      if (!labelImg.naturalWidth) return;
      
      // אם לחצנו על תיבה, לא על התמונה עצמה
      if (ev.target !== labelCanvas && ev.target !== labelImg) return;
      
      const rect = labelCanvas.getBoundingClientRect();
      const W = labelImg.clientWidth;
      const H = labelImg.clientHeight;
      const x = (ev.clientX - rect.left) / W;
      const y = (ev.clientY - rect.top)  / H;
      
      // אם יש תיבה נבחרת, עדכן את העוגן שלה
      if (selectedLabelBox) {
        const item = labelItems.find(i => i.id === selectedLabelBox);
        if (item) {
          item.anchor = { x: Math.max(0, Math.min(1, x)), y: Math.max(0, Math.min(1, y)) };
          renderLabelOverlay();
          labelMsg.textContent = `עוגן תיבה ${item.id} עודכן`;
          setTimeout(() => labelMsg.textContent = '', 2000);
        }
      } else {
        // אחרת, עדכן את העוגן של התיבה האחרונה
        const last = labelItems[labelItems.length-1];
        last.anchor = { x: Math.max(0, Math.min(1, x)), y: Math.max(0, Math.min(1, y)) };
        renderLabelOverlay();
        labelMsg.textContent = `עוגן תיבה ${last.id} עודכן`;
        setTimeout(() => labelMsg.textContent = '', 2000);
      }
    });

    // בעת שינוי גודל התמונה (Responsive), נרנדר שוב
    window.addEventListener('resize', ()=> renderLabelOverlay());
    labelImg.addEventListener('load', ()=> renderLabelOverlay());

    // אמריקאית
    function addOption(text='', correct=false){
      const row = document.createElement('div'); row.className = 'optrow';
      row.innerHTML = `
        <input type="text" class="field" placeholder="תשובה..." value="${text.replace(/"/g,'&quot;')}" dir="rtl">
        <label class="row" style="justify-content:center; gap:6px">
          <input type="radio" name="mc-correct" ${correct?'checked':''}> נכונה
        </label>
        <button type="button" class="btn ghost" aria-label="מחק">מחק</button>
      `;
      row.querySelector('button').addEventListener('click', ()=> row.remove());
      const radio = row.querySelector('input[type="radio"]');
      radio.addEventListener('change', ()=> {
        $$('input[name="mc-correct"]').forEach(r => { if(r !== radio) r.checked = false; });
      });
      optsEl.appendChild(row);
    }
    (function seedMc(){ addOption(); addOption(); })();

    // חיבור כפתור "הוסף אפשרות" לשאלות אמריקאיות
    const addOptBtn = document.getElementById('addOpt');
    if (addOptBtn) addOptBtn.addEventListener('click', ()=> addOption());

async function loadSubcats(cat, preselectSub = '') {
  if (!subEl) return;
  subEl.innerHTML = '<option value="">טוען תת־קטגוריות…</option>';
  subEl.disabled = true;
  if (!cat) {
    subEl.innerHTML = '<option value="">קודם בחר קטגוריה</option>';
    subEl.disabled = true;
    return;
  }
  try{
    const r = await fetch('/subcats?category=' + encodeURIComponent(cat));
    if (!r.ok) {
      subEl.innerHTML = '<option value="">שגיאה בטעינת תת־קטגוריות</option>';
      subEl.disabled = false;
      return;
    }
    const j = await r.json();
    if(!j.ok){ 
      subEl.innerHTML = '<option value="">אין תת־קטגוריות לקטגוריה זו</option>'; 
      subEl.disabled = false;
      return; 
    }
    const list = j.subcategories || [];
    if (list.length === 0) {
      subEl.innerHTML = '<option value="">אין תת־קטגוריות - נא ליצור תת־קטגוריה חדשה</option>';
      subEl.disabled = false;
    } else {
      subEl.innerHTML = '<option value="">בחר תת־קטגוריה…</option>' +
        list.map(s => `<option value="${s.slug}">${s.title}</option>`).join('');
      subEl.disabled = false;
    }
    if (preselectSub) subEl.value = preselectSub;
    // Re-init custom select UI after repopulating options
    const next = subEl.nextElementSibling;
    if (next && next.classList && next.classList.contains('custom-select')) next.remove();
    subEl.removeAttribute('data-customized');
    subEl.style.display = '';
    document.dispatchEvent(new Event('selectsAdded'));
  }catch(e){
    console.error('Error loading subcategories:', e);
    subEl.innerHTML = '<option value="">שגיאה בטעינת תת־קטגוריות</option>';
    subEl.disabled = false;
  }
}

    function buildAnswerByType(){
      const t = typeEl.value;
      if (t === 'free'){
        return (editorA ? editorA.getData() : (document.getElementById('a')?.value || '') ).trim();
      }
      if (t === 'mc'){
        const rows = $$('.optrow');
        const options = rows.map(row => {
          const text = row.querySelector('input[type="text"]').value.trim();
          const correct = row.querySelector('input[type="radio"]').checked;
          return { text, correct };
        }).filter(o => o.text !== '');
        if (options.length === 0) return '';
        return JSON.stringify({ type:'mc', options });
      }
      if (t === 'tf'){
        const val = document.querySelector('input[name="tf"]:checked')?.value === 'true';
        return JSON.stringify({ type:'tf', value: val });
      }
      if (t === 'cloze'){
        const html = (editorQ ? editorQ.getData() : qEl.value || '');
        const ids = (function(){ const arr=[]; const re=/\{\{\s*(\d+)\s*\}\}/g; let m; while((m=re.exec(html))!==null){ const n=parseInt(m[1],10); if(!arr.includes(n)) arr.push(n);} return arr; })();
        const rows = [];
        ids.forEach(id=>{
          const tr = clozeBlanksTbody.querySelector(`tr[data-id="${id}"]`);
          if (!tr) return;
          const answers = tr.querySelector('input[type="text"]').value
            .split(',')
            .map(s=>s.trim())
            .filter(Boolean);
          rows.push({ id, answers });
        });
        return JSON.stringify({ type:'cloze', blanks: rows });
      }
      if (t === 'label'){
        const img = labelImg?.src || '';
        return JSON.stringify({ type:'label', image: img, items: labelItems });
      }
      return '';
    }

    // שמירה / עדכון
    $('#save').addEventListener('click', async ()=>{
      msgEl.textContent = '';
      const category    = catEl.value;
      const subcategory = subEl.value;
      const type        = typeEl.value;
      const question    = (editorQ ? editorQ.getData() : (document.getElementById('q')?.value || '') ).trim();
      const answer      = buildAnswerByType();

      if (!category){ msgEl.textContent = 'יש לבחור קטגוריה קודם'; return; }
      if (!subcategory){ msgEl.textContent = 'יש לבחור תת־קטגוריה קודם'; return; }
      if (!question){ msgEl.textContent = 'יש לכתוב שאלה'; return; }
      if (type === 'free' && !answer){ msgEl.textContent = 'יש לכתוב תשובה לשאלה חופשית'; return; }
      if (type === 'label' && (!answer || answer === '""' || answer === 'null')){ 
        msgEl.textContent = 'יש להוסיף לפחות תיבת תיוג אחת לתמונה'; 
        return; 
      }

      // Debug logging
      console.log('About to send:', { category, subcategory, type, question, answer });

      msgEl.textContent = editMode ? 'מעדכן…' : 'שומר…';

      let res;
      if (editMode && editId) {
        res = await api('<?= url('cards/update') ?>', { id: editId, type, question, answer });
      } else {
        res = await api('<?= url('cards/add') ?>', { category, subcategory, type, question, answer });
      }

      if (!res || !res.ok) { msgEl.textContent = 'שגיאה: ' + ((res && res.error) || 'לא ידוע'); return; }

      // חזרה לתת־קטגוריה (אם יש), אחרת לקטגוריה
      const backTo = subcategory
        ? '<?= url('flashcards') ?>/' + encodeURIComponent(category) + '/' + encodeURIComponent(subcategory)
        : '<?= url('flashcards') ?>/' + encodeURIComponent(category);
      window.location.href = backTo;
    });

    // תצוגה מקדימה
    const previewBtn = $('#preview');
    const previewModal = $('#previewModal');
    const closePreviewBtn = $('#closePreview');
    const previewContent = $('#previewContent');

    previewBtn.addEventListener('click', () => {
      const type = typeEl.value;
      const question = (editorQ ? editorQ.getData() : qEl.value).trim();
      const answer = buildAnswerByType();

      if (!question) {
        msgEl.textContent = 'כתוב שאלה קודם';
        return;
      }

      function escapeHtml(s){
        return (s || '').replace(/[&<>"]|'/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]));
      }
      function renderClozeQuestion(html){
        return (html || '').replace(/\{\{\s*(\d+)\s*\}\}/g, (_m, num) => `<span class="cloze-blank" data-id="${num}"><sup>${num}</sup><input type="text" inputmode="text" autocomplete="off" /></span>`);
      }

      // Build a test-like card structure (static revealed view)
      let qHtml = (type === 'cloze') ? renderClozeQuestion(question) : question;
      let body = `
        <div class="qa-container test-deck">
          <div class="test-card" tabindex="0" aria-live="polite">
            <div class="question-section">
              <div id="prev_qHtml" class="ck-content">${qHtml}</div>
            </div>
            <div id="prev_interactionArea" class="interaction-area">
      `;

      if (type === 'free') {
        // Show the answer directly, revealed
        body += `
              <div class="free-interaction">
                <div class="answer-display ck-content">${answer || ''}</div>
              </div>
        `;
      } else if (type === 'mc') {
        // Render options and mark the correct one(s)
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
        // Highlight the correct choice in green
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
        // Build full revealed answer by replacing tokens with the first correct answer
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
        // Preview for label type - show image with all answers revealed
        let labelData = {};
        try { labelData = JSON.parse(answer||'{}'); } catch(e) { labelData = {}; }
        
        const imageUrl = labelData.image || '';
        const items = labelData.items || [];
        
        if (!imageUrl) {
          body += `<div><em>יש להוסיף תמונה כדי לראות תצוגה מקדימה</em></div>`;
        } else {
          body += `
              <div class="label-interaction">
                <div style="position:relative; display:inline-block; max-width:100%;">
                  <img src="${imageUrl}" alt="תמונה לתיוג" style="max-width:100%; height:auto; display:block;">
                  <div class="label-preview-overlay" style="position:absolute; inset:0; pointer-events:none;">
          `;
          
          // Add all the revealed answers (simplified version)
          items.forEach(item => {
            const answers = (item.answers || []).join(' / ') || 'תיבה ' + item.id;
            const boxLeft = Math.round(item.box.x * 100);
            const boxTop = Math.round(item.box.y * 100);
            const boxWidth = Math.round(item.box.w * 100);
            const boxHeight = Math.round(item.box.h * 100);
            const anchorLeft = Math.round(item.anchor.x * 100);
            const anchorTop = Math.round(item.anchor.y * 100);
            
            body += '<div style="position:absolute; left:' + boxLeft + '%; top:' + boxTop + '%; width:' + boxWidth + '%; height:' + boxHeight + '%; background:rgba(40,167,69,0.9); color:white; border-radius:8px; padding:8px; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:600; text-align:center;">' + escapeHtml(answers) + '</div>';
            body += '<div style="position:absolute; left:' + anchorLeft + '%; top:' + anchorTop + '%; width:8px; height:8px; background:#28a745; border:2px solid white; border-radius:50%; transform:translate(-50%,-50%);"></div>';
          });
          
          body += `
                  </div>
                </div>
                <div class="result-display result-correct" style="margin-top:12px;">
                  תצוגת כל התשובות הנכונות
                </div>
              </div>
          `;
        }
      }

      body += `
            </div>
          </div>
        </div>
      `;

      previewContent.innerHTML = body;
      previewModal.style.display = 'flex';
      previewModal.style.alignItems = 'center';
      previewModal.style.justifyContent = 'center';
    });

    closePreviewBtn.addEventListener('click', () => {
      previewModal.style.display = 'none';
    });

    // סגירת המודל בלחיצה על הרקע
    previewModal.addEventListener('click', (e) => {
      if (e.target === previewModal) {
        previewModal.style.display = 'none';
      }
    });

    // סגירת המודל בEscape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && previewModal.style.display !== 'none') {
        previewModal.style.display = 'none';
      }
    });
  </script>
</body>
</html>