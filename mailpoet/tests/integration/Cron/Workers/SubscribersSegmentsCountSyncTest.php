<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class SubscribersSegmentsCountSyncTest extends \MailPoetTest {
  /** @var SubscribersSegmentsCountSync */
  private $worker;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(SubscribersSegmentsCountSync::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
  }

  public function testItBackfillsTheColumnAndFlipsTheFlag(): void {
    $segment = (new Segment())->create();
    // The factory writes memberships directly, so the column starts unset (0).
    $withList = (new Subscriber())->withSegments([$segment])->create();
    $withoutList = (new Subscriber())->create();

    $this->assertSame(0, $this->getSegmentsCount($withList));
    $this->assertFalse((bool)$this->settings->get(SegmentSubscribersRepository::BACKFILLED_SETTING_KEY, false));

    $this->worker->processTaskStrategy($this->createTask(), microtime(true));

    $this->assertSame(1, $this->getSegmentsCount($withList));
    $this->assertSame(0, $this->getSegmentsCount($withoutList));
    $this->assertTrue((bool)$this->settings->get(SegmentSubscribersRepository::BACKFILLED_SETTING_KEY, false));
  }

  public function testItLeavesProgressMetaInPlaceAfterAFullSweep(): void {
    $subscriber = (new Subscriber())->create();
    $task = $this->createTask();

    $this->worker->processTaskStrategy($task, microtime(true));

    // The cursor is intentionally not reset to 0. The next weekly reconcile run
    // is a fresh task with empty meta (so it sweeps from 0 again on its own), and
    // leaving the cursor past the table end means a markSegmentsCountColumnReady()
    // failure retries only the flag flip instead of re-sweeping the whole table.
    $meta = $task->getMeta();
    $this->assertIsArray($meta);
    $this->assertArrayHasKey('last_subscriber_id', $meta);
    $this->assertGreaterThanOrEqual((int)$subscriber->getId(), (int)$meta['last_subscriber_id']);
  }

  public function testItRepairsDriftOnAReconcileRun(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();

    // First run backfills and flips the flag.
    $this->worker->processTaskStrategy($this->createTask(), microtime(true));
    $this->assertSame(1, $this->getSegmentsCount($subscriber));

    // Simulate a write path that forgot to update the column.
    $this->setSegmentsCountDirectly($subscriber, 99);
    $this->assertSame(99, $this->getSegmentsCount($subscriber));

    // The reconcile run re-derives from source and repairs the drift.
    $this->worker->processTaskStrategy($this->createTask(), microtime(true));
    $this->assertSame(1, $this->getSegmentsCount($subscriber));
    // The backfill flag must remain set after a reconcile run — if it were
    // ever cleared, reads would silently fall back to the anti-join.
    $this->assertTrue((bool)$this->settings->get(SegmentSubscribersRepository::BACKFILLED_SETTING_KEY));
  }

  public function testReadsSwitchToTheColumnOnlyAfterTheBackfillCompletes(): void {
    $segment = (new Segment())->create();
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->withSegments([$segment])->create();
    (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();

    $repository = $this->diContainer->get(SegmentSubscribersRepository::class);
    // Before the backfill, reads use the anti-join fallback.
    $this->assertSame(1, $repository->getSubscribersWithoutSegmentCount());

    $this->worker->processTaskStrategy($this->createTask(), microtime(true));

    // After the backfill the column is populated and trusted; the answer matches.
    $this->assertSame(1, $repository->getSubscribersWithoutSegmentCount());
  }

  private function createTask(): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(SubscribersSegmentsCountSync::TASK_TYPE);
    $task->setStatus(null);
    $this->entityManager->persist($task);
    $this->entityManager->flush();
    return $task;
  }

  private function getSegmentsCount(SubscriberEntity $subscriber): int {
    $this->entityManager->refresh($subscriber);
    return $subscriber->getSegmentsCount();
  }

  private function setSegmentsCountDirectly(SubscriberEntity $subscriber, int $count): void {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE {$subscribersTable} SET segments_count = :count WHERE id = :id",
      ['count' => $count, 'id' => $subscriber->getId()]
    );
  }
}
