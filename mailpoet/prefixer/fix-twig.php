<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing
// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once __DIR__ . '/replace-in-file.php';

$twigSrc = __DIR__ . '/../vendor-prefixed/twig/twig/src/';

replaceInFile(
  $twigSrc . 'Token.php',
  ['\'Twig\\\\Token::\'', '\'Twig\\Token::\''],
  ['\'MailPoetVendor\\\\Twig\\\\Token::\'', '\'MailPoetVendor\\Twig\\Token::\'']
);
replaceInFile(
  $twigSrc . 'Profiler/Node/EnterProfileNode.php',
  ['\\\\Twig\\\\Profiler\\\\Profile', '\\Twig\\Profiler\\Profile'],
  ['\\\\MailPoetVendor\\\\Twig\\\\Profiler\\\\Profile', '\\MailPoetVendor\\Twig\\Profiler\\Profile']
);
replaceInFile(
  $twigSrc . 'Node/CaptureNode.php',
  ['\'\\\\Twig\\\\Extension\\\\CoreExtension', '\'\\Twig\\Extension\\CoreExtension'],
  ['\'\\\\MailPoetVendor\\\\Twig\\\\Extension\\\\CoreExtension', '\'\\MailPoetVendor\\Twig\\Extension\\CoreExtension']
);

$moduleNodeClasses = [
  'Environment',
  'Markup',
  'Source',
  'Template',
  'Error\\\\LoaderError',
  'Error\\\\RuntimeError',
  'Sandbox\\\\SecurityError',
  'Sandbox\\\\SecurityNotAllowedTagError',
  'Sandbox\\\\SecurityNotAllowedFilterError',
  'Sandbox\\\\SecurityNotAllowedFunctionError',
  'Extension\\\\SandboxExtension',
  'Extension\\\\CoreExtension',
];
foreach ($moduleNodeClasses as $class) {
  replaceInFile($twigSrc . 'Node/ModuleNode.php', ["\"use Twig\\\\$class;"], "\"use MailPoetVendor\\\\Twig\\\\$class;");
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
  file_put_contents($file, str_replace('#[\Twig\Attribute\YieldReady]', '#[YieldReady]', file_get_contents($file)));
}
