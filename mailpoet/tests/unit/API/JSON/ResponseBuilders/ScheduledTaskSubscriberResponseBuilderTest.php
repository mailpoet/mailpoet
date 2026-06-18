<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\ResponseBuilders;

use Codeception\Stub;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;

class ScheduledTaskSubscriberResponseBuilderTest extends \MailPoetUnitTest {
  public function testItProjectsAQueuedRowAsUnprocessed(): void {
    $subscriber = Stub::make(SubscriberEntity::class, [
      'getId' => 5,
      'getEmail' => 'pending@example.com',
      'getFirstName' => 'Pending',
      'getLastName' => 'Pat',
    ]);
    $task = Stub::make(ScheduledTaskEntity::class, ['getId' => 9]);
    $queuedSubscriber = Stub::make(ScheduledTaskQueuedSubscriberEntity::class, [
      'getSubscriber' => $subscriber,
      'getTask' => $task,
    ]);

    $item = (new ScheduledTaskSubscriberResponseBuilder())->buildQueued($queuedSubscriber);

    // The queue has no processed/failed/error columns — they are synthesized.
    verify($item['processed'])->equals(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);
    verify($item['failed'])->equals(ScheduledTaskSubscriberEntity::FAIL_STATUS_OK);
    verify($item['error'])->null();
    // Subscriber identity is mapped straight through.
    verify($item['taskId'])->equals(9);
    verify($item['subscriberId'])->equals(5);
    verify($item['email'])->equals('pending@example.com');
    verify($item['firstName'])->equals('Pending');
    verify($item['lastName'])->equals('Pat');
  }

  public function testItBuildsAListOfQueuedItems(): void {
    $queuedSubscriber = Stub::make(ScheduledTaskQueuedSubscriberEntity::class, [
      'getSubscriber' => Stub::make(SubscriberEntity::class, ['getEmail' => 'a@example.com']),
      'getTask' => Stub::make(ScheduledTaskEntity::class, ['getId' => 1]),
    ]);

    $items = (new ScheduledTaskSubscriberResponseBuilder())->buildForQueuedListing([$queuedSubscriber, $queuedSubscriber]);

    verify($items)->arrayCount(2);
    verify($items[0]['processed'])->equals(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);
  }
}
