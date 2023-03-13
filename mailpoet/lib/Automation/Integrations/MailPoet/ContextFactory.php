<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet;

use MailPoet\Segments\SegmentsRepository;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class ContextFactory {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    WooCommerceHelper $woocommerceHelper
  ) {
    $this->segmentsRepository = $segmentsRepository;
    $this->woocommerceHelper = $woocommerceHelper;
  }

  /** @return mixed[] */
  public function getContextData(): array {
    return [
      'segments' => $this->getSegments(),
      'woocommerce' => $this->getWoocommerceData(),
    ];
  }

  private function getWoocommerceData(): array {
    if (!$this->woocommerceHelper->isWooCommerceActive()) {
      return [];
    }
    return [
      'order_statuses' => $this->woocommerceHelper->getOrderStatuses(),
    ];
  }

  private function getSegments(): array {
    $segments = [];
    foreach ($this->segmentsRepository->findAll() as $segment) {
      $segments[] = [
        'id' => $segment->getId(),
        'name' => $segment->getName(),
        'type' => $segment->getType(),
      ];
    }
    return $segments;
  }
}
