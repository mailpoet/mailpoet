<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Cron\Workers\SubscribersSegmentsCountSync;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscriberListingRepository;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\WP\Functions as WPFunctions;

class SegmentsCountRecalculatorTest extends \MailPoetTest {
  /** @var SegmentsCountRecalculator */
  private $recalculator;

  public function _before() {
    parent::_before();
    $this->recalculator = $this->diContainer->get(SegmentsCountRecalculator::class);
  }

  public function testItCountsSubscribedNonDeletedSegments(): void {
    $segment1 = (new Segment())->create();
    $segment2 = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment1, $segment2])->create();

    // The factory writes memberships directly, so the column starts unset.
    $this->assertSame(0, $this->getSegmentsCount($subscriber));

    $this->recalculator->recalculateForSubscribers([(int)$subscriber->getId()]);

    $this->assertSame(2, $this->getSegmentsCount($subscriber));
  }

  public function testItIgnoresDeletedSegments(): void {
    $activeSegment = (new Segment())->create();
    $deletedSegment = (new Segment())->withDeleted()->create();
    $subscriber = (new Subscriber())->withSegments([$activeSegment, $deletedSegment])->create();

    $this->recalculator->recalculateForSubscribers([(int)$subscriber->getId()]);

    $this->assertSame(1, $this->getSegmentsCount($subscriber));
  }

  public function testItIgnoresNonSubscribedMemberships(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    $this->setMembershipStatus($subscriber, $segment, SubscriberEntity::STATUS_UNSUBSCRIBED);

    $this->recalculator->recalculateForSubscribers([(int)$subscriber->getId()]);

    $this->assertSame(0, $this->getSegmentsCount($subscriber));
  }

  public function testRecalculateForIdRangeCoversAllSubscribers(): void {
    $segment = (new Segment())->create();
    $first = (new Subscriber())->withSegments([$segment])->create();
    $second = (new Subscriber())->withSegments([$segment])->create();

    $this->recalculator->recalculateForIdRange((int)$first->getId(), (int)$second->getId());

    $this->assertSame(1, $this->getSegmentsCount($first));
    $this->assertSame(1, $this->getSegmentsCount($second));
  }

  public function testRecalculateForSegmentUpdatesAllMembers(): void {
    $segment = (new Segment())->create();
    $first = (new Subscriber())->withSegments([$segment])->create();
    $second = (new Subscriber())->withSegments([$segment])->create();

    $this->recalculator->recalculateForSegment((int)$segment->getId());

    $this->assertSame(1, $this->getSegmentsCount($first));
    $this->assertSame(1, $this->getSegmentsCount($second));
  }

  public function testSubscribeAndUnsubscribeKeepTheCountInSync(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $repository = $this->diContainer->get(SubscriberSegmentRepository::class);

    $repository->subscribeToSegments($subscriber, [$segment]);
    $this->assertSame(1, $this->getSegmentsCount($subscriber));

    $repository->unsubscribeFromSegments($subscriber, [$segment]);
    $this->assertSame(0, $this->getSegmentsCount($subscriber));
  }

  public function testTrashingAndRestoringASegmentUpdatesMembers(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    $segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->recalculator->recalculateForSubscribers([(int)$subscriber->getId()]);
    $this->assertSame(1, $this->getSegmentsCount($subscriber));

    $segmentsRepository->bulkTrash([(int)$segment->getId()]);
    $this->assertSame(0, $this->getSegmentsCount($subscriber));

    $segmentsRepository->bulkRestore([(int)$segment->getId()]);
    $this->assertSame(1, $this->getSegmentsCount($subscriber));
  }

  public function testHardDeletingASegmentDecrementsCountForMembers(): void {
    $segment1 = (new Segment())->create();
    $segment2 = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment1, $segment2])->create();
    $segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->recalculator->recalculateForSubscribers([(int)$subscriber->getId()]);
    $this->assertSame(2, $this->getSegmentsCount($subscriber));

    $segmentsRepository->bulkDelete([(int)$segment1->getId()]);

    $this->assertSame(1, $this->getSegmentsCount($subscriber));
  }

  public function testItDefersLargeSegmentRecalculationToTheBackgroundSweep(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    $recalculator = new class($this->entityManager) extends SegmentsCountRecalculator {
      protected const DEFER_THRESHOLD = 1;
    };

    // One subscribed membership meets the lowered threshold, so the recalc is
    // handed to the background sweep. Pre-set a sentinel to prove the inline
    // UPDATE never runs.
    $this->setSegmentsCountDirectly($subscriber, 99);
    $recalculator->recalculateForSegment((int)$segment->getId());

    $this->assertSame(99, $this->getSegmentsCount($subscriber));
    $this->assertBackgroundSweepScheduled();
  }

  public function testBulkDeleteDefersRecalculationForLargeSegments(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    $recalculator = new class($this->entityManager) extends SegmentsCountRecalculator {
      protected const DEFER_THRESHOLD = 1;
    };
    $repository = new SegmentsRepository(
      $this->entityManager,
      $this->diContainer->get(NewsletterSegmentRepository::class),
      $this->diContainer->get(FormsRepository::class),
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(LoggerFactory::class),
      $recalculator
    );

    // Over the lowered threshold, so bulkDelete must not materialize ids or
    // recompute inline; the sentinel stays untouched and the sweep is scheduled.
    $this->setSegmentsCountDirectly($subscriber, 99);
    $repository->bulkDelete([(int)$segment->getId()]);

    $this->assertSame(99, $this->getSegmentsCount($subscriber));
    $this->assertBackgroundSweepScheduled();
  }

  public function testListingQueryUsesColumnWhenBackfilled(): void {
    $segment = (new Segment())->create();
    $withList = (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->withSegments([$segment])->create();
    $withoutList = (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $this->recalculator->recalculateForSubscribers([(int)$withList->getId(), (int)$withoutList->getId()]);

    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(SegmentSubscribersRepository::BACKFILLED_SETTING_KEY, true);

    // The listing repository uses addConstraintsForSubscribersWithoutSegment()
    // on a Doctrine ORM query builder (DQL). Verify it switches to the
    // segments_count = 0 path instead of the LEFT JOIN anti-join.
    $definition = new ListingDefinition('all', ['segment' => SubscriberListingRepository::FILTER_WITHOUT_LIST], '', [], 'id', 'asc', 0, 100, []);
    $items = $this->diContainer->get(SubscriberListingRepository::class)->getData($definition);
    $ids = array_map(fn(SubscriberEntity $s) => $s->getId(), $items);

    $this->assertContains($withoutList->getId(), $ids);
    $this->assertNotContains($withList->getId(), $ids);
  }

  public function testReadPathUsesColumnWhenBackfilled(): void {
    $segment = (new Segment())->create();
    $withList = (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->withSegments([$segment])->create();
    $withoutList = (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    // The factory writes memberships directly, so populate the column for both
    // subscribers the way the backfill worker would before reads trust it.
    $this->recalculator->recalculateForSubscribers([(int)$withList->getId(), (int)$withoutList->getId()]);

    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(SegmentSubscribersRepository::BACKFILLED_SETTING_KEY, true);

    $stats = $this->diContainer->get(SegmentSubscribersRepository::class)->getSubscribersWithoutSegmentStatisticsCount();

    $this->assertSame(1, (int)$stats['all']);
    $this->assertSame(1, (int)$stats['subscribed']);
  }

  private function getSegmentsCount(SubscriberEntity $subscriber): int {
    $this->entityManager->refresh($subscriber);
    return $subscriber->getSegmentsCount();
  }

  private function assertBackgroundSweepScheduled(): void {
    $task = $this->diContainer->get(ScheduledTasksRepository::class)
      ->findOneBy(['type' => SubscribersSegmentsCountSync::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
  }

  private function setSegmentsCountDirectly(SubscriberEntity $subscriber, int $count): void {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE {$subscribersTable} SET segments_count = :count WHERE id = :id",
      ['count' => $count, 'id' => $subscriber->getId()]
    );
  }

  private function setMembershipStatus(SubscriberEntity $subscriber, SegmentEntity $segment, string $status): void {
    $subscriberSegmentsTable = $this->entityManager->getClassMetadata(\MailPoet\Entities\SubscriberSegmentEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE {$subscriberSegmentsTable} SET status = :status WHERE subscriber_id = :subscriberId AND segment_id = :segmentId",
      ['status' => $status, 'subscriberId' => $subscriber->getId(), 'segmentId' => $segment->getId()]
    );
  }
}
