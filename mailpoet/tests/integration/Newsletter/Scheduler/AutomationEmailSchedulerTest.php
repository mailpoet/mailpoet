<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
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
    $scheduledTaskSubscriber = $this->automationEmailScheduler->getScheduledTaskSubscriber($this->newsletter, $this->subscriber, 1);
    verify($scheduledTaskSubscriber)->null();
  }

  public function testGetScheduledTaskSubscriberReturnsNullForUnknownRunId() {
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, []);
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, $this->getMeta(1));

    $scheduledTaskSubscriber = $this->automationEmailScheduler->getScheduledTaskSubscriber($this->newsletter, $this->subscriber, 2);
    verify($scheduledTaskSubscriber)->null();
  }

  public function testGetScheduledTaskSubscriberReturnsProperEntityForRun() {
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, []);
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, $this->getMeta(1));
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, $this->getMeta(2));
    $this->automationEmailScheduler->createSendingTask($this->newsletter, $this->subscriber, $this->getMeta(3));

    $scheduledTaskSubscriber = $this->automationEmailScheduler->getScheduledTaskSubscriber($this->newsletter, $this->subscriber, 1);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $scheduledTaskSubscriber);
    $task = $scheduledTaskSubscriber->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $meta = $task->getMeta();
    verify($meta['automation']['run_id'] ?? null)->equals(1);
  }

  private function getMeta(int $runId) {
    return ['automation' => ['run_id' => $runId]];
  }
}
