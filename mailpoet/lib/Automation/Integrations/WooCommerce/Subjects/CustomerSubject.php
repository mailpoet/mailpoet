<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Subjects;

use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;
use MailPoet\NotFoundException;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;

/**
 * @implements Subject<CustomerPayload>
 */
class CustomerSubject implements Subject {

  const KEY = 'woocommerce:customer';

  public function getName(): string {
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
    $id = $subjectData->getArgs()['customer_id'];
    $customer = new \WC_Customer($id);
    if (!$customer->get_id()) {
      // translators: %d is the ID of the customer.
      throw NotFoundException::create()->withMessage(sprintf(__("Customer with ID '%d' not found.", 'mailpoet'), $id));
    }
    return new CustomerPayload($customer);
  }
}
