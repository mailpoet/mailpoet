<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Subjects;

use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\ProductsPayload;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

/**
 * @implements Subject<ProductsPayload>
 */
class ProductsSubject implements Subject {


  const KEY = 'woocommerce:products';

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  public function __construct(
    WooCommerceHelper $woocommerceHelper
  ) {
    $this->woocommerceHelper = $woocommerceHelper;
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getName(): string {
    return __('Product', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'product_ids' => Builder::array(Builder::integer())->required(),
    ]);
  }

  public function getPayload(SubjectData $subjectData): Payload {
    $products = array_filter(array_map(
      function ($productId) {
        return $this->woocommerceHelper->wcGetProduct($productId);
      },
      $subjectData->getArgs()['product_ids']
    ));

    // Question: What to do when the product was not found?
    if (!$products) {
      throw InvalidStateException::create()->withMessage(__('Product not found.', 'mailpoet'));
    }
    return new ProductsPayload($products);
  }
}
