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

  /** @var Helper */
  private $wooHelper;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function __construct(
    StatisticsWooCommercePurchasesRepository $wooPurchasesRepository,
    NewslettersRepository $newslettersRepository,
    Helper $wooHelper,
    LoggerFactory $loggerFactory
  ) {
    $this->wooPurchasesRepository = $wooPurchasesRepository;
    $this->newslettersRepository = $newslettersRepository;
    $this->wooHelper = $wooHelper;
    $this->loggerFactory = $loggerFactory;
  }

  public function addTrackingData(array $data): array {
    try {
      $currency = $this->wooHelper->getWoocommerceCurrency();
      $analyticsData = $this->newslettersRepository->getAnalytics();
      $data['extensions']['mailpoet'] = [
        'campaigns_count' => $analyticsData['campaigns_count'],
        'currency' => $currency,
      ];
      $campaignData = $this->formatCampaignsData($this->wooPurchasesRepository->getRevenuesByCampaigns($currency));
      $data['extensions']['mailpoet'] = array_merge($data['extensions']['mailpoet'], $campaignData);
    } catch (\Throwable $e) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_TRACKING)->error($e->getMessage());
      return $data;
    }
    return $data;
  }

  /**
   * @param array<int, array{revenue: float, campaign_id: string, campaign_type: string, orders_count: int}> $campaignsData
   * @return array<string, string|int|float>
   */
  private function formatCampaignsData(array $campaignsData): array {
    return array_reduce($campaignsData, function($result, array $campaign): array {
      $keyPrefix = 'campaign_' . $campaign['campaign_id'];
      $result[$keyPrefix . '_revenue'] = $campaign['revenue'];
      $result[$keyPrefix . '_orders_count'] = $campaign['orders_count'];
      $result[$keyPrefix . '_type'] = $campaign['campaign_type'];
      return $result;
    }, []);
  }
}
