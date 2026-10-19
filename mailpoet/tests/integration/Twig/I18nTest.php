<?php declare(strict_types = 1);

namespace MailPoet\Test\Twig;

use Codeception\Util\Stub;
use MailPoet\Twig\I18n;
use MailPoet\WP\Functions as WPFunctions;

class I18nTest extends \MailPoetTest {
  public function testLocalizeEmitsTranslationsAsValidJavaScriptStrings() {
    $translations = [
      'path' => 'Save to C:\temp\\',
      'markup' => 'See </script><b>docs</b>',
      'quotes' => 'Use "double", \'single\' and ’curly’ quotes',
      'multiline' => "First line\nSecond line",
    ];

    $handle = null;
    $script = null;
    WPFunctions::set(Stub::make(new WPFunctions, [
      'wpAddInlineScript' => function ($inlineHandle, $data) use (&$handle, &$script) {
        $handle = $inlineHandle;
        $script = $data;
        return true;
      },
    ]));

    (new I18n('mailpoet'))->localize($translations);

    verify($handle)->equals('mailpoet_mailpoet');
    verify($script)->isString();
    verify($script)->stringNotContainsString('<');

    $prefix = 'MailPoet.I18n.add(';
    $suffix = ');';
    $emitted = [];
    foreach (explode("\n", (string)$script) as $line) {
      verify(substr($line, 0, strlen($prefix)))->equals($prefix);
      verify(substr($line, -strlen($suffix)))->equals($suffix);
      // Both arguments are JSON values, so wrapping them in brackets gives a JSON array
      $emitted[] = json_decode('[' . substr($line, strlen($prefix), -strlen($suffix)) . ']', true);
    }
    $expected = array_map(null, array_keys($translations), array_values($translations));
    verify($emitted)->equals($expected);
  }
}
