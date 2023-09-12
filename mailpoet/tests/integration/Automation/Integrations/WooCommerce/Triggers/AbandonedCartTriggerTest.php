<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\AbandonedCartSubject;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\AbandonedCart\AbandonedCartTrigger;
use MailPoet\Cron\Workers\Automations\AbandonedCartWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class AbandonedCartTriggerTest extends \MailPoetTest {

  /** @var AbandonedCartTrigger */
  private $testee;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var int */
  private $productId;

  /** @var int */
  private $productId2;

  /** @var ScheduledTasksRepository */
  private $tasksRepository;

  /** @var AbandonedCartWorker */
  private $abandonedCartWorker;

  public function _before() {
    $this->testee = $this->diContainer->get(AbandonedCartTrigger::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->tasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->abandonedCartWorker = $this->diContainer->get(AbandonedCartWorker::class);
    $this->productId = $this->createProduct('abandoned cart trigger test product');
    $this->productId2 = $this->createProduct('abandoned cart trigger test another product');

    wp_set_current_user(1);
    $subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscribersRepository->getCurrentWPUser() ?? new SubscriberEntity();
    $subscriber->setEmail(wp_get_current_user()->user_email);
    $subscriber->setWpUserId(1);
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscribersRepository->persist($subscriber);
    $subscribersRepository->flush();
  }

  public function testAnAutomationGetsScheduled() {
    $wait = 1;
    $automation = $this->createAutomation($wait);
    $this->testee->registerHooks();
    $this->assertEmpty($this->tasksRepository->findFutureScheduledByType(AbandonedCartWorker::TASK_TYPE));

    $expectedScheduledTime = new Carbon();
    $expectedScheduledTime->addMinutes($wait);

    // Add something to the cart.
    $this->assertIsString(WC()->cart->add_to_cart($this->productId));

    // The task has been scheduled
    /**
     * @var ScheduledTaskEntity[] $scheduledTasks
     */
    $scheduledTasks = $this->tasksRepository->findFutureScheduledByType(AbandonedCartWorker::TASK_TYPE);
    $this->assertCount(1, $scheduledTasks);
    $task = current($scheduledTasks);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    // The task will be executed according to the wait setting of the trigger.
    $scheduledAt = $task->getScheduledAt();
    $this->assertNotNull($scheduledAt);
    $this->assertEquals($expectedScheduledTime->format('Y-m-d H:i'), $scheduledAt->format('Y-m-d H:i'));

    // When the task gets executed, a run is created.
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $this->assertTrue($this->abandonedCartWorker->processTaskStrategy($task, 1));

    // Still no run created, because the trigger checks the date and compares it with the setting
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));

    $oneMinutePassed = $expectedScheduledTime->subMinutes($wait);
    $task->setCreatedAt($oneMinutePassed->subSeconds(60));
    $this->assertTrue($this->abandonedCartWorker->processTaskStrategy($task, 1));
    $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
    $this->assertCount(1, $runs);

    // It will not create a second run. This is important in case you have two separate Abandoned Cart automations active with
    // different waiting times set.
    $this->assertTrue($this->abandonedCartWorker->processTaskStrategy($task, 1));
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation));

    /**
     * @var AutomationRun $run
     */
    $run = current($runs);
    $this->assertSame($automation->getId(), $run->getAutomationId());
    $abandonedCartSubject = current($run->getSubjects(AbandonedCartSubject::KEY));
    $this->assertInstanceOf(Subject::class, $abandonedCartSubject);
    $this->assertSame([$this->productId], $abandonedCartSubject->getArgs()['product_ids']);
  }

  public function testItCancelsTheRunCreationWhenCartIsEmptied() {
    $wait = 1;
    $automation = $this->createAutomation($wait);
    $this->testee->registerHooks();
    $this->assertEmpty($this->tasksRepository->findFutureScheduledByType(AbandonedCartWorker::TASK_TYPE));

    // Add something to the cart.
    $this->assertIsString(WC()->cart->add_to_cart($this->productId));
    $this->assertCount(1, $this->tasksRepository->findFutureScheduledByType(AbandonedCartWorker::TASK_TYPE));
    // Empty the cart.
    WC()->cart->empty_cart(true);
    $this->assertEmpty($this->tasksRepository->findFutureScheduledByType(AbandonedCartWorker::TASK_TYPE));
  }

  public function testItUpdatesTaskWhenCartIsUpdated() {
    $wait = 1;
    $automation = $this->createAutomation($wait);
    $this->testee->registerHooks();

    // Add something to the cart.
    $this->assertIsString(WC()->cart->add_to_cart($this->productId));

    /**
     * @var ScheduledTaskEntity $scheduled
     */
    $scheduled = current($this->tasksRepository->findFutureScheduledByType(AbandonedCartWorker::TASK_TYPE));
    $this->assertNotNull($scheduled->getCreatedAt());

    //Let some time pass.
    $createdAt = (new Carbon($scheduled->getCreatedAt()))->subMinute();
    $this->tasksRepository->persist($scheduled);
    $this->tasksRepository->flush();
    $updatedAt = $scheduled->getUpdatedAt();
    $meta = $scheduled->getMeta();
    $this->assertIsArray($meta);
    $productIds = $meta['product_ids'];
    // Change the cart again.
    $this->assertIsString(WC()->cart->add_to_cart($this->productId2, 2));

    /**
     * @var ScheduledTaskEntity[] $newlyScheduled
     */
    $newlyScheduled = $this->tasksRepository->findFutureScheduledByType(AbandonedCartWorker::TASK_TYPE);
    $this->assertCount(1, $newlyScheduled);
    /**
     * @var ScheduledTaskEntity $newlyScheduled
     */
    $newlyScheduled = current($newlyScheduled);
    /**
     * @var \DateTimeInterface $newCreatedAt
     */
    $newCreatedAt = $newlyScheduled->getCreatedAt();
    $this->assertNotSame($createdAt->format('Y-m-d H:i:s'), $newCreatedAt->format('Y-m-d H:i:s'));
    $this->assertNotSame($updatedAt, $newlyScheduled->getUpdatedAt());
    $newMeta = $newlyScheduled->getMeta();
    $this->assertIsArray($newMeta);
    $this->assertNotSame($productIds, $newMeta['product_ids']);
    $this->assertSame([$this->productId, $this->productId2], $newMeta['product_ids']);
  }

  private function createAutomation(int $wait = 1): Automation {
    $trigger = new Step(
      'trigger',
      Step::TYPE_TRIGGER,
      AbandonedCartTrigger::KEY,
      [
        'wait' => $wait,
      ],
      [new NextStep('action')]
    );
    $action = new Step(
      'action',
      Step::TYPE_ACTION,
      'core:delay',
      [
        'delay' => 1,
        'delay_type' => 'MINUTES',
      ],
      []
    );
    return (new AutomationFactory())
      ->withStatusActive()
      ->addStep($trigger)
      ->addStep($action)
      ->create();
  }

  private function createProduct(string $name, float $price = 1.99): int {
    $productData = [
      'post_type' => 'product',
      'post_status' => 'publish',
      'post_title' => $name,
    ];
    $productId = wp_insert_post($productData);
    $this->assertIsInt($productId);
    update_post_meta($productId, '_price', $price);
    return $productId;
  }

  public function _after() {
    wp_set_current_user(0);
    $this->tasksRepository->truncate();
    parent::_after();
  }
}
