<?php
use Kirby\Toolkit\Str;
use Kirby\Toolkit\F;
use Kirby\Cms\Page;
use Kirby\Http\Response;

function json_ok(array $arr = [], int $code = 200): Response {
  return Response::json(array_merge(['ok' => true], $arr), $code);
}
function json_err(string $msg, int $code = 400): Response {
  return Response::json(['ok' => false, 'error' => $msg], $code);
}

// פונקציה להמרת כותרות Cloze לתצוגה עם קווים תחתונים
function cloze_title_display(string $text): string {
  return preg_replace('/\{\{\s*\d+\s*\}\}/', '____', $text);
}
function safe_body(): array {
  $req  = kirby()->request();
  $raw  = $req->body()->toString();
  $arr  = [];
  if ($raw !== '') {
    $json = json_decode($raw, true);
    if (is_array($json)) $arr = $json;
    else { $tmp=[]; parse_str($raw,$tmp); if (!empty($tmp)) $arr=$tmp; }
  }
  if (empty($arr)) $arr = $req->body()->toArray();
  return is_array($arr) ? $arr : [];
}
function ensure_root_flashcards(): ?\Kirby\Cms\Page {
  $root = page('flashcards');
  if ($root) return $root;
  try {
    $prev = kirby()->user();
    kirby()->impersonate('kirby');
    $page = Page::create([
      'slug'     => 'flashcards',
      'template' => 'flashcards',
      'parent'   => site(),
      'content'  => ['title' => 'Flashcards']
    ]);
    if (method_exists($page,'changeStatus')) $page = $page->changeStatus('listed');
    elseif (method_exists($page,'publish'))  $page = $page->publish();
    return $page;
  } catch (\Throwable $e) {
    return null;
  } finally {
    kirby()->impersonate($prev ? $prev->id() : null);
  }
}
function body_title_with_aliases(array $b): string {
  $candidates = [
    $b['title']    ?? null,
    $b['name']     ?? null,
    $b['category'] ?? null,
  ];
  foreach ($candidates as $v) if ($v !== null && trim((string)$v) !== '') return trim((string)$v);
  return '';
}

/** --------- SM-2 helpers --------- */
function sm2_update(array $row, int $quality): array {
  $e = isset($row['easiness']) ? (float)$row['easiness'] : 2.5;
  $i = isset($row['interval']) ? (int)$row['interval'] : 0;
  $r = isset($row['repetitions']) ? (int)$row['repetitions'] : 0;
  if ($quality < 0) $quality = 0;
  if ($quality > 5) $quality = 5;

  if ($quality >= 3) {
    if ($r == 0)      $i = 1;
    elseif ($r == 1)  $i = 6;
    else              $i = (int)round($i * $e);
    $r = $r + 1;
    $e = $e + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));
    if ($e < 1.3) $e = 1.3;
  } else {
    $r = 0;
    $i = 1;
  }
  $dueAt = date('c', time() + $i * 86400);

  $box = 3;
  if ($quality >= 5) $box = 5;
  elseif ($quality >= 3) $box = 4;
  elseif ($quality == 2) $box = 2;
  else $box = 1;

  return [
    'easiness'=>$e, 'interval'=>$i, 'repetitions'=>$r,
    'dueAt'=>$dueAt, 'box'=>$box,
  ];
}
function progress_file(): string {
  $storage = kirby()->root('content').'/.flashcards';
  if(!is_dir($storage)) @mkdir($storage,0775,true);
  return $storage.'/progress.json';
}
function progress_read(): array {
  $file = progress_file();
  return file_exists($file) ? (json_decode(F::read($file), true) ?: []) : [];
}
function progress_write(array $progress): void {
  $file = progress_file();
  F::write($file, json_encode($progress, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}
function is_same_day(string $iso): bool {
  $ts = strtotime($iso ?: '');
  if (!$ts) return false;
  return date('Y-m-d', $ts) === date('Y-m-d');
}

return [
  'debug' => true,
  'slugs' => 'unicode',

  'routes' => [

    [ 'pattern'=>'/', 'method'=>'GET', 'action'=>fn() => go('flashcards') ],
    [ 'pattern'=>'ping', 'method'=>'GET', 'action'=>fn() => json_ok(['pong'=>true]) ],

    /* ===== עמוד יצירת קטגוריה (HTML) ===== */
    [
      'pattern'=>'flashcards/category-new', 'method'=>'GET',
      'action'=>function(){
        $html = snippet('category-new', [], true);
        return new \Kirby\Http\Response($html, 'text/html', 200);
      }
    ],

    // ---------- קטגוריות ----------
    [
      'pattern'=>'categories', 'method'=>'GET',
      'action'=>function(){
        $root = ensure_root_flashcards(); if(!$root) return json_err('Missing /flashcards',500);
        $progress = progress_read();
        $today = time();

        $cats = $root->children()->filterBy('intendedTemplate','category');
        $data = $cats->map(function($p) use ($progress, $today){
          // ספירה כולל תתי־קטגוריות
          $cards = $p->index()->filterBy('intendedTemplate','card');
          $count = $cards->count();

          $due = 0;
          foreach ($cards as $c) {
            $row = $progress[$c->id()] ?? null;
            if ($row && !empty($row['dueAt']) && strtotime($row['dueAt']) <= $today) $due++;
          }

          $bg = $p->content()->get('background')->value() ?: ($p->content()->get('gradient')->value() ?: '');
          return [
            'id'=>$p->id(),'slug'=>$p->slug(),'title'=>$p->title()->value(),
            'count'=>$count,'dueToday'=>$due,
            'icon'=>$p->content()->get('icon')->value() ?? '',
            'background'=>$bg, 'gradient'=>$bg,
          ];
        })->values();
        return json_ok(['categories'=>$data,'count'=>count($data)]);
      }
    ],
    [
      'pattern'=>'categories/add', 'method'=>'POST',
      'action'=>function(){
        $root = ensure_root_flashcards(); if(!$root) return json_err('Cannot create /flashcards',500);
        $b  = safe_body();
        $title = body_title_with_aliases($b);
        if ($title==='') return json_err('Missing category title');

        $slug = \Kirby\Toolkit\Str::slug($title) ?: 'cat-'.date('Ymd-His');
        $i=1; $base=$slug; while($root->find($slug)) $slug = $base.'-'.$i++;

        $icon       = trim((string)($b['icon'] ?? ''));
        $color      = trim((string)($b['color'] ?? 'blue'));
        $background = trim((string)($b['background'] ?? ($b['gradient'] ?? '')));

        try{
          $prev = kirby()->user(); kirby()->impersonate('kirby');
          $page = \Kirby\Cms\Page::create([
            'slug'=>$slug,
            'template'=>'category',
            'parent'=>$root,
            'content'=>[
              'title'=>$title,
              'icon'=>$icon,
              'color'=>$color,
              'background'=>$background,
              'gradient'=>$background,
            ]
          ]);
          // פרסום הדף מיד לאחר היצירה
          if (method_exists($page, 'changeStatus')) {
            $page = $page->changeStatus('listed');
          } elseif (method_exists($page, 'publish')) {
            $page = $page->publish();
          }
        } catch(\Throwable $e){
          return json_err('Create failed: '.$e->getMessage(),500);
        } finally { kirby()->impersonate($prev ? $prev->id() : null); }

        return json_ok(['slug'=>$page->slug(),'title'=>$page->title()->value()]);
      }
    ],
    [
      'pattern'=>'categories/update', 'method'=>'POST',
      'action'=>function(){
        $root = ensure_root_flashcards(); if(!$root) return json_err('Missing /flashcards',500);
        $b = safe_body();
        $slug  = trim((string)($b['slug'] ?? ''));
        if ($slug==='') return json_err('Missing slug');

        $cat = $root->find($slug); if(!$cat) return json_err('Category not found',404);
        $payload = [];
        if (isset($b['title']))      $payload['title']      = trim((string)$b['title']);
        if (isset($b['icon']))       $payload['icon']       = trim((string)$b['icon']);
        if (isset($b['color']))      $payload['color']      = trim((string)$b['color']);
        if (isset($b['background'])) $payload['background'] = trim((string)$b['background']);
        if (isset($b['gradient']) && !isset($b['background'])) $payload['background'] = trim((string)$b['gradient']);
        if (isset($payload['background'])) $payload['gradient'] = $payload['background'];

        if (empty($payload)) return json_err('Nothing to update');
        try{
          $prev = kirby()->user(); kirby()->impersonate('kirby');
          $cat->update($payload);
        } catch(\Throwable $e){
          return json_err('Update failed: '.$e->getMessage(),500);
        } finally { kirby()->impersonate($prev ? $prev->id() : null); }
        return json_ok(['slug'=>$slug] + $payload);
      }
    ],
    [
      'pattern'=>'categories/delete', 'method'=>'POST',
      'action'=>function(){
        $root = ensure_root_flashcards(); if(!$root) return json_err('Missing /flashcards',404);
        $b = safe_body();
        $slug = trim((string)($b['slug'] ?? get('slug') ?? '')); if($slug==='') return json_err('Missing slug');
        $cat = $root->find($slug); if(!$cat) return json_err('Category not found',404);
        try{
          $prev = kirby()->user(); kirby()->impersonate('kirby');
          $cat->delete(true);
        } catch(\Throwable $e){ return json_err('Delete failed: '.$e->getMessage(),500);
        } finally { kirby()->impersonate($prev ? $prev->id() : null); }
        return json_ok(['deleted'=>$slug]);
      }
    ],

    // ---------- תת־קטגוריות ----------
    [
      'pattern'=>'subcats', 'method'=>'GET',
      'action'=>function(){
        $root = ensure_root_flashcards(); if(!$root) return json_err('Missing /flashcards',404);
        $catSlug = get('category'); if(!$catSlug) return json_err('Missing category slug');
        $cat = $root->find($catSlug); if(!$cat) return json_err('Category not found',404);

        $subs = $cat->children()->filterBy('intendedTemplate','subcategory');
        $data = $subs->map(function($p){
          $cards = $p->children()->filterBy('intendedTemplate','card');
          return [
            'id'=>$p->id(),'slug'=>$p->slug(),'title'=>$p->title()->value(),
            'count'=>$cards->count(),
          ];
        })->values();
        return json_ok(['category'=>$catSlug,'subcategories'=>$data,'count'=>count($data)]);
      }
    ],
    [
      'pattern'=>'subcats/add', 'method'=>'POST',
      'action'=>function(){
        try {
          $root = ensure_root_flashcards(); if(!$root) return json_err('Missing /flashcards',404);
          $b = safe_body();
          $catSlug = trim((string)($b['category'] ?? '')); if($catSlug==='') return json_err('Missing category slug');
          $cat = $root->find($catSlug); if(!$cat) return json_err('Category not found',404);

          $title = body_title_with_aliases($b); if ($title==='') return json_err('Missing subcategory title');
          $slug = \Kirby\Toolkit\Str::slug($title) ?: 'sub-'.date('Ymd-His');
          $i=1; $base=$slug; while($cat->find($slug)) $slug = $base.'-'.$i++;

          $prev = kirby()->user(); kirby()->impersonate('kirby');
          $page = \Kirby\Cms\Page::create([
            'slug'=>$slug,
            'template'=>'subcategory',
            'parent'=>$cat,
            'content'=>['title'=>$title]
          ]);
          // פרסום הדף מיד לאחר היצירה
          if (method_exists($page, 'changeStatus')) {
            $page = $page->changeStatus('listed');
          } elseif (method_exists($page, 'publish')) {
            $page = $page->publish();
          }
          
          return json_ok(['slug'=>$page->slug(),'title'=>$page->title()->value()]);
        } catch(\Throwable $e){
          error_log('Subcategory creation error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
          return json_err('Create failed: '.$e->getMessage(),500);
        } finally { 
          if (isset($prev)) kirby()->impersonate($prev ? $prev->id() : null); 
        }
      }
    ],
    [
      'pattern'=>'subcats/update', 'method'=>'POST',
      'action'=>function(){
        $root = ensure_root_flashcards(); if(!$root) return json_err('Missing /flashcards',404);
        $b = safe_body();
        $catSlug = trim((string)($b['category'] ?? '')); if($catSlug==='') return json_err('Missing category slug');
        $subSlug = trim((string)($b['slug'] ?? ''));      if($subSlug==='') return json_err('Missing subcategory slug');

        $cat = $root->find($catSlug); if(!$cat) return json_err('Category not found',404);
        $sub = $cat->find($subSlug);  if(!$sub) return json_err('Subcategory not found',404);

        $payload = [];
        if (isset($b['title'])) $payload['title'] = trim((string)$b['title']);
        if (empty($payload)) return json_err('Nothing to update');

        try{
          $prev = kirby()->user(); kirby()->impersonate('kirby');
          $sub->update($payload);
        } catch(\Throwable $e){ return json_err('Update failed: '.$e->getMessage(),500);
        } finally { kirby()->impersonate($prev ? $prev->id() : null); }
        return json_ok(['slug'=>$subSlug] + $payload);
      }
    ],
    [
      'pattern'=>'subcats/delete', 'method'=>'POST',
      'action'=>function(){
        $root = ensure_root_flashcards(); if(!$root) return json_err('Missing /flashcards',404);
        $b = safe_body();
        $catSlug = trim((string)($b['category'] ?? '')); if($catSlug==='') return json_err('Missing category slug');
        $subSlug = trim((string)($b['slug'] ?? ''));      if($subSlug==='') return json_err('Missing subcategory slug');

        $cat = $root->find($catSlug); if(!$cat) return json_err('Category not found',404);
        $sub = $cat->find($subSlug);  if(!$sub) return json_err('Subcategory not found',404);

        try{
          $prev = kirby()->user(); kirby()->impersonate('kirby');
          $sub->delete(true);
        } catch(\Throwable $e){ return json_err('Delete failed: '.$e->getMessage(),500);
        } finally { kirby()->impersonate($prev ? $prev->id() : null); }
        return json_ok(['deleted'=>$subSlug]);
      }
    ],

    // ---------- כרטיסים ----------
    [
      'pattern'=>'cards', 'method'=>'GET',
      'action'=>function(){
        $root = ensure_root_flashcards(); if(!$root) return json_err('Missing /flashcards',404);
        $catSlug = get('category'); 
        $subSlug = get('subcategory') ?? get('sub');

        if ($catSlug){
          $cat = $root->find($catSlug); if(!$cat) return json_err('Category not found',404);
          if ($subSlug){
            $sub = $cat->find($subSlug); if(!$sub) return json_err('Subcategory not found',404);
            $cards = $sub->children()->filterBy('intendedTemplate','card');
          } else {
            // כל הכרטיסים מתחת לכל תתי־הקטגוריות של הקטגוריה
            $cards = $cat->children()->filterBy('intendedTemplate','subcategory')->children()->filterBy('intendedTemplate','card');
          }
        } else {
          // כל הכרטיסים בכל האתר (רק מתוך תתי־קטגוריות)
          $cards = $root->children()->filterBy('intendedTemplate','category')
                    ->children()->filterBy('intendedTemplate','subcategory')
                    ->children()->filterBy('intendedTemplate','card');
        }

        $data = $cards->map(function($p){
          $parent = $p->parent();            // subcategory
          $cat    = $parent?->parent();      // category
          
          // עבור שאלות Cloze אנחנו צריכים את התוכן הגולמי, לא מעובד
          $type = $p->type()->value();
          $question = ($type === 'cloze') 
            ? $p->question()->value()  // גולמי
            : $p->question()->kirbytext()->value();  // מעובד
            
          return [
            'id'         => $p->id(),
            'slug'       => $p->slug(),
            'category'   => $cat?->slug(),
            'subcategory'=> $parent?->slug(),
            'type'       => $type,
            'question'   => $question,
            'answer'     => $p->answer()->value(),
            'stats'      => [
              'box'    => (int)$p->box()->or(3)->value(),
              'seen'   => (int)$p->seen()->or(0)->value(),
              'correct'=> (int)$p->correct()->or(0)->value(),
            ],
          ];
        })->values();
        return json_ok(['count'=>count($data),'cards'=>$data]);
      }
    ],
    [
      'pattern'=>'card', 'method'=>'GET',
      'action'=>function(){
        $id = get('id'); if(!$id) return json_err('Missing id');
        $p = page($id); if(!$p) return json_err('Not found',404);
        $parent = $p->parent();       // subcategory
        $cat    = $parent?->parent(); // category
        return json_ok(['card'=>[
          'id'         => $p->id(),
          'slug'       => $p->slug(),
          'category'   => $cat?->slug(),
          'subcategory'=> $parent?->slug(),
          'type'       => $p->type()->value(),
          'question_raw'=>$p->question()->value(),
          'answer_raw'  =>$p->answer()->value(),
          'stats'=>[
            'box'=>(int)$p->box()->or(3)->value(),
            'seen'=>(int)$p->seen()->or(0)->value(),
            'correct'=>(int)$p->correct()->or(0)->value(),
          ],
        ]]);
      }
    ],
    [
      'pattern'=>'cards/add', 'method'=>'POST',
      'action'=>function(){
        $root = ensure_root_flashcards(); if(!$root) return json_err('Missing /flashcards',404);
        $b = safe_body();

        $catSlug = trim((string)($b['category'] ?? get('category') ?? '')); 
        if($catSlug==='') return json_err('חובה לבחור קטגוריה');
        $cat = $root->find($catSlug); if(!$cat) return json_err('קטגוריה לא נמצאה',404);

        $subSlug = trim((string)($b['subcategory'] ?? $b['sub'] ?? get('subcategory') ?? get('sub') ?? ''));
        if ($subSlug==='') return json_err('חובה לבחור תת־קטגוריה'); 
        $sub = $cat->find($subSlug); if(!$sub) return json_err('תת־קטגוריה לא נמצאה',404);

        $type = $b['type'] ?? 'free';
        $q    = trim((string)($b['question'] ?? ''));
        $a    = (string)($b['answer'] ?? '');
        
        // בדיקות בסיסיות
        if ($q==='') return json_err('חובה לכתוב שאלה');
        
        // בדיקות לפי סוג שאלה
        if ($type==='free' && trim($a)==='') return json_err('חובה לכתוב תשובה לשאלה חופשית');
        if ($type==='label' && ($a==='' || $a==='""' || $a==='null')) return json_err('חובה להוסיף תיבות תיוג לתמונה');
        if ($type==='mc') {
          $answerData = json_decode($a, true);
          if (!$answerData || !isset($answerData['options']) || empty($answerData['options'])) {
            return json_err('חובה להוסיף אפשרויות לשאלה אמריקאית');
          }
        }

        $slugBase = \Kirby\Toolkit\Str::slug(substr(strip_tags($q) ?: 'card', 0, 60)) ?: 'card';
        $slug=$slugBase; $i=1; $base=$slug; while($sub->find($slug)) $slug = $base.'-'.$i++;

        $content = [
          'title'    => $q ? \Kirby\Toolkit\Str::excerpt($q,48) : 'Flashcard',
          'type'     => $type,
          'question' => $q,
          'answer'   => $a,
          'box'      => '3','seen'=>'0','correct'=>'0',
        ];

        try{
          error_log("DEBUG: About to create page with content: " . json_encode($content));
          $prev = kirby()->user(); kirby()->impersonate('kirby');
          
          error_log("DEBUG: Creating page with slug: $slug, template: card, parent: " . $sub->id());
          $page = \Kirby\Cms\Page::create(['slug'=>$slug,'template'=>'card','parent'=>$sub,'content'=>$content]);
          error_log("DEBUG: Page created successfully: " . $page->id());
          
          if (method_exists($page,'changeStatus')) {
            error_log("DEBUG: Using changeStatus method");
            $page = $page->changeStatus('listed');
            error_log("DEBUG: Status changed to listed");
          } elseif (method_exists($page,'publish')) {
            error_log("DEBUG: Using publish method"); 
            $page = $page->publish();
            error_log("DEBUG: Page published");
          }
          
          error_log("DEBUG: Final page status: " . $page->status());
        } catch(\Throwable $e){ 
          error_log("DEBUG: Exception caught: " . $e->getMessage());
          error_log("DEBUG: Exception trace: " . $e->getTraceAsString());
          return json_err('Create failed: '.$e->getMessage(),500);
        } finally { 
          error_log("DEBUG: Finally block - restoring user");
          kirby()->impersonate($prev ? $prev->id() : null); 
        }

        return json_ok(['slug'=>$page->slug(),'id'=>$page->id()]);
      }
    ],
    [
      'pattern'=>'api/card/(:all)', 'method'=>'GET',
      'action'=>function($id){
        $p = page($id); 
        if(!$p) return new Response('{"error":"Flashcard not found"}', 'application/json', 404);
        
        return new Response(json_encode([
          'id' => $p->id(),
          'type' => $p->type()->value() ?: 'free',
          'question' => $p->question()->value(),
          'answer' => $p->answer()->value(),
          'title' => $p->title()->value()
        ]), 'application/json');
      }
    ],
    [
      'pattern'=>'cards/update', 'method'=>'POST',
      'action'=>function(){
        $b = safe_body();
        $id = trim((string)($b['id'] ?? get('id') ?? '')); if($id==='') return json_err('Missing id');
        $p  = page($id); if(!$p) return json_err('Not found',404);

        $type = $b['type'] ?? $p->type()->value();
        $q    = isset($b['question']) ? trim((string)$b['question']) : $p->question()->value();
        $a    = isset($b['answer'])   ? (string)$b['answer'] : $p->answer()->value();
        
        // עדכון הכותרת לפי השאלה החדשה
        $title = $q ? \Kirby\Toolkit\Str::excerpt($q, 48) : 'Flashcard';

        try{
          $prev = kirby()->user(); kirby()->impersonate('kirby');
          $p->update(['type'=>$type,'question'=>$q,'answer'=>$a,'title'=>$title]);
        } catch(\Throwable $e){ return json_err('Update failed: '.$e->getMessage(),500);
        } finally { kirby()->impersonate($prev ? $prev->id() : null); }

        return json_ok(['updated'=>$p->id()]);
      }
    ],
    [
      'pattern'=>'cards/delete', 'method'=>'POST',
      'action'=>function(){
        $b = safe_body();
        $id = trim((string)($b['id'] ?? get('id') ?? '')); if($id==='') return json_err('Missing id');
        $p  = page($id); if(!$p) return json_err('Not found',404);
        try{
          $prev = kirby()->user(); kirby()->impersonate('kirby');
          $p->delete(true);
        } catch(\Throwable $e){ return json_err('Delete failed: '.$e->getMessage(),500);
        } finally { kirby()->impersonate($prev ? $prev->id() : null); }
        return json_ok(['deleted'=>$id]);
      }
    ],

    // ---------- Progress (SM-2) ----------
    [
      'pattern'=>'flashcards/progress','method'=>'GET',
      'action'=>function(){ return json_ok(['progress'=>progress_read()]); }
    ],
    [
      'pattern'=>'flashcards/progress','method'=>'POST',
      'action'=>function(){
        $b = safe_body();
        if (!isset($b['id'])) return json_err('Missing id');
        $id = (string)$b['id'];

        $progress = progress_read();
        $row = $progress[$id] ?? ['seen'=>0,'correct'=>0,'box'=>3,'updatedAt'=>null,'easiness'=>2.5,'interval'=>0,'repetitions'=>0,'dueAt'=>null];

        if (isset($b['quality'])) {
          $quality = (int)$b['quality'];
          $row['seen'] = (int)($row['seen'] ?? 0) + 1;
          if ($quality >= 4) $row['correct'] = (int)($row['correct'] ?? 0) + 1;

          $updated = sm2_update($row, $quality);
          $row['easiness']=$updated['easiness']; $row['interval']=$updated['interval'];
          $row['repetitions']=$updated['repetitions']; $row['dueAt']=$updated['dueAt']; $row['box']=$updated['box'];
        } elseif (isset($b['delta'])) {
          $delta = $b['delta'];
          $row['seen']    = max(0, (int)($row['seen'] ?? 0) + (int)($delta['seen'] ?? 0));
          $row['correct'] = max(0, (int)($row['correct'] ?? 0) + (int)($delta['correct'] ?? 0));
          if (isset($delta['box'])) $row['box'] = (int)$delta['box'];
          if (empty($row['dueAt'])) $row['dueAt'] = date('c', time()+86400);
        } else {
          return json_err('Bad payload');
        }

        $row['updatedAt'] = date('c');
        $progress[$id] = $row;
        progress_write($progress);

        return json_ok(['progress'=>$row]);
      }
    ],
    [
      'pattern'=>'flashcards/progress/put','method'=>'POST',
      'action'=>function(){
        $b = safe_body();
        $id = (string)($b['id'] ?? '');
        $row = $b['row'] ?? null;
        if ($id==='') return json_err('Missing id');
        if (!is_array($row)) return json_err('Missing row');
        $progress = progress_read();
        $progress[$id] = $row;
        progress_write($progress);
        return json_ok(['progress'=>$row]);
      }
    ],

    // ---------- Upload (תמונות) ----------
    [
      'pattern' => 'upload',
      'method'  => 'POST',
      'action'  => function () {
        $root = ensure_root_flashcards();
        if (!$root) return json_err('Missing /flashcards', 500);

        if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
          return json_err('No file', 400);
        }
        $file = $_FILES['image'];
        $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
        if (!in_array(mime_content_type($file['tmp_name']), $allowed, true)) {
          return json_err('Unsupported type', 400);
        }
        if ($file['size'] > 5 * 1024 * 1024) {
          return json_err('File too large', 400);
        }

        $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
        $base = \Kirby\Toolkit\Str::slug(pathinfo($file['name'], PATHINFO_FILENAME)) ?: 'img';
        $name = $base . '-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)),0,6) . '.' . strtolower($ext);

        try {
          $prev = kirby()->user(); kirby()->impersonate('kirby');
          $uploaded = $root->createFile([
            'source'   => $file['tmp_name'],
            'filename' => $name,
          ]);
        } catch (\Throwable $e) {
          return json_err('Upload failed: '.$e->getMessage(), 500);
        } finally {
          kirby()->impersonate($prev ? $prev->id() : null);
        }

        return json_ok([
          'url'  => $uploaded->url(),
          'name' => $uploaded->filename(),
          'size' => $uploaded->size()
        ]);
      }
    ],

  ] // <-- סוף routes
];   // <-- סוף return