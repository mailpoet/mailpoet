<?php

// Strip comments and whitespaces from php files in folder

// absolutize path (if not absolute)
$directory = rtrim($argv[1] . '/');
if (mb_substr($directory, 0, 1) !== DIRECTORY_SEPARATOR) {
  $directory = getcwd() . DIRECTORY_SEPARATOR . $directory;
}

$iterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
foreach ($files as $file) {
  if (substr($file, -3) !== 'php') {
    continue;
  }
  file_put_contents($file, php_strip_whitespace($file));
}
