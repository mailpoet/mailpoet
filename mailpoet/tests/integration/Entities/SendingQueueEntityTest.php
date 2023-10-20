<?php declare(strict_types = 1);

namespace integration\Entities;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoetVendor\Carbon\Carbon;

class SendingQueueEntityTest extends \MailPoetTest {
  /** @var NewsletterEntity */
  private $newsletter;

  /** @var ScheduledTaskEntity */
  private $scheduledTask;

  /** @var SendingQueueEntity */
  private $sendingQueue;

  public function _before() {
    $newsletterFactory = new NewsletterFactory();
    $sendingQueueFactory = new SendingQueueFactory();
    $scheduledTaskFactory = new ScheduledTaskFactory();

    $scheduledAt = new Carbon('2018-10-10 10:00:00');
    $this->newsletter = $newsletterFactory->create();
    $this->scheduledTask = $scheduledTaskFactory->create(Sending::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, $scheduledAt);
    $this->sendingQueue = $sendingQueueFactory->create($this->scheduledTask, $this->newsletter);
    $this->assertInstanceOf(SendingQueueEntity::class, $this->sendingQueue);
  }

  public function testToArrayReturnsExpectedResult() {
    $expectedResult = [
      'id' => $this->sendingQueue->getId(),
      'type' => 'sending',
      'status' => 'scheduled',
      'priority' => 0,
      'scheduled_at' => '2018-10-10 10:00:00',
      'processed_at' => null,
      'created_at' => !is_null($this->sendingQueue->getCreatedAt()) ? $this->sendingQueue->getCreatedAt()->format('Y-m-d H:i:s') : null,
      'updated_at' => $this->sendingQueue->getUpdatedAt()->format('Y-m-d H:i:s'),
      'deleted_at' => null,
      'in_progress' => null,
      'reschedule_count' => 0,
      'meta' => null,
      'task_id' => $this->scheduledTask->getId(),
      'newsletter_id' => $this->newsletter->getId(),
      'newsletter_rendered_body' => null,
      'newsletter_rendered_subject' => null,
      'count_total' => 0,
      'count_processed' => 0,
      'count_to_process' => 0,
    ];

    $this->assertSame($expectedResult, $this->sendingQueue->toArray());
  }
}
