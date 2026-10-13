<?php declare(strict_types = 1);

use MailPoet\Tasks\StripWhitespaces;

require_once __DIR__ . '/StripWhitespaces.php';

// Strip comments and whitespaces from php files in folder (needs PHP 8+)

// absolutize path (if not absolute)
$directory = rtrim($argv[1], '/');
if (mb_substr($directory, 0, 1) !== DIRECTORY_SEPARATOR) {
  $directory = getcwd() . DIRECTORY_SEPARATOR . $directory;
}

(new StripWhitespaces())->stripDirectory($directory);
