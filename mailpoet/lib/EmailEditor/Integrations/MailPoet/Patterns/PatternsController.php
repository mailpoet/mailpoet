<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns;

use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\DefaultContent;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\DefaultContentFull;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library\OneColumn;
use MailPoet\Util\CdnAssetUrl;

class PatternsController {
  private CdnAssetUrl $cdnAssetUrl;

  public function __construct(
    CdnAssetUrl $cdnAssetUrl
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
  }

  public function registerPatterns(): void {
    $patterns = [];
    $patterns[] = new DefaultContentFull($this->cdnAssetUrl);
    $patterns[] = new DefaultContent($this->cdnAssetUrl);
    $patterns[] = new OneColumn($this->cdnAssetUrl);
    foreach ($patterns as $pattern) {
      register_block_pattern($pattern->get_namespace() . '/' . $pattern->get_name(), $pattern->get_properties());
    }
  }
}
