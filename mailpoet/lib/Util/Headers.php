<?php declare(strict_types = 1);

namespace MailPoet\Util;

use MailPoet\WP\Functions as WPFunctions;

class Headers {
  public static function setNoCacheHeaders(): void {
    $wp = WPFunctions::get();
    if ($wp->headersSent()) {
      return;
    }

    // Set default no-cache headers:
    header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1+
    header('Pragma: no-cache'); // HTTP 1.0
    header('Expires: 0'); // proxies
    header('X-Cache-Enabled: False'); // SG Optimizer on SiteGround
    header('X-LiteSpeed-Cache-Control: no-cache'); // LiteSpeed server

    // Use WP-native nocache_headers(). This can override the defaults above.
    $wp->nocacheHeaders();
  }
}
