<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\Segments;

use MailPoet\Entities\SegmentEntity;
use MailPoet\REST\Test;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;

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
      'json' => ['action' => 'delete', 'ids' => [(int)$segment->getId()]],
    ]);
    $this->entityManager->clear();
    $this->assertSame(1, $data['data']['deleted']);
    $this->assertNull($this->segmentsRepository->findOneById($segment->getId()));
  }

  public function testBulkActionReportsWrongTypeAsSkipped(): void {
    $static = (new SegmentFactory())->withName('Static')->create();
    $dynamic = (new SegmentFactory())->withName('Dynamic')->withType(SegmentEntity::TYPE_DYNAMIC)->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$static->getId(), (int)$dynamic->getId()]],
    ]);
    $this->entityManager->clear();

    $this->assertSame(1, $data['data']['updated']);
    $this->assertSame(1, $data['data']['skipped']);
    $this->assertSame((int)$static->getId(), $data['data']['errors'][0]['id']);
    $staticAfterAction = $this->segmentsRepository->findOneById($static->getId());
    $dynamicAfterAction = $this->segmentsRepository->findOneById($dynamic->getId());
    $this->assertInstanceOf(SegmentEntity::class, $staticAfterAction);
    $this->assertInstanceOf(SegmentEntity::class, $dynamicAfterAction);
    $this->assertNull($staticAfterAction->getDeletedAt());
    $this->assertNotNull($dynamicAfterAction->getDeletedAt());
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
}
