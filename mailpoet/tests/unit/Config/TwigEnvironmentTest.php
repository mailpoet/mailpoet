<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoetVendor\Twig\Compiler;
use MailPoetVendor\Twig\Error\RuntimeError;
use MailPoetVendor\Twig\Loader\ArrayLoader;

class TwigEnvironmentTest extends \MailPoetUnitTest {
  public function testItCompilesTraitNamesWithQuotes(): void {
    $twig = new TwigEnvironment(new ArrayLoader([
      "it's.html" => '{% block existing %}{% endblock %}',
      'main.html' => '{% use "it\'s.html" with missing as alias %}',
    ]));

    $this->expectException(RuntimeError::class);
    $this->expectExceptionMessage('Block "missing" is not defined in trait "it\'s.html"');
    $twig->load('main.html');
  }

  public function testItEncodesSingleQuotesInCompiledStrings(): void {
    $compiler = new Compiler(new TwigEnvironment(new ArrayLoader()));
    verify($compiler->string("it's")->getSource())->equals('"it\x27s"');
  }
}
