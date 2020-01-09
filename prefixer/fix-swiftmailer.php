<?php

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// fix Swiftmailer namespaces in string not being correctly prefixed
$iterator = new RecursiveDirectoryIterator(__DIR__ . '/../vendor-prefixed/swiftmailer', RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
foreach ($files as $file) {
  if (substr($file, -3) === 'php') {
    $data = file_get_contents($file);
    $data = preg_replace("/'(Swift_[^']*?::)/", "'MailPoetVendor\\\\\\\\$1", $data);
    $data = preg_replace("/InstanceOf\('(Swift_[^']*?')/", "InstanceOf('MailPoetVendor\\\\\\\\$1", $data);
    $data = preg_replace("/registerAutoload\('(_swift[^']*?')/", "registerAutoload('MailPoetVendor\\\\\\\\$1", $data);
    $data = preg_replace("/'(Swift_[^']*?Listener)/", "'MailPoetVendor\\\\\\\\$1", $data);
    $data = str_replace("'Swift_CharacterReader_", "'MailPoetVendor\\\\Swift_CharacterReader_", $data);
    $data = str_replace('SWIFT_INIT_LOADED', 'MAILPOET_SWIFT_INIT_LOADED', $data);

    // require-once 'swift_init.php' in classes since prefixed version won't use Swift's autoloader
    if (strpos($file, 'classes') !== false) {
      $path = substr($file, strpos($file, 'classes'));
      $nestingLevel = substr_count(str_replace('\\', '/', $path), '/');
      $search = 'namespace MailPoetVendor;';
      $requirePath = str_repeat('/..', $nestingLevel) . '/swift_init.php';
      $data = str_replace($search, "$search\n\nrequire_once __DIR__ . '$require_path';", $data);
    }
    file_put_contents($file, $data);
  }
}

# remove unused PHP file that starts with shebang line instead of <?php and causes PHP lint on WP repo to fail
exec('rm -f ' . __DIR__ . '/../vendor-prefixed/swiftmailer/swiftmailer/lib/swiftmailer_generate_mimes_config.php');
