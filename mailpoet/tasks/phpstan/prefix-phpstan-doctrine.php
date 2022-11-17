<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

$iterator = new RecursiveDirectoryIterator(__DIR__ . '/vendor/phpstan/phpstan-doctrine', RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
foreach ($files as $file) {
  if (substr($file, -4) === '.php' || substr($file, -5) === '.stub') {
    $data = file_get_contents($file);

    // when string 'Doctrine' is prefixed by a whitespace, ', ", or ( plus zero or more \, and suffixed by
    // one or more \, prefix it with 'MailPoetDoctrine' + the number of trailing \ in the original string
    $data = preg_replace('/([\'"\s(?]\\\\*)(Doctrine)(\\\\+)/', '$1MailPoetVendor$3$2$3', $data);
    file_put_contents($file, $data);
  }
}
