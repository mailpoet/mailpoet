<?php declare(strict_types = 1);

namespace MailPoet\Test\Twig;

use Codeception\Util\Stub;
use MailPoet\Twig\Functions;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Twig\Environment;

class FunctionsTest extends \MailPoetTest {
  public function testItExecutesIsRtlFunction() {
    $template = ['template' => '{% if is_rtl() %}rtl{% endif %}'];
    $twig = new Environment(new \MailPoetVendor\Twig\Loader\ArrayLoader($template));
    WPFunctions::set(Stub::make(new WPFunctions, [
      'isRtl' => Stub::consecutive(true, false),
    ]));

    $twig->addExtension(new Functions());
    $resultRtl = $twig->render('template');
    verify($resultRtl)->equals('rtl');
    $resultNoRtl = $twig->render('template');
    verify($resultNoRtl)->empty();
  }

  public function testItEscapesScriptTagsInJsonEncode() {
    // Test that json_encode escapes </script> tags to prevent breaking out of script context
    $template = ['template' => '{{ json_encode(data) }}'];
    $twig = new Environment(new \MailPoetVendor\Twig\Loader\ArrayLoader($template));
    $twig->addExtension(new Functions());

    // Test data containing </script> which should be escaped
    $dataWithScriptTag = ['content' => 'Hello </script><script>alert("XSS")</script>'];
    $result = $twig->render('template', ['data' => $dataWithScriptTag]);

    // Verify that < and > are encoded (JSON_HEX_TAG converts them to \u003C and \u003E)
    verify($result)->stringContainsString('\u003C');
    verify($result)->stringContainsString('\u003E');
    // The dangerous </script> should not appear unescaped in the JSON value
    verify($result)->stringNotContainsString('"content":"Hello </script>');

    // Also verify JSON_UNESCAPED_SLASHES is working (forward slashes should not be escaped)
    $dataWithUrl = ['url' => 'https://example.com/path'];
    $result = $twig->render('template', ['data' => $dataWithUrl]);
    verify($result)->stringContainsString('https://example.com/path');
    verify($result)->stringNotContainsString('https:\\/\\/example.com\\/path');
  }
}
