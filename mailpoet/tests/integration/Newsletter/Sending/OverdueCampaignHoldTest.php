<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\API\JSON\ResponseBuilders\HeldCampaignsResponseBuilder;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoetVendor\Carbon\Carbon;

class OverdueCampaignHoldTest extends \MailPoetTest {
  private OverdueCampaignHold $hold;

  public function _before() {
    parent::_before();
    $this->hold = $this->diContainer->get(OverdueCampaignHold::class);
  }

  public function _after() {
    remove_all_filters('mailpoet_hold_overdue_campaigns');
    parent::_after();
  }

  public function testItHoldsRunningOverdueTaskAndReturnsHeldCount(): void {
    $task = $this->createCampaignTask(Carbon::now()->subHours(25), null);
    $this->createCampaignTask(Carbon::now()->subHours(23), null); // not late enough

    $this->assertSame(1, $this->hold->holdDueCampaigns());

    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $task->getStatus());
    $this->assertSame(ScheduledTaskEntity::PAUSE_REASON_OVERDUE, $task->getPauseReason());
    $this->assertSame([$task->getId()], array_map(fn(ScheduledTaskEntity $held) => $held->getId(), $this->hold->getHeld()));
  }

  public function testItDoesNotHoldWhenTheFilterTurnsTheHoldOff(): void {
    add_filter('mailpoet_hold_overdue_campaigns', '__return_false');
    $task = $this->createCampaignTask(Carbon::now()->subHours(25), null);

    $this->assertSame(0, $this->hold->holdDueCampaigns());

    $this->assertNull($task->getStatus());
    $this->assertSame([], $this->hold->getHeld());
  }

  public function testItSkipsTasksThatAreStillScheduled(): void {
    $task = $this->createCampaignTask(Carbon::now()->subHours(25), ScheduledTaskEntity::STATUS_SCHEDULED);

    $this->assertSame(0, $this->hold->holdDueCampaigns());

    $this->assertSame(ScheduledTaskEntity::STATUS_SCHEDULED, $task->getStatus());
  }

  public function testItSkipsTasksThatAWorkerIsProcessing(): void {
    $task = $this->createCampaignTask(Carbon::now()->subHours(25), null);
    $task->setInProgress(true);
    $this->entityManager->flush();

    $this->assertSame(0, $this->hold->holdDueCampaigns());

    $this->assertNull($task->getStatus());
  }

  public function testItHoldsAllBatchesOfATimeZoneCampaignAndListsItOnce(): void {
    $newsletter = (new NewsletterFactory())->withSubject('Zones')->create();
    $running = $this->createCampaignTask(Carbon::now()->subHours(25), null, $newsletter, $this->getTimeZoneMeta('zones'));
    $future = $this->createCampaignTask(Carbon::now()->addHours(2), ScheduledTaskEntity::STATUS_SCHEDULED, $newsletter, $this->getTimeZoneMeta('zones'));
    $later = $this->createCampaignTask(Carbon::now()->addHours(5), ScheduledTaskEntity::STATUS_SCHEDULED, $newsletter, $this->getTimeZoneMeta('zones'));

    $this->assertSame(1, $this->hold->holdDueCampaigns());

    foreach ([$running, $future, $later] as $task) {
      $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $task->getStatus());
      $this->assertSame(ScheduledTaskEntity::PAUSE_REASON_OVERDUE, $task->getPauseReason());
    }
    $held = $this->hold->getHeld();
    $this->assertCount(1, $held);
    $this->assertSame($running->getId(), $held[0]->getId());
  }

  public function testItLeavesATimeZoneBatchThatAWorkerHasStartedRunning(): void {
    $newsletter = (new NewsletterFactory())->withSubject('Zones')->create();
    $overdue = $this->createCampaignTask(Carbon::now()->subHours(25), null, $newsletter, $this->getTimeZoneMeta('zones'));
    $started = $this->createCampaignTask(Carbon::now()->subHours(1), null, $newsletter, $this->getTimeZoneMeta('zones'));
    $started->setInProgress(true);
    $idle = $this->createCampaignTask(Carbon::now()->addHours(2), ScheduledTaskEntity::STATUS_SCHEDULED, $newsletter, $this->getTimeZoneMeta('zones'));
    $this->entityManager->flush();

    $this->assertSame(1, $this->hold->holdDueCampaigns());

    $this->entityManager->refresh($started);
    $this->entityManager->refresh($idle);
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $overdue->getStatus());
    $this->assertNull($started->getStatus());
    $this->assertNull($started->getPauseReason());
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $idle->getStatus());
    $this->assertSame(ScheduledTaskEntity::PAUSE_REASON_OVERDUE, $idle->getPauseReason());
  }

  public function testTheWorkerHoldLeavesATimeZoneSiblingThatAnotherWorkerStarted(): void {
    $newsletter = (new NewsletterFactory())->withSubject('Zones')->create();
    $own = $this->createCampaignTask(Carbon::now()->subHours(25), null, $newsletter, $this->getTimeZoneMeta('zones'));
    $own->setInProgress(true);
    $started = $this->createCampaignTask(Carbon::now()->subHours(1), null, $newsletter, $this->getTimeZoneMeta('zones'));
    $started->setInProgress(true);
    $idle = $this->createCampaignTask(Carbon::now()->addHours(2), ScheduledTaskEntity::STATUS_SCHEDULED, $newsletter, $this->getTimeZoneMeta('zones'));
    $this->entityManager->flush();

    $this->hold->holdInWorker($own);

    $this->entityManager->refresh($started);
    $this->entityManager->refresh($idle);
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $own->getStatus());
    $this->assertSame(ScheduledTaskEntity::PAUSE_REASON_OVERDUE, $own->getPauseReason());
    $this->assertNull($started->getStatus());
    $this->assertNull($started->getPauseReason());
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $idle->getStatus());
    $this->assertSame(ScheduledTaskEntity::PAUSE_REASON_OVERDUE, $idle->getPauseReason());
  }

  public function testLoadingTheHeldListDoesNotQueryPerEmail(): void {
    $this->createHeldMixedEmails(3);
    $few = $this->countSelectsForHeldList();

    $this->createHeldMixedEmails(17);
    $many = $this->countSelectsForHeldList();

    $this->assertSame($few, $many);
  }

  public function testGetHeldExcludesSenderDomainPauses(): void {
    $overdue = $this->createCampaignTask(Carbon::now()->subHours(30), ScheduledTaskEntity::STATUS_PAUSED);
    $overdue->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_OVERDUE);
    $senderDomain = $this->createCampaignTask(Carbon::now()->subHours(40), ScheduledTaskEntity::STATUS_PAUSED);
    $senderDomain->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN);
    $this->entityManager->flush();

    $held = $this->hold->getHeld();

    $this->assertSame([$overdue->getId()], array_map(fn(ScheduledTaskEntity $task) => $task->getId(), $held));
  }

  private function getTimeZoneMeta(string $campaignId): array {
    return [
      TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE => true,
      TimeZoneCampaignScheduler::META_TIMEZONE_CAMPAIGN_ID => $campaignId,
    ];
  }

  private function createCampaignTask(\DateTimeInterface $scheduledAt, ?string $status, ?NewsletterEntity $newsletter = null, ?array $queueMeta = null): ScheduledTaskEntity {
    $task = (new ScheduledTaskFactory())->create(SendingQueueWorker::TASK_TYPE, $status, $scheduledAt);
    $queue = (new SendingQueueFactory())->create($task, $newsletter ?? (new NewsletterFactory())->withSubject('Late')->create());
    $queue->setCountTotal(1);
    $queue->setCountToProcess(1);
    $queue->setMeta($queueMeta);
    $this->entityManager->flush();
    return $task;
  }

  private function createHeldMixedEmails(int $count): void {
    for ($i = 0; $i < $count; $i++) {
      $factory = (new NewsletterFactory())->withSubject('Held ' . $i);
      if ($i % 3 === 1) {
        $factory->withWpPostId((int)wp_insert_post(['post_title' => 'Campaign ' . $i, 'post_status' => 'publish']));
      }
      if ($i % 3 === 2) {
        $parent = (new NewsletterFactory())->withType(NewsletterEntity::TYPE_NOTIFICATION)->withSubject('Parent ' . $i)->create();
        $factory->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)->withParent($parent);
      }
      $task = $this->createCampaignTask(Carbon::now()->subHours(30), ScheduledTaskEntity::STATUS_PAUSED, $factory->create());
      $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_OVERDUE);
    }
    $this->entityManager->flush();
  }

  private function countSelectsForHeldList(): int {
    $this->entityManager->clear();
    $builder = $this->diContainer->get(HeldCampaignsResponseBuilder::class);
    $before = $this->getSelectCount();
    $rows = $builder->buildAll($this->hold->getHeld());
    $count = $this->getSelectCount() - $before;
    $this->assertNotEmpty($rows);
    return $count;
  }

  private function getSelectCount(): int {
    $status = $this->entityManager->getConnection()->fetchNumeric("SHOW SESSION STATUS LIKE 'Com_select'");
    $value = $status ? $status[1] : 0;
    return is_numeric($value) ? (int)$value : 0;
  }
}
