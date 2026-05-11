<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\NewsletterReplayMetadata;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;

class LatestNewsletterReplayTest extends \MailPoetTest {
  private NewsletterTask $newsletterTask;

  public function _before() {
    parent::_before();
    $this->newsletterTask = new NewsletterTask();
  }

  public function testItAllowsSentStandardNewsletterOnlyForReplayQueue(): void {
    $newsletter = (new Newsletter())->withSentStatus()->create();
    $normalTask = $this->createTaskWithQueue($newsletter, null);
    $replayTask = $this->createTaskWithQueue($newsletter, [
      NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY => true,
    ]);

    $this->assertNull($this->newsletterTask->getNewsletterFromQueue($normalTask));
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $normalTask->getStatus());
    $this->assertSame($newsletter, $this->newsletterTask->getNewsletterFromQueue($replayTask));
  }

  private function createTaskWithQueue(NewsletterEntity $newsletter, ?array $meta): ScheduledTaskEntity {
    $task = (new ScheduledTask())->create(SendingQueue::TASK_TYPE, null);
    $queue = (new SendingQueueFactory())->create($task, $newsletter);
    $queue->setMeta($meta);
    $this->entityManager->flush();
    return $task;
  }
}
