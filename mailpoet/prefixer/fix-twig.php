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

replaceInFile($twigSrc . 'Node/ModuleNode.php', ['"use Twig\\\\Environment;'], '"use MailPoetVendor\\\\Twig\\\\Environment;');
replaceInFile($twigSrc . 'Node/ModuleNode.php', ['"use Twig\\\\Markup;'], '"use MailPoetVendor\\\\Twig\\\\Markup;');
replaceInFile($twigSrc . 'Node/ModuleNode.php', ['"use Twig\\\\Source;'], '"use MailPoetVendor\\\\Twig\\\\Source;');
replaceInFile($twigSrc . 'Node/ModuleNode.php', ['"use Twig\\\\Template;'], '"use MailPoetVendor\\\\Twig\\\\Template;');
replaceInFile($twigSrc . 'Node/ModuleNode.php', ['"use Twig\\\\Error\\\\LoaderError;'], '"use MailPoetVendor\\\\Twig\\\\Error\\\\LoaderError;');
replaceInFile($twigSrc . 'Node/ModuleNode.php', ['"use Twig\\\\Error\\\\RuntimeError;'], '"use MailPoetVendor\\\\Twig\\\\Error\\\\RuntimeError;');
replaceInFile($twigSrc . 'Node/ModuleNode.php', ['"use Twig\\\\Sandbox\\\\SecurityError;'], '"use MailPoetVendor\\\\Twig\\\\Sandbox\\\\SecurityError;');
replaceInFile($twigSrc . 'Node/ModuleNode.php', ['"use Twig\\\\Sandbox\\\\SecurityNotAllowedTagError;'], '"use MailPoetVendor\\\\Twig\\\\Sandbox\\\\SecurityNotAllowedTagError;');
replaceInFile($twigSrc . 'Node/ModuleNode.php', ['"use Twig\\\\Sandbox\\\\SecurityNotAllowedFilterError;'], '"use MailPoetVendor\\\\Twig\\\\Sandbox\\\\SecurityNotAllowedFilterError;');
replaceInFile($twigSrc . 'Node/ModuleNode.php', ['"use Twig\\\\Sandbox\\\\SecurityNotAllowedFunctionError;'], '"use MailPoetVendor\\\\Twig\\\\Sandbox\\\\SecurityNotAllowedFunctionError;');
replaceInFile($twigSrc . 'Node/ModuleNode.php', ['"use Twig\\\\Extension\\\\SandboxExtension;'], '"use MailPoetVendor\\\\Twig\\\\Extension\\\\SandboxExtension;');
replaceInFile($twigSrc . 'Node/ModuleNode.php', ['"use Twig\\\\Extension\\\\CoreExtension;'], '"use MailPoetVendor\\\\Twig\\\\Extension\\\\CoreExtension;');

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
