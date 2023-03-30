<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\SubjectTransformers;

use MailPoet\Automation\Engine\Control\StepHandler;
use MailPoet\Automation\Engine\Control\TriggerHandler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\SubjectEntry;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\OrderStatusChangedTrigger;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\Automation\Stubs\TestAction;

require_once __DIR__ . '/../../../Stubs/TestAction.php';

/**
 * @group woo
 */
class OrderSubjectToSubscriberSubjectTransformerTest extends \MailPoetTest {

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var AutomationRunLogStorage */
  private $automationRunLogStorage;

  /** @var Registry */
  private $registry;

  /** @var TriggerHandler */
  private $triggerHandler;

  /** @var StepHandler */
  private $stepHandler;

  /** @var mixed */
  private $expectedSubscriberSubjectEntry = null;

  public function _before() {
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->automationRunLogStorage = $this->diContainer->get(AutomationRunLogStorage::class);
    $this->registry = $this->diContainer->get(Registry::class);
    $this->stepHandler = $this->diContainer->get(StepHandler::class);
    $this->triggerHandler = $this->diContainer->get(TriggerHandler::class);
    $this->triggerHandler->initialize();
    $this->expectedSubscriberSubjectEntry = null;
  }

  public function testItTransformsAnOrderSubjectToASubscriberSubject() {
    $testAction = new TestAction();
    $testAction->setSubjectKeys(SubscriberSubject::KEY);
    $testAction->setCallback(function(StepRunArgs $args) {
      $this->expectedSubscriberSubjectEntry = $args->getSingleSubjectEntry(SubscriberSubject::KEY);
    });
    $this->registry->addAction($testAction);

    /** @var OrderStatusChangedTrigger $orderChangeTrigger */
    $orderChangeTrigger = $this->diContainer->get(OrderStatusChangedTrigger::class);

    $steps = [
      new Step('trigger', Step::TYPE_TRIGGER, $orderChangeTrigger->getKey(), [
        'from' => 'any',
        'to' => 'any',
      ], [new NextStep('action')]),
      new Step('action', Step::TYPE_ACTION, $testAction->getKey(), [], []),
    ];
    $automation = $this->tester->createAutomation('test', ...$steps);
    $this->assertInstanceOf(Automation::class, $automation);

    /**
     * We need to register the hooks ourselves because the active automation has been created too late
     * and the trigger does not listen to it.
     **/
    $orderChangeTrigger->registerHooks();

    $this->assertEmpty($this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $order = new \WC_Order();

    $billingAddress = md5(uniqid()) . '@example.com';
    $order->set_billing_email($billingAddress);
    $order->set_customer_id(1);
    $order->set_status('pending');
    $order->save();

    // Lets make a status change.
    $order->set_status('completed');
    $order->save();

    $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
    $run = current($runs);
    $this->assertInstanceOf(AutomationRun::class, $run);

    // Lets execute the action step.
    $this->stepHandler->handle([
      'automation_run_id' => $run->getId(),
      'step_id' => 'action',
    ]);

    $this->assertInstanceOf(SubjectEntry::class, $this->expectedSubscriberSubjectEntry);
    $subject = $this->expectedSubscriberSubjectEntry->getSubject();
    $payload = $this->expectedSubscriberSubjectEntry->getPayload();
    $this->assertInstanceOf(SubscriberSubject::class, $subject);
    $this->assertInstanceOf(SubscriberPayload::class, $payload);
    $this->assertSame($billingAddress, $payload->getEmail());
  }

  public function _after() {
    parent::_after();
    $this->expectedSubscriberSubjectEntry = null;
    $this->subscribersRepository->truncate();
    $this->automationStorage->truncate();
    $this->automationRunStorage->truncate();
    $this->automationRunLogStorage->truncate();
  }
}
