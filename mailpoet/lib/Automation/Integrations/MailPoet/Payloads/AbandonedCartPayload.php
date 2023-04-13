<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Payloads;

use MailPoet\Automation\Engine\Integration\Payload;

class AbandonedCartPayload implements Payload {

  /** @var \WC_Customer */
  private $customer;

  /** @var \DateTimeImmutable */
  private $lastActivityAt;

  /** @var int[] */
  private $productIds;

  /**
   * @param \WC_Customer $customer
   * @param \DateTimeImmutable $lastActivityAt
   * @param int[] $productIds
   */
  public function __construct(
    \WC_Customer $customer,
    \DateTimeImmutable $lastActivityAt,
    array $productIds
  ) {

    $this->customer = $customer;
    $this->lastActivityAt = $lastActivityAt;
    $this->productIds = $productIds;
  }

  public function getLastActivityAt(): \DateTimeImmutable {
    return $this->lastActivityAt;
  }

  public function getCustomer(): \WC_Customer {
    return $this->customer;
  }

  /**
   * @return int[]
   */
  public function getProductIds(): array {
    return $this->productIds;
  }
}
