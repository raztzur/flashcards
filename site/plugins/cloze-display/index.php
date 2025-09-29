<?php

use Kirby\Cms\App;

// רישום פילטר מותאם אישית עבור כותרות Cloze
App::plugin('flashcards/cloze-filter', [
  'fieldMethods' => [
    'clozeDisplay' => function ($field) {
      $text = $field->value();
      return preg_replace('/\{\{\s*\d+\s*\}\}/', '____', $text);
    }
  ],
  'pageMethods' => [
    'displayTitle' => function ($page) {
      if ($page->type()->value() === 'cloze') {
        return $page->title()->clozeDisplay();
      }
      return $page->title()->value();
    }
  ],
  'hooks' => [
    // עדכון הכותרת בפאנל עבור שאלות Cloze
    'panel.page.create:after' => function ($page) {
      if ($page->type()->value() === 'cloze') {
        return $page;
      }
    }
  ]
]);