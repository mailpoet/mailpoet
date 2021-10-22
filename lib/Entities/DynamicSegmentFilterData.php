<?php declare(strict_types = 1);

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
    return $this->filterType;
  }

  public function getAction(): ?string {
    // bc compatibility, the wordpress user role segment didn't have action
    if ($this->getFilterType() === self::TYPE_USER_ROLE && !$this->action) {
      return UserRole::TYPE;
    }
    return $this->action;
  }
}
