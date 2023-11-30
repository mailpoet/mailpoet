<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce\MultichannelMarketing;

use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Controller\OverviewStatisticsController;
use MailPoet\Features\FeaturesController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WooCommerce\Helper;

class MPMarketingChannelController {

  /** @var CdnAssetUrl */
  private $cdnAssetUrl;

  /** @var FeaturesController */
  private $featuresController;

  /**
   * @var SettingsController
   */
  private $settings;

  /**
   * @var Bridge
   */
  private $bridge;

  /**
   * @var NewslettersRepository
   */
  private $newsletterRepository;

  /**
   * @var Helper
   */
  private $woocommerceHelper;

  /**
   * @var AutomationStorage
   */
  private $automationStorage;

  /**
   * @var NewsletterStatisticsRepository
   */
  private $newsletterStatisticsRepository;

  /**
   * @var OverviewStatisticsController
   */
  private $overviewStatisticsController;

  public function __construct(
    CdnAssetUrl $cdnAssetUrl,
    FeaturesController $featuresController,
    SettingsController $settings,
    Bridge $bridge,
    NewslettersRepository $newsletterRepository,
    Helper $woocommerceHelper,
    AutomationStorage $automationStorage,
    NewsletterStatisticsRepository $newsletterStatisticsRepository,
    OverviewStatisticsController $overviewStatisticsController
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->featuresController = $featuresController;
    $this->settings = $settings;
    $this->bridge = $bridge;
    $this->newsletterRepository = $newsletterRepository;
    $this->automationStorage = $automationStorage;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->newsletterStatisticsRepository = $newsletterStatisticsRepository;
    $this->overviewStatisticsController = $overviewStatisticsController;
  }

  public function registerMarketingChannel($registeredMarketingChannels): array {
    if (!$this->featuresController->isSupported(FeaturesController::MAILPOET_WOOCOMMERCE_MULTICHANNEL_INTEGRATION)) {
      return $registeredMarketingChannels; // Do not register the marketing channel if the feature flag is not enabled
    }

    return array_merge($registeredMarketingChannels, [
      new MPMarketingChannel(
        $this->cdnAssetUrl,
        $this->settings,
        $this->bridge,
        $this->newsletterRepository,
        $this->woocommerceHelper,
        $this->automationStorage,
        $this->newsletterStatisticsRepository,
        $this->overviewStatisticsController
      ),
    ]);
  }
}
