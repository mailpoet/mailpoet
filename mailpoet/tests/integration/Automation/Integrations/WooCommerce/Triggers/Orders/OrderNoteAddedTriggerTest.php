<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers\Orders;

use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\Orders\OrderNoteAddedTrigger;
use MailPoet\Automation\Integrations\WordPress\Subjects\CommentSubject;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;

/**
 * @group woo
 */
class OrderNoteAddedTriggerTest extends \MailPoetTest {

    /** @var OrderNoteAddedTrigger */
    private $testee;

    /** @var AutomationRunStorage */
    private $automationRunStorage;

  public function _before() {
      $this->testee = $this->diContainer->get(OrderNoteAddedTrigger::class);
      $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
  }

  public function testItCreatesRunWhenOrderNoteIsAdded() {
      $orderNoteAddedTriggerStep = new Step(
        uniqid(),
        Step::TYPE_TRIGGER,
        $this->testee->getKey(),
        [],
        []
      );
      $automation = ( new AutomationFactory() )
      ->withStep($orderNoteAddedTriggerStep)
      ->withDelayAction()
      ->withStatusActive()
      ->create();
      $this->testee->registerHooks();

      $this->assertEmpty($this->automationRunStorage->getAutomationRunsForAutomation($automation));

      $order = wc_create_order(['customer_id' => 1]);
      $this->assertInstanceOf(\WC_Order::class, $order);

      // Add a note to trigger the automation
      $commentId = $order->add_order_note('Test order note');
      $this->assertIsInt($commentId);

      $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
      $this->assertCount(1, $runs);
      /** @var AutomationRun $run */
      $run = current($runs);

      $orderSubject = $run->getSubjects(OrderSubject::KEY)[0];
      $this->assertEquals($order->get_id(), $orderSubject->getArgs()['order_id']);

      $commentSubject = $run->getSubjects(CommentSubject::KEY)[0];
      $this->assertEquals($commentId, $commentSubject->getArgs()['comment_id']);
      $this->assertEquals('Test order note', $commentSubject->getArgs()['comment_content']);
      $this->assertEquals('private', $commentSubject->getArgs()['note_type']);
  }

  public function testItCreatesRunWhenCustomerNoteIsAdded() {
      $orderNoteAddedTriggerStep = new Step(
        uniqid(),
        Step::TYPE_TRIGGER,
        $this->testee->getKey(),
        [],
        []
      );
      $automation = ( new AutomationFactory() )
      ->withStep($orderNoteAddedTriggerStep)
      ->withDelayAction()
      ->withStatusActive()
      ->create();
      $this->testee->registerHooks();

      $order = wc_create_order(['customer_id' => 1]);
      $this->assertInstanceOf(\WC_Order::class, $order);

      // Add a customer note to trigger the automation
      $commentId = $order->add_order_note('Customer note', 1);
      $this->assertIsInt($commentId);

      $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
      $this->assertCount(1, $runs);
      /** @var AutomationRun $run */
      $run = current($runs);

      $commentSubject = $run->getSubjects(CommentSubject::KEY)[0];
      $this->assertEquals($commentId, $commentSubject->getArgs()['comment_id']);
      $this->assertEquals('Customer note', $commentSubject->getArgs()['comment_content']);
      $this->assertEquals('customer', $commentSubject->getArgs()['note_type']);
  }

  public function testItDoesNotRunWhenNoteContentDoesNotMatch() {
      $orderNoteAddedTriggerStep = new Step(
        uniqid(),
        Step::TYPE_TRIGGER,
        $this->testee->getKey(),
        [
              'note_contains' => 'specific text',
          ],
        []
      );
      $automation = ( new AutomationFactory() )
      ->withStep($orderNoteAddedTriggerStep)
      ->withDelayAction()
      ->withStatusActive()
      ->create();
      $this->testee->registerHooks();

      $order = wc_create_order(['customer_id' => 1]);
      $this->assertInstanceOf(\WC_Order::class, $order);

      // Add a note that doesn't contain the required text
      $order->add_order_note('Different note content');

      $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
      $this->assertCount(0, $runs);

      // Add a note that contains the required text
      $order->add_order_note('This note contains specific text');

      $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
      $this->assertCount(1, $runs);
  }

  public function testItDoesNotRunWhenNoteTypeDoesNotMatch() {
      $orderNoteAddedTriggerStep = new Step(
        uniqid(),
        Step::TYPE_TRIGGER,
        $this->testee->getKey(),
        [
              'note_type' => 'customer',
          ],
        []
      );
      $automation = ( new AutomationFactory() )
      ->withStep($orderNoteAddedTriggerStep)
      ->withDelayAction()
      ->withStatusActive()
      ->create();
      $this->testee->registerHooks();

      $order = wc_create_order(['customer_id' => 1]);
      $this->assertInstanceOf(\WC_Order::class, $order);

      // Add a private note when customer note is required
      $order->add_order_note('Private note');

      $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
      $this->assertCount(0, $runs);

      // Add a customer note
      $order->add_order_note('Customer note', 1);

      $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
      $this->assertCount(1, $runs);
  }

  public function testItRunsWhenBothFiltersMatch() {
      $orderNoteAddedTriggerStep = new Step(
        uniqid(),
        Step::TYPE_TRIGGER,
        $this->testee->getKey(),
        [
              'note_contains' => 'important',
              'note_type' => 'customer',
          ],
        []
      );
      $automation = ( new AutomationFactory() )
      ->withStep($orderNoteAddedTriggerStep)
      ->withDelayAction()
      ->withStatusActive()
      ->create();
      $this->testee->registerHooks();

      $order = wc_create_order(['customer_id' => 1]);
      $this->assertInstanceOf(\WC_Order::class, $order);

      // Add a customer note with the required text
      $order->add_order_note('This is an important customer note', 1);

      $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
      $this->assertCount(1, $runs);

      // Add a private note with the required text (should not trigger)
      $order->add_order_note('This is an important private note');

      $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
      $this->assertCount(1, $runs); // Still only 1 run

      // Add a customer note without the required text (should not trigger)
      $order->add_order_note('Regular customer note', 1);

      $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
      $this->assertCount(1, $runs); // Still only 1 run
  }
}
