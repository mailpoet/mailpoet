<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Payloads;

use MailPoet\Automation\Engine\Integration\Payload;

class ProductsPayload implements Payload {


  private $products;

  /**
   * @param \WC_Product[] $products
   */
  public function __construct(
    array $products
  ) {
    $this->products = $products;
  }

  /**
   * @return \WC_Product[]
   */
  public function getProducts(): array {
    return $this->products;
  }
}
