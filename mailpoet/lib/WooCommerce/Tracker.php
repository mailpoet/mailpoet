<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Statistics\StatisticsWooCommercePurchasesRepository;

class Tracker {

  /** @var StatisticsWooCommercePurchasesRepository */
  private $wooPurchasesRepository;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function __construct(
    StatisticsWooCommercePurchasesRepository $wooPurchasesRepository,
    NewslettersRepository $newslettersRepository,
    LoggerFactory $loggerFactory
  ) {
    $this->wooPurchasesRepository = $wooPurchasesRepository;
    $this->newslettersRepository = $newslettersRepository;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * @param array $data
   * @return array
   */
  public function addTrackingData($data): array {
    if (!is_array($data)) {
      return $data;
    }
    try {
      $analyticsData = $this->newslettersRepository->getAnalytics();
      $data['extensions']['mailpoet'] = [
        'campaigns_count' => $analyticsData['campaigns_count'],
      ];
      $campaignData = $this->wooPurchasesRepository->getRevenuesByCampaigns();
      $data['extensions']['mailpoet']['campaign_revenues'] = $campaignData;
    } catch (\Throwable $e) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_TRACKING)->error($e->getMessage());
      return $data;
    }
    return $data;
  }
}
