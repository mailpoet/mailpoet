<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce\MultichannelMarketing;

use MailPoet\Features\FeaturesController;

class MPMarketingChannelController {

  /** @var FeaturesController */
  private $featuresController;

  /**
   * @var MPMarketingChannelDataController
   */
  private $channelDataController;

  public function __construct(
    FeaturesController $featuresController,
    MPMarketingChannelDataController $channelDataController
  ) {
    $this->featuresController = $featuresController;
    $this->channelDataController = $channelDataController;
  }

  public function registerMarketingChannel($registeredMarketingChannels): array {
    if (!$this->featuresController->isSupported(FeaturesController::MAILPOET_WOOCOMMERCE_MULTICHANNEL_INTEGRATION)) {
      return $registeredMarketingChannels; // Do not register the marketing channel if the feature flag is not enabled
    }

    return array_merge($registeredMarketingChannels, [
      new MPMarketingChannel(
        $this->channelDataController
      ),
    ]);
  }
}
