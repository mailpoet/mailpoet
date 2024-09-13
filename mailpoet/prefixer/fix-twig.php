<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing
// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

$replacements = [
  [
    'file' => '../vendor-prefixed/twig/twig/src/Token.php',
    'find' => [
      '\'Twig\\\\Token::\'',
    ],
    'replace' => [
      '\'MailPoetVendor\\\\Twig\\\\Token::\'',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Profiler/Node/EnterProfileNode.php',
    'find' => [
      '\\\\Twig\\\\Profiler\\\\Profile',
    ],
    'replace' => [
      '\\\\MailPoetVendor\\\\Twig\\\\Profiler\\\\Profile',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/ModuleNode.php',
    'find' => [
      '"use Twig\\\\Environment;',
      '"use Twig\\\\Markup;',
      '"use Twig\\\\Source;',
      '"use Twig\\\\Template;',
      '"use Twig\\\\Error\\\\LoaderError;',
      '"use Twig\\\\Error\\\\RuntimeError;',
      '"use Twig\\\\Sandbox\\\\SecurityError;',
      '"use Twig\\\\Sandbox\\\\SecurityNotAllowedTagError;',
      '"use Twig\\\\Sandbox\\\\SecurityNotAllowedFilterError;',
      '"use Twig\\\\Sandbox\\\\SecurityNotAllowedFunctionError;',
      '"use Twig\\\\Extension\\\\SandboxExtension;',
      '"use Twig\\\\Extension\\\\CoreExtension;',
    ],
    'replace' => [
      '"use MailPoetVendor\\\\Twig\\\\Environment;',
      '"use MailPoetVendor\\\\Twig\\\\Markup;',
      '"use MailPoetVendor\\\\Twig\\\\Source;',
      '"use MailPoetVendor\\\\Twig\\\\Template;',
      '"use MailPoetVendor\\\\Twig\\\\Error\\\\LoaderError;',
      '"use MailPoetVendor\\\\Twig\\\\Error\\\\RuntimeError;',
      '"use MailPoetVendor\\\\Twig\\\\Sandbox\\\\SecurityError;',
      '"use MailPoetVendor\\\\Twig\\\\Sandbox\\\\SecurityNotAllowedTagError;',
      '"use MailPoetVendor\\\\Twig\\\\Sandbox\\\\SecurityNotAllowedFilterError;',
      '"use MailPoetVendor\\\\Twig\\\\Sandbox\\\\SecurityNotAllowedFunctionError;',
      '"use MailPoetVendor\\\\Twig\\\\Extension\\\\SandboxExtension;',
      '"use MailPoetVendor\\\\Twig\\\\Extension\\\\CoreExtension;',
    ],
  ],
  [
    'file' => '../vendor-prefixed/twig/twig/src/Node/CaptureNode.php',
    'find' => [
      '\'\\\\Twig\\\\Extension\\\\CoreExtension',
    ],
    'replace' => [
      '\'\\\\MailPoetVendor\\\\Twig\\\\Extension\\\\CoreExtension',
    ],
  ],
];

function replaceInFile($file, $find, $replace) {
  $data = file_get_contents($file);
  $data = str_replace($find, $replace, $data);
  file_put_contents($file, $data);
}

foreach ($replacements as $singleFile) {
  replaceInFile($singleFile['file'], $singleFile['find'], $singleFile['replace']);
}

// Remove unwanted class aliases in lib/Twig
exec("rm -rf ../vendor-prefixed/twig/twig/lib/Twig");
exec("rm ../vendor-prefixed/twig/twig/README.rst");
exec("rm -rf ../vendor-prefixed/twig/twig/src/Test");

// Restore prefixed attributes in Twig PHP files
$it = new RecursiveDirectoryIterator('../vendor-prefixed/twig/twig/src/', RecursiveDirectoryIterator::SKIP_DOTS);
foreach (new RecursiveIteratorIterator($it) as $file) {
  if (substr($file, -3) !== 'php') {
    continue;
  }
  replaceInFile($file, '#[\Twig\Attribute\YieldReady]', '#[YieldReady]');
}
