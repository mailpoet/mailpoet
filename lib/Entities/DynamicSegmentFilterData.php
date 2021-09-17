<?php

namespace MailPoet\Entities;

use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class DynamicSegmentFilterData {
  const TYPE_USER_ROLE = 'userRole';
  const TYPE_EMAIL = 'email';
  const TYPE_WOOCOMMERCE = 'woocommerce';
  const TYPE_WOOCOMMERCE_SUBSCRIPTION = 'woocommerceSubscription';

  public const CONNECT_TYPE_AND = 'and';
  public const CONNECT_TYPE_OR = 'or';

  /**
   * @ORM\Column(type="serialized_array")
   * @var array|null
   */
  private $filterData;

  public function __construct(
    array $filterData
  ) {
    $this->filterData = $filterData;
  }

  public function getData(): ?array {
    $filterData = $this->filterData;
    // bc compatibility, the wordpress user role segment didn't have action
    if (($this->filterData['segmentType'] ?? null) === self::TYPE_USER_ROLE && !isset($this->filterData['action'])) {
      $filterData['action'] = UserRole::TYPE;
    }
    return $filterData;
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
