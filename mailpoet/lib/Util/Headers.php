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

  /**
   * Marks the current response as non-cacheable at both the HTTP layer and the
   * full-page-cache-plugin layer.
   *
   * Use this for responses that embed per-visitor personal data (e.g. a
   * subscriber's email and link token) into the page body. The HTTP no-cache
   * headers only take effect when they are sent before output starts, which is
   * not guaranteed when the markup is produced mid-`the_content` (blocks and
   * shortcodes). Defining DONOTCACHEPAGE is honoured by page-cache plugins
   * (Batcache/WP.com VIP, WP Super Cache, W3TC, WP Rocket, LiteSpeed) at their
   * own shutdown stage, so it still prevents caching after output has started.
   *
   * Unlike ThirdPartyOutput::preventHtmlRewriting(), this does not discard
   * output buffers, so it is safe to call while rendering inside a host page.
   */
  public static function preventPageCaching(): void {
    self::setNoCacheHeaders();
    if (!defined('DONOTCACHEPAGE')) {
      define('DONOTCACHEPAGE', true);
    }
  }
}
