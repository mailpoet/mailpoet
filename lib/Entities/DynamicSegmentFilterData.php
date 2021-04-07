<?php

namespace MailPoet\Entities;

use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class DynamicSegmentFilterData {
  const TYPE_USER_ROLE = 'userRole';
  const TYPE_EMAIL = 'email';
  const TYPE_WOOCOMMERCE = 'woocommerce';
  const TYPE_WOOCOMMERCE_SUBSCRIPTION = 'woocommerceSubscription';

  /**
   * @ORM\Column(type="serialized_array")
   * @var array|null
   */
  private $filterData;

  public function __construct(array $filterData) {
    $this->filterData = $filterData;
  }

  public function getData(): ?array {
    return $this->filterData;
  }

  /**
   * @return mixed|null
   */
  public function getParam(string $name) {
    return $this->filterData[$name] ?? null;
  }

  public function getFilterType(): ?string {
    $filterData = $this->getData();
    return $filterData['segmentType'] ?? null;
  }
}
