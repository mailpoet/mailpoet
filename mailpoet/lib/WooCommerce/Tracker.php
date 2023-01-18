<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\Statistics\StatisticsWooCommercePurchasesRepository;

class Tracker {

  /** @var StatisticsWooCommercePurchasesRepository */
  private $wooPurchasesRepository;

  public function __construct(
    StatisticsWooCommercePurchasesRepository $wooPurchasesRepository
  ) {
    $this->wooPurchasesRepository = $wooPurchasesRepository;
  }

  /**
   * @param array $data
   * @return array
   */
  public function addTrackingData($data): array {
    if (!is_array($data)) {
      return $data;
    }
    $campaignData = $this->wooPurchasesRepository->getRevenuesByCampaigns();
    $data['extensions']['mailpoet']['campaign_revenues'] = $campaignData;
    return $data;
  }
}
