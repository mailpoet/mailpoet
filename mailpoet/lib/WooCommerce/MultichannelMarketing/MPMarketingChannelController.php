<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce\MultichannelMarketing;

use MailPoet\Features\FeaturesController;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\CdnAssetUrl;

class MPMarketingChannelController {

  /** @var CdnAssetUrl */
  private $cdnAssetUrl;

  /** @var FeaturesController */
  private $featuresController;

  /**
   * @var SettingsController
   */
  protected $settings;

  public function __construct(
    CdnAssetUrl $cdnAssetUrl,
    FeaturesController $featuresController,
    SettingsController $settings
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->featuresController = $featuresController;
    $this->settings = $settings;
  }

  public function registerMarketingChannel($registeredMarketingChannels): array {
    if (!$this->featuresController->isSupported(FeaturesController::MAILPOET_WOOCOMMERCE_MULTICHANNEL_INTEGRATION)) {
      return $registeredMarketingChannels; // Do not register the marketing channel if the feature flag is not enabled
    }

    return array_merge($registeredMarketingChannels, [
      new MPMarketingChannel(
        $this->cdnAssetUrl,
        $this->settings
      ),
    ]);
  }
}
