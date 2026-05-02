<?php declare(strict_types = 1);

namespace integration\API\JSON\ResponseBuilders;

use MailPoet\API\JSON\ResponseBuilders\SendingQueuesResponseBuilder;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Sending\TimeZoneCampaignScheduler;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoetVendor\Carbon\Carbon;

class SendingQueuesResponseBuilderTest extends \MailPoetTest {
  /** @var NewsletterEntity */
  private $newsletter;

  /** @var ScheduledTaskEntity */
  private $scheduledTask;

  /** @var SendingQueueEntity */
  private $sendingQueue;

  /** @var SendingQueuesResponseBuilder */
  private $sendingQueuesResponseBuilder;

  public function _before() {
    $newsletterFactory = new NewsletterFactory();
    $sendingQueueFactory = new SendingQueueFactory();
    $scheduledTaskFactory = new ScheduledTaskFactory();

    $this->sendingQueuesResponseBuilder = $this->diContainer->get(SendingQueuesResponseBuilder::class);
    $scheduledAt = new Carbon('2018-10-10 10:00:00');
    $this->newsletter = $newsletterFactory->create();
    $this->scheduledTask = $scheduledTaskFactory->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, $scheduledAt);
    $this->sendingQueue = $sendingQueueFactory->create($this->scheduledTask, $this->newsletter);
  }

  public function testBuildReturnsExpectedResult() {
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

    $this->assertSame($expectedResult, $this->sendingQueuesResponseBuilder->build($this->sendingQueue));
  }

  public function testBuildAggregatesTimeZoneCampaignQueues() {
    $campaignId = 'timezone-campaign';
    $this->sendingQueue->setCountTotal(1);
    $this->sendingQueue->setCountToProcess(1);
    $this->sendingQueue->setMeta([
      TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE => true,
      TimeZoneCampaignScheduler::META_TIMEZONE_CAMPAIGN_ID => $campaignId,
      TimeZoneCampaignScheduler::META_GROUP_TIMEZONE => 'Europe/Bratislava',
      TimeZoneCampaignScheduler::META_FALLBACK_USED => false,
    ]);
    $secondTask = (new ScheduledTaskFactory())->create(
      SendingQueue::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      new Carbon('2018-10-11 10:00:00')
    );
    $secondQueue = (new SendingQueueFactory())->create($secondTask, $this->newsletter);
    $secondQueue->setCountTotal(2);
    $secondQueue->setCountToProcess(2);
    $secondQueue->setMeta([
      TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE => true,
      TimeZoneCampaignScheduler::META_TIMEZONE_CAMPAIGN_ID => $campaignId,
      TimeZoneCampaignScheduler::META_GROUP_TIMEZONE => 'America/New_York',
      TimeZoneCampaignScheduler::META_FALLBACK_USED => false,
    ]);
    $this->entityManager->flush();

    $result = $this->sendingQueuesResponseBuilder->build($this->sendingQueue);

    verify($result['status'])->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
    verify($result['scheduled_at'])->equals('2018-10-10 10:00:00');
    verify($result['count_total'])->equals(3);
    verify($result['count_processed'])->equals(0);
    verify($result['count_to_process'])->equals(3);
    verify(count($result['meta'][TimeZoneCampaignScheduler::META_TIMEZONE_BREAKDOWN]))->equals(2);
  }
}
