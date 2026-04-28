<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\Segments;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\REST\Test;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/../Test.php';

class DynamicSegmentsEndpointsTest extends Test {
  private const BASE_PATH = '/mailpoet/v1/dynamic-segments';

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var int */
  private $editorUserId;

  public function _before() {
    parent::_before();
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    wp_set_current_user(1);
    $userId = wp_create_user('dynamic_segments_editor_' . uniqid(), 'password', 'dynamic-segments-editor-' . uniqid() . '@localhost.test');
    $this->assertIsNumeric($userId);
    $user = new \WP_User($userId);
    $user->add_role('editor');
    $this->editorUserId = $userId;
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
    is_multisite() ? wpmu_delete_user($this->editorUserId) : wp_delete_user($this->editorUserId);
  }

  public function testGetReturnsDynamicSegmentsEnvelope(): void {
    $suffix = uniqid();
    $segment = (new SegmentFactory())->withName("zzz_dynamic_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    (new SegmentFactory())->withName("zzz_static_{$suffix}")->create();
    (new SegmentFactory())->withName("zzz_trashed_dynamic_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->withDeleted()->create();

    $data = $this->get(self::BASE_PATH, ['query' => [
      'per_page' => 100,
      'orderby' => 'name',
      'order' => 'asc',
    ]]);

    $this->assertIsArray($data['data']);
    $this->assertArrayHasKey('items', $data['data']);
    $this->assertArrayHasKey('meta', $data['data']);
    $this->assertArrayHasKey('filters', $data['data']);
    $this->assertArrayHasKey('groups', $data['data']);

    $items = $data['data']['items'];
    $names = array_column($items, 'name');
    $this->assertContains("zzz_dynamic_{$suffix}", $names);
    $this->assertNotContains("zzz_static_{$suffix}", $names);
    $this->assertNotContains("zzz_trashed_dynamic_{$suffix}", $names);

    $item = $items[array_search("zzz_dynamic_{$suffix}", $names, true)];
    $this->assertSame((string)$segment->getId(), $item['id']);
    $this->assertSame(SegmentEntity::TYPE_DYNAMIC, $item['type']);
    $this->assertArrayHasKey('count_all', $item);
    $this->assertArrayHasKey('count_subscribed', $item);
    $this->assertArrayHasKey('is_plugin_missing', $item);
    $this->assertArrayHasKey('missing_plugin_message', $item);
    $this->assertArrayHasKey('subscribers_url', $item);
  }

  public function testGetSupportsTrashGroupSearchPaginationAndDefaults(): void {
    $suffix = uniqid();
    (new SegmentFactory())->withName("zzz_search_b_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    (new SegmentFactory())->withName("zzz_search_a_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $trashed = (new SegmentFactory())->withName("zzz_search_trash_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->withDeleted()->create();

    $data = $this->get(self::BASE_PATH, ['query' => [
      'search' => $suffix,
      'per_page' => 1,
      'page' => 1,
      'orderby' => 'name',
      'order' => 'asc',
    ]]);
    $this->assertSame(2, $data['data']['meta']['count']);
    $this->assertSame(2, $data['data']['meta']['pages']);
    $this->assertSame("zzz_search_a_{$suffix}", $data['data']['items'][0]['name']);

    $trashData = $this->get(self::BASE_PATH, ['query' => ['group' => 'trash', 'search' => $suffix, 'per_page' => 100]]);
    $ids = array_column($trashData['data']['items'], 'id');
    $this->assertContains((string)$trashed->getId(), $ids);
  }

  public function testGetUsesDefaultPaginationAndSortWhenParamsAreOmitted(): void {
    $suffix = uniqid();
    $older = (new SegmentFactory())->withName("zzz_default_older_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $newer = (new SegmentFactory())->withName("zzz_default_newer_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $fillers = [];
    for ($i = 0; $i < 25; $i++) {
      $fillers[] = (new SegmentFactory())->withName("zzz_default_filler_{$suffix}_{$i}")->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    }
    $this->setUpdatedAt($older, new Carbon('2024-01-01 00:00:00'));
    $this->setUpdatedAt($newer, new Carbon('2024-01-02 00:00:00'));
    foreach ($fillers as $filler) {
      $this->setUpdatedAt($filler, new Carbon('2023-01-01 00:00:00'));
    }
    $this->entityManager->flush();

    $data = $this->get(self::BASE_PATH, ['query' => ['search' => $suffix]]);
    $names = array_column($data['data']['items'], 'name');

    $this->assertCount(25, $data['data']['items']);
    $this->assertSame("zzz_default_newer_{$suffix}", $names[0]);
  }

  public function testGetSupportsLegacySortAliasesAndOffset(): void {
    $suffix = uniqid();
    (new SegmentFactory())->withName("zzz_alias_a_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    (new SegmentFactory())->withName("zzz_alias_b_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    (new SegmentFactory())->withName("zzz_alias_c_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->create();

    $data = $this->get(self::BASE_PATH, ['query' => [
      'search' => $suffix,
      'limit' => 1,
      'offset' => 2,
      'sort_by' => 'name',
      'sort_order' => 'asc',
    ]]);

    $this->assertSame(3, $data['data']['meta']['count']);
    $this->assertSame("zzz_alias_c_{$suffix}", $data['data']['items'][0]['name']);
  }

  public function testGetRejectsInvalidListingParamsAndUsersWithoutPermission(): void {
    $this->assertSame('mailpoet_segments_invalid_group', $this->get(self::BASE_PATH, ['query' => ['group' => 'archived']])['code']);
    $this->assertSame('mailpoet_segments_invalid_orderby', $this->get(self::BASE_PATH, ['query' => ['orderby' => 'bad_field']])['code']);
    $this->assertSame('mailpoet_segments_invalid_order', $this->get(self::BASE_PATH, ['query' => ['order' => 'sideways']])['code']);
    $this->assertSame('mailpoet_segments_invalid_page', $this->get(self::BASE_PATH, ['query' => ['page' => 0]])['code']);
    $this->assertSame('mailpoet_segments_invalid_per_page', $this->get(self::BASE_PATH, ['query' => ['limit' => 101]])['code']);

    wp_set_current_user($this->editorUserId);
    $this->assertSame('rest_forbidden', $this->get(self::BASE_PATH)['code']);
  }

  public function testBulkActionTrashesRestoresAndDeletesDynamicSegments(): void {
    $segment = (new SegmentFactory())->withName('Dynamic one')->withType(SegmentEntity::TYPE_DYNAMIC)->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$segment->getId()]],
    ]);
    $this->entityManager->clear();
    $this->assertSame(1, $data['data']['updated']);
    $trashedSegment = $this->segmentsRepository->findOneById($segment->getId());
    $this->assertInstanceOf(SegmentEntity::class, $trashedSegment);
    $this->assertNotNull($trashedSegment->getDeletedAt());

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'restore', 'ids' => [(int)$segment->getId()]],
    ]);
    $this->entityManager->clear();
    $this->assertSame(1, $data['data']['updated']);
    $restoredSegment = $this->segmentsRepository->findOneById($segment->getId());
    $this->assertInstanceOf(SegmentEntity::class, $restoredSegment);
    $this->assertNull($restoredSegment->getDeletedAt());

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$segment->getId()]],
    ]);
    $this->entityManager->clear();
    $this->assertSame(1, $data['data']['updated']);

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'delete', 'ids' => [(int)$segment->getId()]],
    ]);
    $this->entityManager->clear();
    $this->assertSame(1, $data['data']['deleted']);
    $this->assertNull($this->segmentsRepository->findOneById($segment->getId()));
  }

  public function testBulkActionRejectsWrongTypeWithoutMutatingRows(): void {
    $static = (new SegmentFactory())->withName('Static')->create();
    $dynamic = (new SegmentFactory())->withName('Dynamic')->withType(SegmentEntity::TYPE_DYNAMIC)->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$static->getId(), (int)$dynamic->getId()]],
    ]);
    $this->entityManager->clear();

    $this->assertSame('mailpoet_dynamic_segments_invalid_type', $data['code']);
    $staticAfterAction = $this->segmentsRepository->findOneById($static->getId());
    $dynamicAfterAction = $this->segmentsRepository->findOneById($dynamic->getId());
    $this->assertInstanceOf(SegmentEntity::class, $staticAfterAction);
    $this->assertInstanceOf(SegmentEntity::class, $dynamicAfterAction);
    $this->assertNull($staticAfterAction->getDeletedAt());
    $this->assertNull($dynamicAfterAction->getDeletedAt());
  }

  public function testBulkActionRejectsMissingIdsWithoutMutatingRows(): void {
    $dynamic = (new SegmentFactory())->withName('Dynamic')->withType(SegmentEntity::TYPE_DYNAMIC)->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$dynamic->getId(), 999999]],
    ]);
    $this->entityManager->clear();

    $this->assertSame('mailpoet_dynamic_segments_not_found', $data['code']);
    $dynamicAfterAction = $this->segmentsRepository->findOneById($dynamic->getId());
    $this->assertInstanceOf(SegmentEntity::class, $dynamicAfterAction);
    $this->assertNull($dynamicAfterAction->getDeletedAt());
  }

  public function testBulkActionRejectsPermanentDeleteForActiveDynamicSegments(): void {
    $active = (new SegmentFactory())->withName('Active dynamic')->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $trashed = (new SegmentFactory())->withName('Trashed dynamic')->withType(SegmentEntity::TYPE_DYNAMIC)->withDeleted()->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'delete', 'ids' => [(int)$active->getId(), (int)$trashed->getId()]],
    ]);
    $this->entityManager->clear();

    $this->assertSame('mailpoet_dynamic_segments_delete_requires_trash', $data['code']);
    $this->assertInstanceOf(SegmentEntity::class, $this->segmentsRepository->findOneById($active->getId()));
    $this->assertInstanceOf(SegmentEntity::class, $this->segmentsRepository->findOneById($trashed->getId()));
  }

  public function testBulkActionSupportsSelectAllMatchingDynamicSegments(): void {
    $suffix = uniqid();
    $first = (new SegmentFactory())->withName("zzz_select_all_first_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $second = (new SegmentFactory())->withName("zzz_select_all_second_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    (new SegmentFactory())->withName("zzz_select_all_other")->withType(SegmentEntity::TYPE_DYNAMIC)->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'select_all' => true, 'search' => $suffix, 'sort_by' => 'name', 'sort_order' => 'asc'],
    ]);
    $this->entityManager->clear();

    $this->assertSame(2, $data['data']['updated']);
    $firstAfterAction = $this->segmentsRepository->findOneById($first->getId());
    $secondAfterAction = $this->segmentsRepository->findOneById($second->getId());
    $this->assertInstanceOf(SegmentEntity::class, $firstAfterAction);
    $this->assertInstanceOf(SegmentEntity::class, $secondAfterAction);
    $this->assertNotNull($firstAfterAction->getDeletedAt());
    $this->assertNotNull($secondAfterAction->getDeletedAt());
  }

  public function testBulkActionGuestCannotMutateRows(): void {
    $segment = (new SegmentFactory())->withName('Guest protected')->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    wp_set_current_user(0);

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$segment->getId()]],
    ]);
    $this->entityManager->clear();

    $this->assertSame('rest_forbidden', $data['code']);
    $segmentAfterAction = $this->segmentsRepository->findOneById($segment->getId());
    $this->assertInstanceOf(SegmentEntity::class, $segmentAfterAction);
    $this->assertNull($segmentAfterAction->getDeletedAt());
  }

  public function testBulkActionReportsActiveNewsletterBlocker(): void {
    $segment = (new SegmentFactory())->withName('Blocked dynamic')->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    (new NewsletterFactory())
      ->withSubject('Dynamic blocker')
      ->withScheduledStatus()
      ->withScheduledQueue(['status' => ScheduledTaskEntity::STATUS_SCHEDULED])
      ->withSegments([$segment])
      ->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$segment->getId()]],
    ]);
    $this->entityManager->clear();

    $this->assertSame(0, $data['data']['updated']);
    $this->assertSame(1, $data['data']['skipped']);
    $segmentAfterAction = $this->segmentsRepository->findOneById($segment->getId());
    $this->assertInstanceOf(SegmentEntity::class, $segmentAfterAction);
    $this->assertNull($segmentAfterAction->getDeletedAt());
  }

  public function testBulkActionRejectsInvalidInputs(): void {
    $segment = (new SegmentFactory())->withType(SegmentEntity::TYPE_DYNAMIC)->create();

    $this->assertSame('mailpoet_dynamic_segments_invalid_bulk_action', $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'publish', 'ids' => [(int)$segment->getId()]],
    ])['code']);
    $this->assertSame('mailpoet_segments_ids_required', $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => []],
    ])['code']);
  }

  private function setUpdatedAt(SegmentEntity $segment, Carbon $updatedAt): void {
    $this->entityManager->createQueryBuilder()
      ->update(SegmentEntity::class, 's')
      ->set('s.updatedAt', ':updatedAt')
      ->where('s.id = :id')
      ->setParameter('updatedAt', $updatedAt)
      ->setParameter('id', $segment->getId())
      ->getQuery()
      ->execute();
  }
}
