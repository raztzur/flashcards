<?php
use Kirby\Filesystem\F;
/** טוען CSS גלובלי אחד לכל האתר עם גרסת קובץ (cache-busting) */
$ver = function (string $path): string {
  return url($path) . '?v=' . F::modified(kirby()->root('index') . '/' . $path);
};
?>
<link rel="stylesheet" href="<?= $ver('assets/flashcards/style.css') ?>">
<link rel="manifest" href="<?= url('assets/pwa/manifest.json') ?>">
<meta name="theme-color" content="#ffffff">
<?= snippet('icons-sprite') ?>