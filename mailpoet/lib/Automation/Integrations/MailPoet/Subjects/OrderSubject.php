<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Subjects;

use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Integrations\MailPoet\Payloads\OrderPayload;
use MailPoet\NotFoundException;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;

/**
 * @implements Subject<OrderPayload>
 */
class OrderSubject implements Subject {

  const KEY = 'woocommerce:order';

  public function getName(): string {
    return __('WooCommerce order', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'order_id' => Builder::integer()->required(),
    ]);
  }

  public function getPayload(SubjectData $subjectData): Payload {
    $id = $subjectData->getArgs()['order_id'];
    $order = wc_get_order($id);
    if (!$order instanceof \WC_Order) {
      // translators: %d is the order ID.
      throw NotFoundException::create()->withMessage(sprintf(__("Order with ID '%d' not found.", 'mailpoet'), $id));
    }
    return new OrderPayload($order);
  }

  public function getKey(): string {
    return self::KEY;
  }
}
