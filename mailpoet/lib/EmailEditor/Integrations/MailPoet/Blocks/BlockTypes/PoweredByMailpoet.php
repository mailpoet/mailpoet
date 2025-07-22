<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypes;

use MailPoet\Util\CdnAssetUrl;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

class PoweredByMailpoet extends AbstractBlock {
  private SubscribersFeature $subscribersFeature;
  private CdnAssetUrl $cdnAssetUrl;
  protected $blockName = 'powered-by-mailpoet';

  public function __construct(
    SubscribersFeature $subscribersFeature,
    CdnAssetUrl $cdnAssetUrl
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->subscribersFeature = $subscribersFeature;
  }

  public function render($attributes, $content, $block) {
    if ($this->subscribersFeature->hasValidPremiumKey()) {
      return '';
    }

    $logo = $attributes['logo'] ?? 'default';
    $logoUrl = $this->cdnAssetUrl->generateCdnUrl('email-editor/logo-' . $logo . '.png');

    return $this->addSpacer(sprintf(
      '<div class="%1$s" style="text-align:center">%2$s</div>',
      esc_attr('wp-block-' . $this->blockName),
      '<img src="' . esc_attr($logoUrl) . '" alt="Powered by MailPoet" width="100px" />'
    ), $block->parsed_block['email_attrs'] ?? []); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }
}
