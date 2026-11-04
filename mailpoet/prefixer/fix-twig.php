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

// Twig 3.26.0 makes the next two changes so a quote in a name can't end a
// string in the compiled PHP. Remove them once we update Twig; 3.26 needs PHP 8.
$traitBlockError = <<<'PHP'
->write("throw new RuntimeError('Block ")->string($key)->raw(' is not defined in trait ')->subcompile($trait->getNode('template'))->raw(".', ")
PHP;
$traitBlockErrorFixed = <<<'PHP'
->write("throw new RuntimeError(sprintf('Block \"%s\" is not defined in trait \"%s\".', ")->string($key)->raw(', ')->subcompile($trait->getNode('template'))->raw('), ')
PHP;
replaceInFile($twigSrc . 'Node/ModuleNode.php', [$traitBlockError], $traitBlockErrorFixed);

// php-scoper 0.17 writes "\addcslashes(" and 0.18 "addcslashes(", so don't
// match the leading backslash.
$stringEscaping = <<<'PHP'
addcslashes($value, "\x00\t\"\$\\")
PHP;
$stringEscapingFixed = <<<'PHP'
str_replace("'", '\\x27', addcslashes($value, "\x00\t\"\$\\"))
PHP;
replaceInFile($twigSrc . 'Compiler.php', [$stringEscaping], $stringEscapingFixed);

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
