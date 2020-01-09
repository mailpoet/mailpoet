#!/usr/local/bin/php
<?php
$path = "/tmp/fake-mailer";
if (!file_exists($path)) {
  mkdir($path);
}
$filename = $path . '/mailpoet-' . microtime(true) . '.txt';
$fileHandle = fopen($filename, "w");

// phpcs:ignore Squiz.PHP.DiscouragedFunctions
$callArguments = print_r($argv, true) . "\n";
fwrite($fileHandle, $callArguments);

while ($line = fgets(STDIN)) {
  fwrite($fileHandle, $line);
}

fclose($fileHandle);
