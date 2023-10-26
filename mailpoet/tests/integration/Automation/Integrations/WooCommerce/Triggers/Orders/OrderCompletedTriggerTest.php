<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers\Orders;

use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\Orders\OrderCompletedTrigger;

/**
 * @group woo
 */
class OrderCompletedTriggerTest extends AbstractOrderSingleStatusTest {
  protected function getTestee(): Trigger {
    return $this->diContainer->get(OrderCompletedTrigger::class);
  }

  protected function getTriggerStatus(): string {
    return 'completed';
  }
}
