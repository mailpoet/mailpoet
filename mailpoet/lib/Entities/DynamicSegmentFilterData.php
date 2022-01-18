<?php declare(strict_types = 1);

namespace MailPoet\Entities;

use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class DynamicSegmentFilterData {
  const TYPE_USER_ROLE = 'userRole';
  const TYPE_EMAIL = 'email';
  const TYPE_WOOCOMMERCE = 'woocommerce';
  const TYPE_WOOCOMMERCE_MEMBERSHIP = 'woocommerceMembership';
  const TYPE_WOOCOMMERCE_SUBSCRIPTION = 'woocommerceSubscription';

  public const CONNECT_TYPE_AND = 'and';
  public const CONNECT_TYPE_OR = 'or';

  public const OPERATOR_ALL = 'all';
  public const OPERATOR_ANY = 'any';
  public const OPERATOR_NONE = 'none';

  /**
   * @ORM\Column(type="serialized_array")
   * @var array|null
   */
  private $filterData;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $filterType;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $action;

  public function __construct(
    string $filterType,
    string $action,
    array $filterData = []
  ) {
    $this->filterType = $filterType;
    $this->action = $action;
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
    if ($this->filterType) {
      return $this->filterType;
    }
    // When a new column is empty, we try to get the value from serialized data
    return $filterData['segmentType'] ?? null;
  }

  public function getAction(): ?string {
    if ($this->action) {
      return $this->action;
    }
    // When a new column is empty, we try to get the value from serialized data
    // BC compatibility, the wordpress user role segment didn't have action
    if ($this->getFilterType() === self::TYPE_USER_ROLE && !isset($this->filterData['action'])) {
      return UserRole::TYPE;
    }
    return $this->filterData['action'] ?? null;
  }

  public function getOperator(): ?string {
    $operator = $this->filterData['operator'] ?? null;
    if (!$operator) {
      return $this->getDefaultOperator();
    }

    return $operator;
  }

  private function getDefaultOperator(): ?string {
    if ($this->getFilterType() === self::TYPE_WOOCOMMERCE && $this->getAction() === WooCommerceProduct::ACTION_PRODUCT) {
      return self::OPERATOR_ANY;
    }
    return null;
  }
}
