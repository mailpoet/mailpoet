<?php

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

$clientFilePath = __DIR__ . '/../vendor/guzzlehttp/guzzle/src/Client.php';

// Skip for production build
if (!file_exists($clientFilePath) || version_compare(phpversion(), '8.0.0') == -1) {
  exit;
}
$replacement = '
// Updated by MailPoet
function http_build_query($data, string $numeric_prefix = "", ?string $arg_separator = "&", int $encoding_type = PHP_QUERY_RFC1738) {
  $prefix = empty($numeric_prefix) ? "" : $numeric_prefix; 
  return \http_build_query($data, $numeric_prefix, $arg_separator, $encoding_type);
}

';

$replacement .= 'class Client implements ClientInterface';

$data = file_get_contents($clientFilePath);

if (strpos($data, 'Updated by MailPoet') === false) {
  $data = str_replace('class Client implements ClientInterface', $replacement, $data);
  file_put_contents($clientFilePath, $data);
}
