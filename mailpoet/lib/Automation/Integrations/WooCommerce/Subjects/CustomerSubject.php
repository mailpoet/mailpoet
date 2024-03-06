<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Subjects;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Integrations\WooCommerce\Fields\CustomerFieldsFactory;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;
use MailPoet\NotFoundException;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use WC_Customer;
use WC_Order;

/**
 * @implements Subject<CustomerPayload>
 */
class CustomerSubject implements Subject {
  const KEY = 'woocommerce:customer';

  /** @var CustomerFieldsFactory */
  private $customerFieldsFactory;

  public function __construct(
    CustomerFieldsFactory $customerFieldsFactory
  ) {
    $this->customerFieldsFactory = $customerFieldsFactory;
  }

  public function getName(): string {
    // translators: automation subject (entity entering automation) title
    return __('WooCommerce customer', 'mailpoet');
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'customer_id' => Builder::integer()->required(),
    ]);
  }

  public function getPayload(SubjectData $subjectData): Payload {
    $customerId = $subjectData->getArgs()['customer_id'];
    $orderId = $subjectData->getArgs()['order_id'] ?? null;
    if (!$customerId) {
      return new CustomerPayload(null, $orderId);
    }

    $customer = new WC_Customer($customerId);
    if (!$customer->get_id()) {
      // translators: %d is the ID of the customer.
      throw NotFoundException::create()->withMessage(sprintf(__("Customer with ID '%d' not found.", 'mailpoet'), $customerId));
    }

    $order = wc_get_order($orderId);
    return new CustomerPayload($customer, $order instanceof WC_Order ? $order : null);
  }

  /** @return Field[] */
  public function getFields(): array {
    return $this->customerFieldsFactory->getFields();
  }
}
