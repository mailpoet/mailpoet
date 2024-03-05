<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Payloads;

use MailPoet\Automation\Engine\Integration\Payload;
use WC_Customer;
use WC_Order;

class CustomerPayload implements Payload {
  private ?WC_Customer $customer;
  private ?WC_Order $order;

  public function __construct(
    WC_Customer $customer = null,
    WC_Order $order = null
  ) {
    $this->customer = $customer;
    $this->order = $order;
  }

  public function getCustomer(): ?WC_Customer {
    return $this->customer;
  }

  public function getId(): int {
    return $this->customer ? $this->customer->get_id() : 0;
  }

  public function isGuest(): bool {
    return $this->customer === null;
  }
}
