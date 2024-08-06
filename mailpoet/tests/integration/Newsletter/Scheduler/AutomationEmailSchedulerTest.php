<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\AutomationRun;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Subscriber;

class AutomationEmailSchedulerTest extends \MailPoetTest {

  /** @var AutomationEmailScheduler */
  private $automationEmailScheduler;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var SubscriberEntity */
  private $subscriber;

  public function _before() {
    parent::_before();
    $this->automationEmailScheduler = $this->diContainer->get(AutomationEmailScheduler::class);
    $this->newsletter = (new Newsletter())->withType(NewsletterEntity::TYPE_AUTOMATION)->create();
    $this->subscriber = (new Subscriber())->create();
  }

  public function testGetScheduledTaskSubscriberReturnsNullWhenNonExists() {
    $run = (new AutomationRun())->create();
    $scheduledTaskSubscriber = $this->automationEmailScheduler->getScheduledTaskSubscriber($this->newsletter, $this->subscriber, $run);
    verify($scheduledTaskSubscriber)->null();
  }

  public function testGetScheduledTaskSubscriberReturnsNullForUnknownRunId() {
    $run = (new AutomationRun())->create();
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, []);
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, $this->getMeta($run->getId() + 1));

    $scheduledTaskSubscriber = $this->automationEmailScheduler->getScheduledTaskSubscriber($this->newsletter, $this->subscriber, $run);
    verify($scheduledTaskSubscriber)->null();
  }

  public function testGetScheduledTaskSubscriberOnlyIgnoresScheduledTasksCreatedLongTimeBeforeRun() {
    $run1 = (new AutomationRun())->withCreatedAt(new \DateTimeImmutable('now + 2 days'))->create();
    $run2 = (new AutomationRun())->withCreatedAt(new \DateTimeImmutable('now - 1 hour'))->create();
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, $this->getMeta($run1->getId()));
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, $this->getMeta($run2->getId()));

    $scheduledTaskSubscriber = $this->automationEmailScheduler->getScheduledTaskSubscriber($this->newsletter, $this->subscriber, $run1);
    verify($scheduledTaskSubscriber)->null();

    $scheduledTaskSubscriber = $this->automationEmailScheduler->getScheduledTaskSubscriber($this->newsletter, $this->subscriber, $run2);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $scheduledTaskSubscriber);
  }

  public function testGetScheduledTaskSubscriberReturnsProperEntityForRun() {
    $run = (new AutomationRun())->create();
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, []);
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, $this->getMeta($run->getId()));
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, $this->getMeta($run->getId() + 1));
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, $this->getMeta($run->getId() + 2));

    $scheduledTaskSubscriber = $this->automationEmailScheduler->getScheduledTaskSubscriber($this->newsletter, $this->subscriber, $run);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $scheduledTaskSubscriber);
    $task = $scheduledTaskSubscriber->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $meta = $task->getMeta();
    verify($meta['automation']['run_id'] ?? null)->equals($run->getId());
  }

  private function getMeta(int $runId) {
    return ['automation' => ['run_id' => $runId]];
  }
}
