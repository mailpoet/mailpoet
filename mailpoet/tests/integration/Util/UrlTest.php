<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use MailPoet\Util\Url;
use MailPoet\WP\Functions as WPFunctions;

class UrlTest extends \MailPoetTest {
  public function testGetCurrentUrlKeepsQueryStringWithoutDeprecations() {
    $originalUri = $_SERVER['REQUEST_URI'] ?? null;
    $wp = new WPFunctions();
    $homeUrl = parse_url($wp->homeUrl());
    $path = is_array($homeUrl) && isset($homeUrl['path']) ? $homeUrl['path'] : '';
    $_SERVER['REQUEST_URI'] = $path . '/some-page/?foo=bar&baz=1';

    $deprecations = [];
    set_error_handler(function ($errno, $errstr) use (&$deprecations) {
      $deprecations[] = $errstr;
      return true;
    }, E_DEPRECATED | E_USER_DEPRECATED);

    try {
      $url = (new Url($wp))->getCurrentUrl();
    } finally {
      restore_error_handler();
      if ($originalUri === null) {
        unset($_SERVER['REQUEST_URI']);
      } else {
        $_SERVER['REQUEST_URI'] = $originalUri;
      }
    }

    verify($deprecations)->equals([]);
    verify($url)->equals($wp->homeUrl('/some-page/?foo=bar&baz=1'));
  }
}
