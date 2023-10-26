<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers\Orders;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\OrderStatusChangePayload;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\Orders\OrderStatusChangedTrigger;

class OrderStatusChangeTriggerTest extends \MailPoetTest {
  /**
   * @dataProvider dataTestIsTriggeredBy
   */
  public function testIsTriggeredBy(string $from, string $to, string $configuredFrom, string $configuredTo, bool $expected) {

    $statusChangePayload = $this->createMock(OrderStatusChangePayload::class);
    $statusChangePayload->method('getFrom')->willReturn($from);
    $statusChangePayload->method('getTo')->willReturn($to);
    $step = $this->createMock(\MailPoet\Automation\Engine\Data\Step::class);
    $step->method('getArgs')->willReturn(['from' => $configuredFrom, 'to' => $configuredTo]);
    $stepRunArgs = $this->createMock(StepRunArgs::class);
    $stepRunArgs->method('getSinglePayloadByClass')->willReturn($statusChangePayload);
    $stepRunArgs->method('getStep')->willReturn($step);
    $testee = $this->diContainer->get(OrderStatusChangedTrigger::class);
    $this->assertEquals($expected, $testee->isTriggeredBy($stepRunArgs));
  }

  public function dataTestIsTriggeredBy() {
    return [
      'status_match' => [
        'from' => 'processing',
        'to' => 'completed',
        'configured_from' => 'wc-processing',
        'configured_to' => 'wc-completed',
        'expected' => true,
      ],
      'status_match_from_any' => [
        'from' => 'processing',
        'to' => 'completed',
        'configured_from' => 'any',
        'configured_to' => 'wc-completed',
        'expected' => true,
      ],
      'status_match_to_any' => [
        'from' => 'processing',
        'to' => 'completed',
        'configured_from' => 'wc-processing',
        'configured_to' => 'any',
        'expected' => true,
      ],
      'status_mismatch_from' => [
        'from' => 'on-hold',
        'to' => 'completed',
        'configured_from' => 'wc-processing',
        'configured_to' => 'wc-completed',
        'expected' => false,
      ],
      'status_mismatch_to' => [
        'from' => 'processing',
        'to' => 'failed',
        'configured_from' => 'wc-processing',
        'configured_to' => 'wc-completed',
        'expected' => false,
      ],
    ];
  }
}
