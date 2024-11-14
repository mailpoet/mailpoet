<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns;

use MailPoet\EmailEditor\Engine\Patterns\Abstract_Pattern;
use MailPoet\Util\CdnAssetUrl;

abstract class Pattern extends Abstract_Pattern {
  protected CdnAssetUrl $cdnAssetUrl;
  protected $namespace = 'mailpoet';

  public function __construct(
    CdnAssetUrl $cdnAssetUrl
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
  }
}
