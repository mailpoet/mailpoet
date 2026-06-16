<?php declare(strict_types = 1);

namespace MailPoet\Util;

class ThirdPartyOutput {
  /**
   * Public email pages (view-in-browser, public share) emit bare, fully-rendered
   * email HTML and exit early. They include no wp_head()/wp_footer(), so no
   * front-end JS ever loads. Image-optimizer / lazy-load plugins that buffer the
   * whole page output and rewrite <img src> into JS-driven <img data-src>
   * placeholders therefore break every image: the placeholder is never swapped
   * back in because their loader script is absent.
   *
   * Signal those plugins to skip this response, and discard any output buffers
   * they already opened so their flush callbacks never process our HTML.
   */
  public static function preventHtmlRewriting(): void {
    $bypassConstants = [
      'DONOTCACHEPAGE',
      'DONOTMINIFY',
      'DONOTLAZYLOAD',
      'DONOTROCKETOPTIMIZE',
    ];
    foreach ($bypassConstants as $constant) {
      if (!defined($constant)) {
        define($constant, true);
      }
    }

    // Discard foreign output buffers. Stop if a buffer cannot be removed (e.g.
    // zlib.output_compression) to avoid an infinite loop.
    while (ob_get_level() > 0) {
      if (!ob_end_clean()) {
        break;
      }
    }
  }
}
