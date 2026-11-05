<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Test\DataFactories\Tag as TagFactory;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Focused coverage of {@see BulkActionController}. The HTTP-layer tests in
 * {@see \MailPoet\Test\REST\Subscribers\SubscribersEndpointsTest} only assert
 * envelope shape — per-action effects, exception codes, and the "skip already
 * unsubscribed" branch of the unsubscribe tracker live here.
 */
class BulkActionControllerTest extends \MailPoetTest {
  /** @var BulkActionController */
  private $controller;

  /** @var ListingHandler */
  private $listingHandler;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var StatisticsUnsubscribesRepository */
  private $statisticsUnsubscribesRepository;

  public function _before() {
    parent::_before();
    $this->controller = $this->diContainer->get(BulkActionController::class);
    $this->listingHandler = $this->diContainer->get(ListingHandler::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->statisticsUnsubscribesRepository = $this->diContainer->get(StatisticsUnsubscribesRepository::class);
  }

  public function testItThrowsForUnknownAction(): void {
    $exception = $this->captureException('not-a-real-action', $this->definition([]));
    $this->assertInstanceOf(BulkActionException::class, $exception);
    verify($exception->getStatusCode())->equals(400);
    verify($exception->getErrorCode())->equals('mailpoet_subscribers_invalid_bulk_action');
    verify($exception->getMessage())->stringContainsString('not-a-real-action');
  }

  public function testItTrashesAndRestoresAndDeletesSelection(): void {
    $subscriber = $this->createSubscriber('trash@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriberId = (int)$subscriber->getId();
    $definition = $this->definition([$subscriberId]);

    $trashResult = $this->controller->execute(BulkActionController::ACTION_TRASH, $definition);
    verify($trashResult['count'])->equals(1);
    verify($this->reloadSubscriber($subscriberId)->getDeletedAt())->notNull();

    $restoreResult = $this->controller->execute(BulkActionController::ACTION_RESTORE, $definition);
    verify($restoreResult['count'])->equals(1);
    verify($this->reloadSubscriber($subscriberId)->getDeletedAt())->null();

    $deleteResult = $this->controller->execute(BulkActionController::ACTION_DELETE, $definition);
    verify($deleteResult['count'])->equals(1);
    $this->entityManager->clear();
    verify($this->subscribersRepository->findOneById($subscriberId))->null();
  }

  public function testItReportsKeptSubscribersOnEmptyTrashDelete(): void {
    $deletable = (new SubscriberFactory())
      ->withEmail('plain@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->withDeletedAt(new \DateTimeImmutable())
      ->create();
    $wpUser = (new SubscriberFactory())
      ->withEmail('wpuser@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->withWpUserId(1)
      ->withDeletedAt(new \DateTimeImmutable())
      ->create();
    $wooUser = (new SubscriberFactory())
      ->withEmail('woo@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->withIsWooCommerceUser()
      ->withDeletedAt(new \DateTimeImmutable())
      ->create();

    // Empty Trash targets the whole trash group (no explicit selection).
    $result = $this->controller->execute(BulkActionController::ACTION_DELETE, $this->definition([], 'trash'));

    verify($result['count'])->equals(1);
    verify($result['kept'] ?? null)->equals(2);
    $this->entityManager->clear();
    verify($this->subscribersRepository->findOneById((int)$deletable->getId()))->null();
    verify($this->subscribersRepository->findOneById((int)$wpUser->getId()))->notNull();
    verify($this->subscribersRepository->findOneById((int)$wooUser->getId()))->notNull();
  }

  public function testItUnsubscribesAndTracksAdministrativeSourceOnce(): void {
    $segment = (new SegmentFactory())->withName('Segment')->create();
    $active = $this->createSubscriber('active@example.com', SubscriberEntity::STATUS_SUBSCRIBED, [$segment]);
    $alreadyUnsubscribed = $this->createSubscriber('done@example.com', SubscriberEntity::STATUS_UNSUBSCRIBED, [$segment]);
    $activeId = (int)$active->getId();
    $alreadyUnsubscribedId = (int)$alreadyUnsubscribed->getId();

    $result = $this->controller->execute(
      BulkActionController::ACTION_UNSUBSCRIBE,
      $this->definition([$activeId, $alreadyUnsubscribedId])
    );

    verify($result['count'])->equals(2);
    verify($this->reloadSubscriber($activeId)->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);

    // The already-unsubscribed row must not be re-tracked on each click,
    // otherwise the admin-source counter inflates on repeat bulk actions.
    verify($this->statisticsUnsubscribesRepository->findBy(['subscriber' => $this->reloadSubscriber($activeId)]))->arrayCount(1);
    verify($this->statisticsUnsubscribesRepository->findBy(['subscriber' => $this->reloadSubscriber($alreadyUnsubscribedId)]))->arrayCount(0);
  }

  public function testItMovesAddsAndRemovesFromList(): void {
    $sourceSegment = (new SegmentFactory())->withName('Source')->create();
    $targetSegment = (new SegmentFactory())->withName('Target')->create();
    $sourceId = (int)$sourceSegment->getId();
    $targetId = (int)$targetSegment->getId();
    $subscriber = $this->createSubscriber('lists@example.com', SubscriberEntity::STATUS_SUBSCRIBED, [$sourceSegment]);
    $subscriberId = (int)$subscriber->getId();
    $definition = $this->definition([$subscriberId]);

    $moveResult = $this->controller->execute(
      BulkActionController::ACTION_MOVE_TO_LIST,
      $definition,
      ['segment_id' => $targetId]
    );
    verify($moveResult['count'])->equals(1);
    $this->assertArrayHasKey('segment', $moveResult);
    verify($moveResult['segment']['id'])->equals($targetId);
    verify($moveResult['segment']['name'])->equals('Target');
    verify($this->subscribedSegmentIds($subscriberId))->equals([$targetId]);

    $addResult = $this->controller->execute(
      BulkActionController::ACTION_ADD_TO_LIST,
      $definition,
      ['segment_id' => $sourceId]
    );
    verify($addResult['count'])->equals(1);
    verify($this->subscribedSegmentIds($subscriberId))->equalsCanonicalizing([$sourceId, $targetId]);

    $removeResult = $this->controller->execute(
      BulkActionController::ACTION_REMOVE_FROM_LIST,
      $definition,
      ['segment_id' => $sourceId]
    );
    verify($removeResult['count'])->equals(1);
    verify($this->subscribedSegmentIds($subscriberId))->equals([$targetId]);

    $removeAllResult = $this->controller->execute(
      BulkActionController::ACTION_REMOVE_FROM_ALL_LISTS,
      $definition
    );
    verify($removeAllResult['count'])->equals(1);
    verify($this->subscribedSegmentIds($subscriberId))->equals([]);
  }

  public function testTrashUpdatesCachedAllAndTrashCounts(): void {
    $this->createSubscriber('kept@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $trashed = $this->createSubscriber('trashed@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $counts = $this->cacheEnabledCountsController();
    $counts->recalculateGlobalStatusStatisticsCache();

    $this->controller->execute(BulkActionController::ACTION_TRASH, $this->definition([(int)$trashed->getId()]));

    verify($this->tabCounts($counts))->equals(['all' => 1, 'trash' => 1]);
  }

  public function testRestoreUpdatesCachedAllAndTrashCounts(): void {
    $restored = $this->createSubscriber('restored@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $restoredId = (int)$restored->getId();
    $this->subscribersRepository->bulkTrash([$restoredId]);
    $counts = $this->cacheEnabledCountsController();
    $counts->recalculateGlobalStatusStatisticsCache();

    $this->controller->execute(BulkActionController::ACTION_RESTORE, $this->definition([$restoredId], 'trash'));

    verify($this->tabCounts($counts))->equals(['all' => 1, 'trash' => 0]);
  }

  public function testEmptyingTheTrashUpdatesCachedTrashCount(): void {
    $deleted = $this->createSubscriber('deleted@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $deletedId = (int)$deleted->getId();
    $this->subscribersRepository->bulkTrash([$deletedId]);
    $counts = $this->cacheEnabledCountsController();
    $counts->recalculateGlobalStatusStatisticsCache();

    $this->controller->execute(BulkActionController::ACTION_DELETE, $this->definition([$deletedId], 'trash'));

    verify($this->tabCounts($counts))->equals(['all' => 0, 'trash' => 0]);
  }

  public function testUnsubscribeUpdatesCachedStatusCounts(): void {
    $subscriber = $this->createSubscriber('leaving@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $counts = $this->cacheEnabledCountsController();
    $counts->recalculateGlobalStatusStatisticsCache();

    $this->controller->execute(BulkActionController::ACTION_UNSUBSCRIBE, $this->definition([(int)$subscriber->getId()]));

    $statusCounts = $counts->getGlobalStatusStatisticsCount();
    verify($statusCounts[SubscriberEntity::STATUS_SUBSCRIBED])->equals(0);
    verify($statusCounts[SubscriberEntity::STATUS_UNSUBSCRIBED])->equals(1);
  }

  public function testTrashInAListViewUpdatesThatListsCachedCounts(): void {
    $segment = (new SegmentFactory())->withName('Viewed list')->create();
    $this->createSubscriber('kept-in-list@example.com', SubscriberEntity::STATUS_SUBSCRIBED, [$segment]);
    $trashed = $this->createSubscriber('trashed-from-list@example.com', SubscriberEntity::STATUS_SUBSCRIBED, [$segment]);
    $counts = $this->cacheEnabledCountsController();
    $counts->recalculateSegmentStatisticsCache($segment);

    $this->controller->execute(
      BulkActionController::ACTION_TRASH,
      $this->definition([(int)$trashed->getId()], 'all', ['segment_in' => [(int)$segment->getId()]])
    );

    verify($this->listItemCounts($counts, $segment))->equals(['all' => 1, 'trash' => 1]);
  }

  public function testMoveToListUpdatesTargetListsCachedCounts(): void {
    $target = (new SegmentFactory())->withName('Target list')->create();
    $this->createSubscriber('already-in-target@example.com', SubscriberEntity::STATUS_SUBSCRIBED, [$target]);
    $moved = $this->createSubscriber('moved@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $counts = $this->cacheEnabledCountsController();
    $counts->recalculateSegmentStatisticsCache($target);

    $this->controller->execute(
      BulkActionController::ACTION_MOVE_TO_LIST,
      $this->definition([(int)$moved->getId()]),
      ['segment_id' => (int)$target->getId()]
    );

    verify($this->listItemCounts($counts, $target))->equals(['all' => 2, 'trash' => 0]);
  }

  public function testAddToListUpdatesTargetListsCachedCounts(): void {
    $target = (new SegmentFactory())->withName('Add target list')->create();
    $this->createSubscriber('already-in-add-target@example.com', SubscriberEntity::STATUS_SUBSCRIBED, [$target]);
    $added = $this->createSubscriber('added@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $counts = $this->cacheEnabledCountsController();
    $counts->recalculateSegmentStatisticsCache($target);

    $this->controller->execute(
      BulkActionController::ACTION_ADD_TO_LIST,
      $this->definition([(int)$added->getId()]),
      ['segment_id' => (int)$target->getId()]
    );

    verify($this->listItemCounts($counts, $target))->equals(['all' => 2, 'trash' => 0]);
  }

  public function testRemoveFromListUpdatesThatListsCachedCounts(): void {
    $segment = (new SegmentFactory())->withName('Remove source list')->create();
    $this->createSubscriber('stays-in-list@example.com', SubscriberEntity::STATUS_SUBSCRIBED, [$segment]);
    $removed = $this->createSubscriber('removed-from-list@example.com', SubscriberEntity::STATUS_SUBSCRIBED, [$segment]);
    $counts = $this->cacheEnabledCountsController();
    $counts->recalculateSegmentStatisticsCache($segment);

    $this->controller->execute(
      BulkActionController::ACTION_REMOVE_FROM_LIST,
      $this->definition([(int)$removed->getId()]),
      ['segment_id' => (int)$segment->getId()]
    );

    verify($this->listItemCounts($counts, $segment))->equals(['all' => 1, 'trash' => 0]);
  }

  public function testRemoveTagInAListViewUpdatesThatListsCachedCounts(): void {
    $segment = (new SegmentFactory())->withName('Tagged list')->create();
    $tagged = $this->createSubscriber('tagged-cached@example.com', SubscriberEntity::STATUS_SUBSCRIBED, [$segment]);
    $tag = (new TagFactory())->withName('VIP')->create();
    $this->createSubscriberTag($tagged, $tag);
    $counts = $this->cacheEnabledCountsController();
    $counts->recalculateSegmentStatisticsCache($segment);
    $this->createSubscriber('not-yet-counted@example.com', SubscriberEntity::STATUS_SUBSCRIBED, [$segment]);

    $this->controller->execute(
      BulkActionController::ACTION_REMOVE_TAG,
      $this->definition([(int)$tagged->getId()], 'all', ['segment_in' => [(int)$segment->getId()]]),
      ['tag_id' => (int)$tag->getId()]
    );

    verify($this->listItemCounts($counts, $segment))->equals(['all' => 2, 'trash' => 0]);
  }

  public function testItAddsAndRemovesTags(): void {
    $tag = (new TagFactory())->withName('VIP')->create();
    $tagId = (int)$tag->getId();
    $subscriber = $this->createSubscriber('tagged@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriberId = (int)$subscriber->getId();
    $definition = $this->definition([$subscriberId]);

    $added = $this->controller->execute(
      BulkActionController::ACTION_ADD_TAG,
      $definition,
      ['tag_id' => $tagId]
    );
    verify($added['count'])->equals(1);
    $this->assertArrayHasKey('tag', $added);
    verify($added['tag']['id'])->equals($tagId);
    verify($added['tag']['name'])->equals('VIP');
    verify($this->subscriberTagIds($subscriberId))->equals([$tagId]);

    $removed = $this->controller->execute(
      BulkActionController::ACTION_REMOVE_TAG,
      $definition,
      ['tag_id' => $tagId]
    );
    verify($removed['count'])->equals(1);
    verify($this->subscriberTagIds($subscriberId))->equals([]);
  }

  public function testItSuppressesBulkAutomationHooksByDefault(): void {
    $sourceSegment = (new SegmentFactory())->withName('Default Hook Source')->create();
    $addedSegment = (new SegmentFactory())->withName('Default Hook Added')->create();
    $movedSegment = (new SegmentFactory())->withName('Default Hook Moved')->create();
    $existingTag = (new TagFactory())->withName('Default Hook Existing Tag')->create();
    $addedTag = (new TagFactory())->withName('Default Hook Added Tag')->create();
    $subscriber = $this->createSubscriber(
      'default-hooks@example.com',
      SubscriberEntity::STATUS_SUBSCRIBED,
      [$sourceSegment]
    );
    $this->createSubscriberTag($subscriber, $existingTag);
    $definition = $this->definition([(int)$subscriber->getId()]);

    $wp = new WPFunctions();
    $hookCalls = ['segment' => 0, 'tagAdded' => 0, 'tagRemoved' => 0];
    $wp->removeAllActions('mailpoet_segment_subscribed');
    $wp->removeAllActions('mailpoet_subscriber_tag_added');
    $wp->removeAllActions('mailpoet_subscriber_tag_removed');
    $wp->addAction('mailpoet_segment_subscribed', function () use (&$hookCalls): void {
      $hookCalls['segment']++;
    });
    $wp->addAction('mailpoet_subscriber_tag_added', function () use (&$hookCalls): void {
      $hookCalls['tagAdded']++;
    });
    $wp->addAction('mailpoet_subscriber_tag_removed', function () use (&$hookCalls): void {
      $hookCalls['tagRemoved']++;
    });

    $addResult = $this->controller->execute(
      BulkActionController::ACTION_ADD_TO_LIST,
      $definition,
      ['segment_id' => (int)$addedSegment->getId()]
    );
    $moveResult = $this->controller->execute(
      BulkActionController::ACTION_MOVE_TO_LIST,
      $definition,
      ['segment_id' => (int)$movedSegment->getId()]
    );
    $addTagResult = $this->controller->execute(
      BulkActionController::ACTION_ADD_TAG,
      $definition,
      ['tag_id' => (int)$addedTag->getId()]
    );
    $removeTagResult = $this->controller->execute(
      BulkActionController::ACTION_REMOVE_TAG,
      $definition,
      ['tag_id' => (int)$existingTag->getId()]
    );

    verify($addResult['count'])->equals(1);
    verify($moveResult['count'])->equals(1);
    verify($addTagResult['count'])->equals(1);
    verify($removeTagResult['count'])->equals(1);
    verify($hookCalls)->equals(['segment' => 0, 'tagAdded' => 0, 'tagRemoved' => 0]);
    $wp->removeAllActions('mailpoet_segment_subscribed');
    $wp->removeAllActions('mailpoet_subscriber_tag_added');
    $wp->removeAllActions('mailpoet_subscriber_tag_removed');
  }

  public function testItFiresBulkAutomationHooksWhenExplicitlyEnabled(): void {
    $sourceSegment = (new SegmentFactory())->withName('Enabled Hook Source')->create();
    $addedSegment = (new SegmentFactory())->withName('Enabled Hook Added')->create();
    $movedSegment = (new SegmentFactory())->withName('Enabled Hook Moved')->create();
    $existingTag = (new TagFactory())->withName('Enabled Hook Existing Tag')->create();
    $addedTag = (new TagFactory())->withName('Enabled Hook Added Tag')->create();
    $subscriber = $this->createSubscriber(
      'enabled-hooks@example.com',
      SubscriberEntity::STATUS_SUBSCRIBED,
      [$sourceSegment]
    );
    $this->createSubscriberTag($subscriber, $existingTag);
    $definition = $this->definition([(int)$subscriber->getId()]);
    $triggerAutomations = ['trigger_automations' => true];

    $wp = new WPFunctions();
    $hookCalls = ['segment' => 0, 'tagAdded' => 0, 'tagRemoved' => 0];
    $wp->removeAllActions('mailpoet_segment_subscribed');
    $wp->removeAllActions('mailpoet_subscriber_tag_added');
    $wp->removeAllActions('mailpoet_subscriber_tag_removed');
    $wp->addAction('mailpoet_segment_subscribed', function () use (&$hookCalls): void {
      $hookCalls['segment']++;
    });
    $wp->addAction('mailpoet_subscriber_tag_added', function () use (&$hookCalls): void {
      $hookCalls['tagAdded']++;
    });
    $wp->addAction('mailpoet_subscriber_tag_removed', function () use (&$hookCalls): void {
      $hookCalls['tagRemoved']++;
    });

    $addResult = $this->controller->execute(
      BulkActionController::ACTION_ADD_TO_LIST,
      $definition,
      $triggerAutomations + ['segment_id' => (int)$addedSegment->getId()]
    );
    $moveResult = $this->controller->execute(
      BulkActionController::ACTION_MOVE_TO_LIST,
      $definition,
      $triggerAutomations + ['segment_id' => (int)$movedSegment->getId()]
    );
    $addTagResult = $this->controller->execute(
      BulkActionController::ACTION_ADD_TAG,
      $definition,
      $triggerAutomations + ['tag_id' => (int)$addedTag->getId()]
    );
    $removeTagResult = $this->controller->execute(
      BulkActionController::ACTION_REMOVE_TAG,
      $definition,
      $triggerAutomations + ['tag_id' => (int)$existingTag->getId()]
    );

    verify($addResult['count'])->equals(1);
    verify($moveResult['count'])->equals(1);
    verify($addTagResult['count'])->equals(1);
    verify($removeTagResult['count'])->equals(1);
    verify($hookCalls)->equals(['segment' => 2, 'tagAdded' => 1, 'tagRemoved' => 1]);
    $wp->removeAllActions('mailpoet_segment_subscribed');
    $wp->removeAllActions('mailpoet_subscriber_tag_added');
    $wp->removeAllActions('mailpoet_subscriber_tag_removed');
  }

  public function testItRequiresSegmentForListActions(): void {
    $subscriber = $this->createSubscriber('needs-list@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $definition = $this->definition([(int)$subscriber->getId()]);

    $exception = $this->captureException(BulkActionController::ACTION_MOVE_TO_LIST, $definition);
    $this->assertInstanceOf(BulkActionException::class, $exception);
    verify($exception->getStatusCode())->equals(400);
    verify($exception->getErrorCode())->equals('mailpoet_subscribers_missing_segment');
  }

  public function testItRequiresTagForTagActions(): void {
    $subscriber = $this->createSubscriber('needs-tag@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $definition = $this->definition([(int)$subscriber->getId()]);

    $exception = $this->captureException(BulkActionController::ACTION_ADD_TAG, $definition);
    $this->assertInstanceOf(BulkActionException::class, $exception);
    verify($exception->getStatusCode())->equals(400);
    verify($exception->getErrorCode())->equals('mailpoet_subscribers_missing_tag');
  }

  public function testItReturnsNotFoundWhenSegmentIdDoesNotExist(): void {
    $subscriber = $this->createSubscriber('bad-segment@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $definition = $this->definition([(int)$subscriber->getId()]);

    $exception = $this->captureException(
      BulkActionController::ACTION_ADD_TO_LIST,
      $definition,
      ['segment_id' => PHP_INT_MAX]
    );
    $this->assertInstanceOf(BulkActionException::class, $exception);
    verify($exception->getStatusCode())->equals(404);
    verify($exception->getErrorCode())->equals('mailpoet_subscribers_segment_not_found');
  }

  public function testItReturnsNotFoundWhenTagIdDoesNotExist(): void {
    $subscriber = $this->createSubscriber('bad-tag@example.com', SubscriberEntity::STATUS_SUBSCRIBED);
    $definition = $this->definition([(int)$subscriber->getId()]);

    $exception = $this->captureException(
      BulkActionController::ACTION_ADD_TAG,
      $definition,
      ['tag_id' => PHP_INT_MAX]
    );
    $this->assertInstanceOf(BulkActionException::class, $exception);
    verify($exception->getStatusCode())->equals(404);
    verify($exception->getErrorCode())->equals('mailpoet_subscribers_tag_not_found');
  }

  /**
   * @param int[] $selection
   */
  private function definition(array $selection, string $group = 'all', array $filter = []): ListingDefinition {
    return $this->listingHandler->getListingDefinition([
      'offset' => 0,
      'limit' => 0,
      'sort_by' => 'id',
      'sort_order' => 'desc',
      'group' => $group,
      'filter' => $filter,
      'selection' => $selection,
      'params' => [],
    ]);
  }

  /**
   * The counts cache is only used above 2,000 subscribers.
   */
  private function cacheEnabledCountsController(): SubscribersCountsController {
    return $this->getServiceWithOverrides(SubscribersCountsController::class, [
      'subscribersFeature' => $this->make(SubscribersFeature::class, ['isSubscribersCountEnoughForCache' => true]),
    ]);
  }

  /**
   * @return array<mixed> The All and Trash tab counts (site totals), keyed by tab name.
   */
  private function tabCounts(SubscribersCountsController $counts): array {
    $groups = $this->listingRepository($counts)->getGroups(new ListingDefinition('all'));
    $countsByTab = array_column($groups, 'count', 'name');
    return ['all' => $countsByTab['all'], 'trash' => $countsByTab['trash']];
  }

  /**
   * @return array<string, int> The item counts of a single-list view in the All and Trash tabs.
   */
  private function listItemCounts(SubscribersCountsController $counts, SegmentEntity $segment): array {
    $listingRepository = $this->listingRepository($counts);
    $listFilter = ['segment_in' => [(int)$segment->getId()]];
    return [
      'all' => $listingRepository->getListingCount(new ListingDefinition('all', $listFilter)),
      'trash' => $listingRepository->getListingCount(new ListingDefinition('trash', $listFilter)),
    ];
  }

  private function listingRepository(SubscribersCountsController $counts): SubscriberListingRepository {
    return $this->getServiceWithOverrides(SubscriberListingRepository::class, [
      'subscribersCountsController' => $counts,
    ]);
  }

  private function reloadSubscriber(int $id): SubscriberEntity {
    $this->entityManager->clear();
    $subscriber = $this->subscribersRepository->findOneById($id);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    return $subscriber;
  }

  /**
   * @param SegmentEntity[] $segments
   */
  private function createSubscriber(string $email, string $status, array $segments = []): SubscriberEntity {
    $factory = (new SubscriberFactory())->withEmail($email)->withStatus($status);
    if ($segments !== []) {
      $factory = $factory->withSegments($segments);
    }
    return $factory->create();
  }

  /**
   * @param array{segment_id?: int|string, tag_id?: int|string, trigger_automations?: bool} $data
   */
  private function captureException(string $action, ListingDefinition $definition, array $data = []): ?\Throwable {
    try {
      $this->controller->execute($action, $definition, $data);
    } catch (\Throwable $throwable) {
      return $throwable;
    }
    return null;
  }

  /**
   * @return int[]
   */
  private function subscribedSegmentIds(int $subscriberId): array {
    $reloaded = $this->reloadSubscriber($subscriberId);
    $ids = [];
    foreach ($reloaded->getSubscriberSegments() as $subscriberSegment) {
      if (!$subscriberSegment instanceof SubscriberSegmentEntity) continue;
      if ($subscriberSegment->getStatus() !== SubscriberEntity::STATUS_SUBSCRIBED) continue;
      $segment = $subscriberSegment->getSegment();
      if ($segment instanceof SegmentEntity) {
        $ids[] = (int)$segment->getId();
      }
    }
    sort($ids);
    return $ids;
  }

  /**
   * @return int[]
   */
  private function subscriberTagIds(int $subscriberId): array {
    $reloaded = $this->reloadSubscriber($subscriberId);
    $ids = [];
    foreach ($reloaded->getSubscriberTags() as $subscriberTag) {
      if (!$subscriberTag instanceof SubscriberTagEntity) continue;
      $tag = $subscriberTag->getTag();
      if ($tag instanceof TagEntity) {
        $ids[] = (int)$tag->getId();
      }
    }
    sort($ids);
    return $ids;
  }

  private function createSubscriberTag(SubscriberEntity $subscriber, TagEntity $tag): void {
    $subscriberTag = new SubscriberTagEntity($tag, $subscriber);
    $this->entityManager->persist($subscriberTag);
    $this->entityManager->flush();
  }
}
