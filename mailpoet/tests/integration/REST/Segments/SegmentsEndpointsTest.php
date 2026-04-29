<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\Segments;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\REST\Test;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Form as FormFactory;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

require_once __DIR__ . '/../Test.php';

class SegmentsEndpointsTest extends Test {
  private const BASE_PATH = '/mailpoet/v1/segments';

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var int */
  private $editorUserId;

  public function _before() {
    parent::_before();
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    wp_set_current_user(1);
    $userId = wp_create_user('segments_editor_' . uniqid(), 'password', 'segments-editor-' . uniqid() . '@localhost.test');
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

  public function testGetReturnsStaticListsEnvelope(): void {
    $suffix = uniqid();
    $list = (new SegmentFactory())->withName("zzz_list_{$suffix}")->withDescription('Description')->create();
    (new SegmentFactory())->withName("zzz_trashed_{$suffix}")->withDeleted()->create();
    (new SegmentFactory())->withName("zzz_dynamic_{$suffix}")->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    (new SubscriberFactory())->withSegments([$list])->create();

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
    $this->assertContains("zzz_list_{$suffix}", $names);
    $this->assertNotContains("zzz_trashed_{$suffix}", $names);
    $this->assertNotContains("zzz_dynamic_{$suffix}", $names);

    $item = $items[array_search("zzz_list_{$suffix}", $names, true)];
    $this->assertSame((string)$list->getId(), $item['id']);
    $this->assertSame(SegmentEntity::TYPE_DEFAULT, $item['type']);
    $this->assertArrayHasKey('subscribers_count', $item);
    $this->assertArrayHasKey('subscribers_url', $item);
    $this->assertArrayHasKey('show_in_manage_subscription_page', $item);
  }

  public function testGetReturnsTrashGroupAndCounts(): void {
    $trashed = (new SegmentFactory())->withName('Trashed_' . uniqid())->withDeleted()->create();

    $data = $this->get(self::BASE_PATH, ['query' => ['group' => 'trash', 'per_page' => 100]]);
    $ids = array_column($data['data']['items'], 'id');
    $this->assertContains((string)$trashed->getId(), $ids);
    foreach ($data['data']['items'] as $item) {
      $this->assertNotNull($item['deleted_at']);
    }

    $groups = array_column($data['data']['groups'], 'count', 'name');
    $this->assertArrayHasKey('all', $groups);
    $this->assertArrayHasKey('trash', $groups);
    $this->assertGreaterThanOrEqual(1, $groups['trash']);
  }

  public function testGetSearchesStaticLists(): void {
    $suffix = uniqid();
    $matching = (new SegmentFactory())->withName("Searchable list {$suffix}")->create();
    (new SegmentFactory())->withName("Hidden list {$suffix}")->create();

    $data = $this->get(self::BASE_PATH, ['query' => [
      'search' => "Searchable list {$suffix}",
      'per_page' => 100,
    ]]);

    $ids = array_column($data['data']['items'], 'id');
    $this->assertContains((string)$matching->getId(), $ids);
    foreach ($data['data']['items'] as $item) {
      $this->assertStringContainsString("Searchable list {$suffix}", $item['name'] . $item['description']);
    }
  }

  public function testGetRejectsInvalidListingParams(): void {
    $this->assertSame('mailpoet_segments_invalid_group', $this->get(self::BASE_PATH, ['query' => ['group' => 'archived']])['code']);
    $this->assertSame('mailpoet_segments_invalid_orderby', $this->get(self::BASE_PATH, ['query' => ['orderby' => 'bad_field']])['code']);
    $this->assertSame('mailpoet_segments_invalid_order', $this->get(self::BASE_PATH, ['query' => ['order' => 'sideways']])['code']);
    $this->assertSame('mailpoet_segments_invalid_page', $this->get(self::BASE_PATH, ['query' => ['page' => 0]])['code']);
    $this->assertSame('mailpoet_segments_invalid_per_page', $this->get(self::BASE_PATH, ['query' => ['per_page' => 101]])['code']);
    $this->assertSame('mailpoet_segments_invalid_offset', $this->get(self::BASE_PATH, ['query' => ['offset' => 100001]])['code']);
  }

  public function testGetRejectsUsersWithoutPermission(): void {
    wp_set_current_user($this->editorUserId);

    $data = $this->get(self::BASE_PATH);
    $this->assertSame('rest_forbidden', $data['code']);
  }

  public function testBulkActionTrashesAndRestoresStaticLists(): void {
    $list = (new SegmentFactory())->withName('Trash me')->create();
    $wpUsers = (new SegmentFactory())->withName('WP Users')->withType(SegmentEntity::TYPE_WP_USERS)->create();
    $subscriber = (new SubscriberFactory())->withSegments([$wpUsers])->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$list->getId(), (int)$wpUsers->getId()]],
    ]);
    $this->entityManager->clear();
    $this->assertSame(2, $data['data']['updated']);
    $this->assertSame(0, $data['data']['skipped']);
    $trashedList = $this->segmentsRepository->findOneById($list->getId());
    $trashedWpUsers = $this->segmentsRepository->findOneById($wpUsers->getId());
    $trashedSubscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SegmentEntity::class, $trashedList);
    $this->assertInstanceOf(SegmentEntity::class, $trashedWpUsers);
    $this->assertInstanceOf(SubscriberEntity::class, $trashedSubscriber);
    $this->assertNotNull($trashedList->getDeletedAt());
    $this->assertNotNull($trashedWpUsers->getDeletedAt());
    $this->assertNotNull($trashedSubscriber->getDeletedAt());

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'restore', 'ids' => [(int)$list->getId(), (int)$wpUsers->getId()]],
    ]);
    $this->entityManager->clear();
    $this->assertSame(2, $data['data']['updated']);
    $restoredList = $this->segmentsRepository->findOneById($list->getId());
    $restoredWpUsers = $this->segmentsRepository->findOneById($wpUsers->getId());
    $this->assertInstanceOf(SegmentEntity::class, $restoredList);
    $this->assertInstanceOf(SegmentEntity::class, $restoredWpUsers);
    $this->assertNull($restoredList->getDeletedAt());
    $this->assertNull($restoredWpUsers->getDeletedAt());
  }

  public function testBulkActionSelectAllRestoresStaticTrash(): void {
    $trashedOne = (new SegmentFactory())->withName('Select all restore one')->withDeleted()->create();
    $trashedTwo = (new SegmentFactory())->withName('Select all restore two')->withDeleted()->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => [
        'action' => 'restore',
        'select_all' => true,
        'group' => 'trash',
        'page' => 1,
        'per_page' => 20,
        'orderby' => 'name',
        'order' => 'asc',
      ],
    ]);
    $this->entityManager->clear();

    $this->assertGreaterThanOrEqual(2, $data['data']['updated']);
    $restoredOne = $this->segmentsRepository->findOneById($trashedOne->getId());
    $restoredTwo = $this->segmentsRepository->findOneById($trashedTwo->getId());
    $this->assertInstanceOf(SegmentEntity::class, $restoredOne);
    $this->assertInstanceOf(SegmentEntity::class, $restoredTwo);
    $this->assertNull($restoredOne->getDeletedAt());
    $this->assertNull($restoredTwo->getDeletedAt());
  }

  public function testBulkActionReportsPartialFailures(): void {
    $blocked = (new SegmentFactory())->withName('Blocked')->create();
    $ok = (new SegmentFactory())->withName('OK')->create();
    (new FormFactory())->withName('Blocked form')->withSegments([$blocked])->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$blocked->getId(), (int)$ok->getId()]],
    ]);
    $this->entityManager->clear();

    $this->assertSame(1, $data['data']['updated']);
    $this->assertSame(1, $data['data']['skipped']);
    $this->assertSame((int)$blocked->getId(), $data['data']['errors'][0]['id']);
    $blockedAfterAction = $this->segmentsRepository->findOneById($blocked->getId());
    $okAfterAction = $this->segmentsRepository->findOneById($ok->getId());
    $this->assertInstanceOf(SegmentEntity::class, $blockedAfterAction);
    $this->assertInstanceOf(SegmentEntity::class, $okAfterAction);
    $this->assertNull($blockedAfterAction->getDeletedAt());
    $this->assertNotNull($okAfterAction->getDeletedAt());
  }

  public function testBulkActionRejectsWrongTypeWithoutMutatingRows(): void {
    $dynamic = (new SegmentFactory())->withName('Dynamic')->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $list = (new SegmentFactory())->withName('Static')->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$dynamic->getId(), (int)$list->getId()]],
    ]);
    $this->entityManager->clear();

    $this->assertSame('mailpoet_segments_invalid_type', $data['code']);
    $dynamicAfterAction = $this->segmentsRepository->findOneById($dynamic->getId());
    $listAfterAction = $this->segmentsRepository->findOneById($list->getId());
    $this->assertInstanceOf(SegmentEntity::class, $dynamicAfterAction);
    $this->assertInstanceOf(SegmentEntity::class, $listAfterAction);
    $this->assertNull($dynamicAfterAction->getDeletedAt());
    $this->assertNull($listAfterAction->getDeletedAt());
  }

  public function testBulkActionRejectsPermanentDeleteForActiveLists(): void {
    $active = (new SegmentFactory())->withName('Active')->create();
    $trashed = (new SegmentFactory())->withName('Trashed')->withDeleted()->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'delete', 'ids' => [(int)$active->getId(), (int)$trashed->getId()]],
    ]);
    $this->entityManager->clear();

    $this->assertSame('mailpoet_segments_delete_requires_trash', $data['code']);
    $this->assertInstanceOf(SegmentEntity::class, $this->segmentsRepository->findOneById($active->getId()));
    $this->assertInstanceOf(SegmentEntity::class, $this->segmentsRepository->findOneById($trashed->getId()));
  }

  public function testBulkActionGuestCannotMutateRows(): void {
    $list = (new SegmentFactory())->withName('Guest protected')->create();
    wp_set_current_user(0);

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$list->getId()]],
    ]);
    $this->entityManager->clear();

    $this->assertSame('rest_forbidden', $data['code']);
    $listAfterAction = $this->segmentsRepository->findOneById($list->getId());
    $this->assertInstanceOf(SegmentEntity::class, $listAfterAction);
    $this->assertNull($listAfterAction->getDeletedAt());
  }

  public function testBulkActionReportsActiveNewsletterBlocker(): void {
    $blocked = (new SegmentFactory())->withName('Blocked by newsletter')->create();
    (new NewsletterFactory())
      ->withSubject('Blocking newsletter')
      ->withScheduledStatus()
      ->withScheduledQueue(['status' => ScheduledTaskEntity::STATUS_SCHEDULED])
      ->withSegments([$blocked])
      ->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$blocked->getId()]],
    ]);
    $this->entityManager->clear();

    $this->assertSame(0, $data['data']['updated']);
    $this->assertSame(1, $data['data']['skipped']);
    $this->assertSame((int)$blocked->getId(), $data['data']['errors'][0]['id']);
    $blockedAfterAction = $this->segmentsRepository->findOneById($blocked->getId());
    $this->assertInstanceOf(SegmentEntity::class, $blockedAfterAction);
    $this->assertNull($blockedAfterAction->getDeletedAt());
  }

  public function testBulkActionSkipsWooCommerceSpecialSegment(): void {
    $woocommerce = (new SegmentFactory())->withName('WooCommerce Customers')->withType(SegmentEntity::TYPE_WC_USERS)->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$woocommerce->getId()]],
    ]);
    $this->entityManager->clear();

    $this->assertSame(0, $data['data']['updated']);
    $this->assertSame(1, $data['data']['skipped']);
    $woocommerceAfterAction = $this->segmentsRepository->findOneById($woocommerce->getId());
    $this->assertInstanceOf(SegmentEntity::class, $woocommerceAfterAction);
    $this->assertNull($woocommerceAfterAction->getDeletedAt());
  }

  public function testBulkActionDeletesAndEmptyTrashDeletesStaticListsOnly(): void {
    $trashed = (new SegmentFactory())->withName('Gone')->withDeleted()->create();
    $wpUsers = (new SegmentFactory())->withName('WP Users trashed')->withType(SegmentEntity::TYPE_WP_USERS)->withDeleted()->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'empty_trash'],
    ]);
    $this->entityManager->clear();

    $this->assertGreaterThanOrEqual(1, $data['data']['deleted']);
    $this->assertNull($this->segmentsRepository->findOneById($trashed->getId()));
    $this->assertInstanceOf(SegmentEntity::class, $this->segmentsRepository->findOneById($wpUsers->getId()));
  }

  public function testBulkActionRejectsInvalidInputs(): void {
    $list = (new SegmentFactory())->create();

    $this->assertSame('mailpoet_segments_invalid_bulk_action', $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'publish', 'ids' => [(int)$list->getId()]],
    ])['code']);
    $this->assertSame('mailpoet_segments_ids_required', $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => []],
    ])['code']);
  }
}
