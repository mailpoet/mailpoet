<?php

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});


// remove all attributes.
// Until we support Php 7.x we cannot use them. PHP 7.x treats everything that starts with # as a comment.
// Our script build.sh removes whitespaces and form the first attribute to the end of the file everything is a comment and that leads to invalid php code
$iterator = new RecursiveDirectoryIterator(__DIR__ . '/./vendor', RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
foreach ($files as $file) {
  if (substr($file, -3) === 'php') {
    $data = file_get_contents($file);
    $data = preg_replace('!^\s*#\[[A-Za-z\]+[A-Za-z\-_| \\\)(:]+]$!m', '', $data);
    file_put_contents($file, $data);
  }
}
