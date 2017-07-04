#!/usr/local/bin/php
<?php
$path = "/tmp/fake-mailer";
if(!file_exists($path)) {
  mkdir($path);
}
$filename = $path . '/mailpoet-' . microtime(true) . '.txt';
$file_handle = fopen($filename, "w");

$call_arguments = print_r($argv, true) . "\n";
fwrite($file_handle, $call_arguments);

while($line = fgets(STDIN)) {
  fwrite($file_handle, $line);
}

fclose($file_handle);
