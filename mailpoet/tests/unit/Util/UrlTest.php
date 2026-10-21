<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use Codeception\Stub;
use MailPoet\Util\Url;
use MailPoet\WP\Functions as WPFunctions;

class UrlTest extends \MailPoetUnitTest {
  public function testCurrentUrlReturnsHomeUrlOnHome() {
    $homeUrl = 'http://example.com';
    $urlHelper = new Url(Stub::make(new WPFunctions(), [
      'homeUrl' => $homeUrl,
      'addQueryArg' => '',
      'wpParseUrl' => function ($url, $component = -1) {
        return parse_url($url, $component);
      },
    ]));
    $currentUrl = $urlHelper->getCurrentUrl();
    verify($currentUrl)->equals($homeUrl);
  }
}
