<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers\Orders;

use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\Orders\OrderCancelledTrigger;

/**
 * @group woo
 */
class OrderCancelledTriggerTest extends AbstractOrderSingleStatusTest {
  protected function getTestee(): Trigger {
    return $this->diContainer->get(OrderCancelledTrigger::class);
  }

  protected function getTriggerStatus(): string {
    return 'cancelled';
  }
}
