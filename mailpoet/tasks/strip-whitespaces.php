<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// Strip comments and whitespaces from php files in folder

// absolutize path (if not absolute)
$directory = rtrim($argv[1] . '/');
if (mb_substr($directory, 0, 1) !== DIRECTORY_SEPARATOR) {
  $directory = getcwd() . DIRECTORY_SEPARATOR . $directory;
}

$iterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
foreach ($files as $file) {
  if (!is_file($file) || substr($file, -3) !== 'php') {
    continue;
  }
  $data = file_get_contents($file);
  $output = preg_replace('/^\s*\/\*(\*)?(((?!\*\/)[\s\S])+)?\*\//m', '', $data); // remove multiline comments
  $data = $output !== null ? $output : $data;
  $data = preg_replace("/[\r\n]+/", "\n", $data); // remove redundant new lines
  $data = preg_replace("/[[:blank:]]+/s", " ", $data); // remove redundant whitespaces
  file_put_contents($file, $data);
}
