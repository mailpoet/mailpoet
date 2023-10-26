<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers\Orders;

use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\Orders\OrderCreatedTrigger;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;

/**
 * @group woo
 */
class OrderCreatedTriggerTest extends \MailPoetTest {

  /** @var OrderCreatedTrigger */
  private $testee;

  /** @var AutomationRunStorage */
  private $runStorage;

  public function _before() {
    $this->testee = $this->diContainer->get(OrderCreatedTrigger::class);
    $this->runStorage = $this->diContainer->get(AutomationRunStorage::class);
  }

  public function testCreatesRunWhenANewOrderIsCreated() {
    $orderCreatedTriggerStep = new Step(
      uniqid(),
      Step::TYPE_TRIGGER,
      $this->testee->getKey(),
      [],
      []
    );
    $automation = (new AutomationFactory())
      ->withStep($orderCreatedTriggerStep)
      ->withDelayAction()
      ->withStatusActive()
      ->create();
    $this->testee->registerHooks();

    $this->assertEmpty($this->runStorage->getAutomationRunsForAutomation($automation));

    $order = wc_create_order(['customer_id' => 1]);
    $this->assertInstanceOf(\WC_Order::class, $order);

    $runs = $this->runStorage->getAutomationRunsForAutomation($automation);
    $this->assertCount(1, $runs);
    /** @var AutomationRun $run */
    $run = current($runs);

    $subject = $run->getSubjects(OrderSubject::KEY)[0];
    $this->assertEquals($order->get_id(), $subject->getArgs()['order_id']);

    // Test with no email address available yet.
    $order = wc_create_order();
    $this->assertInstanceOf(\WC_Order::class, $order);

    $runs = $this->runStorage->getAutomationRunsForAutomation($automation);
    $this->assertCount(1, $runs);
    $order->set_billing_email('someone@example.com');
    $order->save();
    $runs = $this->runStorage->getAutomationRunsForAutomation($automation);
    $this->assertCount(2, $runs);
    $order->set_status('failed');
    $order->save();
    $runs = $this->runStorage->getAutomationRunsForAutomation($automation);
    $this->assertCount(2, $runs);
  }
}
