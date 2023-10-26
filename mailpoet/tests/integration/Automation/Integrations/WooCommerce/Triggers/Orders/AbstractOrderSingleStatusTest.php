<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers\Orders;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\OrderStatusChangePayload;

/**
 * For testing order triggers that are triggered by a status change to a specific status,
 * e.g. OrderCompletedTrigger, OrderCancelledTrigger.
 */
abstract class AbstractOrderSingleStatusTest extends \MailPoetTest {
  abstract protected function getTestee(): Trigger;

  abstract protected function getTriggerStatus(): string;

  /**
   * @dataProvider dataTestIsTriggeredBy
   */
  public function testIsTriggeredBy(string $from, string $to, bool $expected) {

    $statusChangePayload = $this->createMock(OrderStatusChangePayload::class);
    $statusChangePayload->method('getFrom')->willReturn($from);
    $statusChangePayload->method('getTo')->willReturn($to);
    $stepRunArgs = $this->createMock(StepRunArgs::class);
    $stepRunArgs->method('getSinglePayloadByClass')->willReturn($statusChangePayload);
    $this->assertEquals($expected, $this->getTestee()->isTriggeredBy($stepRunArgs));
  }

  public function dataTestIsTriggeredBy() {
    $statuses = [
      'pending',
      'processing',
      'on-hold',
      'completed',
      'cancelled',
      'refunded',
      'failed',
    ];

    $data = [];
    foreach ($statuses as $from) {
      foreach ($statuses as $to) {
        if ($from === $to) {
          continue;
        }
        $data[sprintf('from_%s_to_%s', $from, $to)] = [
          'from' => $from,
          'to' => $to,
          'expected' => $to === $this->getTriggerStatus(),
        ];
      }
    }
    return $data;
  }
}
