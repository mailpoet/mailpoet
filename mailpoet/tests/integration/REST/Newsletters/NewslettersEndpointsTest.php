<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\Newsletters;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\REST\Test;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

require_once __DIR__ . '/../Test.php';

/**
 * Covers the contract surface of the newsletters REST endpoints. Per-action
 * behaviour for the bulk dispatcher and status controller is covered by the
 * existing JSON-endpoint integration tests (which now delegate to the same
 * services); this file only asserts that the HTTP layer wires those services
 * and returns the expected envelope shape.
 */
class NewslettersEndpointsTest extends Test {
  private const LISTING_PATH = '/mailpoet/v1/newsletters';
  private const BULK_ACTION_PATH = '/mailpoet/v1/newsletters/bulk-action';

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function _before() {
    parent::_before();
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    wp_set_current_user(1);
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
  }

  public function testListingRejectsInvalidSortAndFilters(): void {
    $cases = [
      'unknown orderby' => [['orderby' => 'segments'], 'mailpoet_newsletters_invalid_orderby'],
      'camelCase orderby' => [['orderby' => 'sentAt'], 'mailpoet_newsletters_invalid_orderby'],
      'unknown sort_by' => [['sort_by' => 'bogus'], 'mailpoet_newsletters_invalid_orderby'],
      'bad order' => [['order' => 'sideways'], 'mailpoet_newsletters_invalid_order'],
      'unknown filter key' => [['filter' => ['bogus' => '1']], 'mailpoet_newsletters_invalid_filter'],
      'status invalid for standard' => [['filter' => ['status' => 'active']], 'mailpoet_newsletters_invalid_status'],
      'paused invalid for notification' => [['type' => 'notification', 'filter' => ['status' => 'paused']], 'mailpoet_newsletters_invalid_status'],
      'status without type' => [['type' => '', 'filter' => ['status' => 'draft']], 'mailpoet_newsletters_invalid_status'],
      'status in list invalid' => [['filter' => ['status' => ['draft', 'bogus']]], 'mailpoet_newsletters_invalid_status'],
      'bad segment' => [['filter' => ['segment' => 'abc']], 'mailpoet_newsletters_invalid_segment'],
      'bad segment_ids' => [['filter' => ['segment_ids' => ['abc'], 'segment_operator' => 'isAny']], 'mailpoet_newsletters_invalid_segment_ids'],
      'bad segment_operator' => [['filter' => ['segment_ids' => ['1'], 'segment_operator' => 'bogus']], 'mailpoet_newsletters_invalid_segment_operator'],
      'bad date shape' => [['filter' => ['sent_at_from' => '2025-1-5']], 'mailpoet_newsletters_invalid_sent_at_from'],
      'from after to' => [['filter' => ['sent_at_from' => '2025-02-02', 'sent_at_to' => '2025-02-01']], 'mailpoet_newsletters_invalid_date_range'],
    ];
    foreach ($cases as $name => [$query, $code]) {
      $query = array_merge(['type' => NewsletterEntity::TYPE_STANDARD], $query);
      $response = $this->get(self::LISTING_PATH, ['query' => $query]);
      $this->assertIsArray($response, $name);
      $this->assertSame($code, $response['code'] ?? null, $name);
      $this->assertSame(400, $this->getErrorStatus($response), $name);
    }
  }

  public function testListingKeepsAcceptingTodaysRequests(): void {
    $queries = [
      ['filter' => ['segment' => '']],
      ['filter' => ['segment' => '3']],
      ['orderby' => 'name', 'order' => 'asc'],
      ['orderby' => 'subject', 'order' => 'DESC'],
      ['orderby' => 'sent_at', 'order' => 'desc'],
      ['orderby' => 'updated_at', 'order' => 'desc'],
      ['orderby' => 'created_at', 'order' => 'asc'],
      ['sort_by' => 'status', 'sort_order' => 'asc'],
      ['filter' => ['segment_ids' => ['3'], 'segment_operator' => 'isNone', 'sent_at_from' => '2025-01-01', 'sent_at_to' => '2025-01-31']],
    ];
    foreach ($queries as $query) {
      $response = $this->get(self::LISTING_PATH, ['query' => array_merge(['type' => 'standard'], $query)]);
      $this->assertIsArray($response);
      $this->assertArrayNotHasKey('code', $response, (string)json_encode($query));
      $this->assertIsArray($this->getPayload($response)['items'] ?? null, (string)json_encode($query));
    }
  }

  public function testListingSortsByCreatedAt(): void {
    $suffix = uniqid();
    $middle = (new NewsletterFactory())->withSubject("Middle_{$suffix}")->withType('standard')->create();
    $oldest = (new NewsletterFactory())->withSubject("Oldest_{$suffix}")->withType('standard')->create();
    $newest = (new NewsletterFactory())->withSubject("Newest_{$suffix}")->withType('standard')->create();
    $middle->setCreatedAt(new \DateTimeImmutable('2025-01-02 10:00:00'));
    $oldest->setCreatedAt(new \DateTimeImmutable('2025-01-01 10:00:00'));
    $newest->setCreatedAt(new \DateTimeImmutable('2025-01-03 10:00:00'));
    $this->newslettersRepository->flush();
    $mine = [$oldest->getId(), $middle->getId(), $newest->getId()];
    $only = function(array $ids) use ($mine): array {
      return array_values(array_filter($ids, function(int $id) use ($mine): bool {
        return in_array($id, $mine, true);
      }));
    };

    $this->assertSame($mine, $only($this->listIds(['type' => 'standard', 'orderby' => 'created_at', 'order' => 'asc'])));
    $this->assertSame(array_reverse($mine), $only($this->listIds(['type' => 'standard', 'orderby' => 'created_at', 'order' => 'desc'])));
  }

  public function testListingFiltersByStatusPerType(): void {
    $suffix = uniqid();
    foreach (['welcome', 'automatic', 're_engagement', 'notification'] as $type) {
      $active = (new NewsletterFactory())->withSubject("{$type}_active_{$suffix}")->withType($type)->withStatus(NewsletterEntity::STATUS_ACTIVE)->create();
      (new NewsletterFactory())->withSubject("{$type}_draft_{$suffix}")->withType($type)->withStatus(NewsletterEntity::STATUS_DRAFT)->create();
      $this->assertSame([$active->getId()], $this->listIds(['type' => $type, 'filter' => ['status' => ['active']]]), $type);
    }

    $draft = (new NewsletterFactory())->withSubject("std_draft_{$suffix}")->withType('standard')->withStatus(NewsletterEntity::STATUS_DRAFT)->create();
    (new NewsletterFactory())->withSubject("std_sent_{$suffix}")->withType('standard')->withStatus(NewsletterEntity::STATUS_SENT)->create();
    $this->assertSame([$draft->getId()], $this->listIds(['type' => 'standard', 'filter' => ['status' => 'draft']]));

    $paused = $this->createSending(ScheduledTaskEntity::STATUS_PAUSED, "hist_paused_{$suffix}", 'notification_history');
    $this->createSending(ScheduledTaskEntity::STATUS_COMPLETED, "hist_running_{$suffix}", 'notification_history');
    $this->assertSame([$paused->getId()], $this->listIds(['type' => 'notification_history', 'filter' => ['status' => 'paused']]));
  }

  public function testBulkActionRejectsInvalidFilters(): void {
    $newsletter = (new NewsletterFactory())->withSubject('Keep_' . uniqid())->withType('standard')->create();
    $cases = [
      'unknown key' => [['bogus' => '1'], 'mailpoet_newsletters_invalid_filter'],
      'invalid status' => [['status' => 'active'], 'mailpoet_newsletters_invalid_status'],
      'bad date' => [['sent_at_to' => 'tomorrow'], 'mailpoet_newsletters_invalid_sent_at_to'],
    ];
    foreach ($cases as $name => [$filter, $code]) {
      $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
        'action' => 'trash',
        'type' => 'standard',
        'selection' => [],
        'select_all' => true,
        'group' => 'all',
        'filter' => $filter,
      ]]);
      $this->assertIsArray($response, $name);
      $this->assertSame($code, $response['code'] ?? null, $name);
      $this->assertSame(400, $this->getErrorStatus($response), $name);
    }
    $this->newslettersRepository->refresh($newsletter);
    $this->assertNull($newsletter->getDeletedAt());
  }

  public function testBulkActionSelectAllRejectsUnsupportedScope(): void {
    $newsletter = (new NewsletterFactory())->withSubject('Keep_' . uniqid())->withType('standard')->create();
    $cases = [
      'unknown type' => [['type' => 'bogus'], 'mailpoet_newsletters_invalid_type'],
      'missing type' => [['group' => 'all'], 'mailpoet_newsletters_invalid_type'],
      'missing group' => [['type' => 'standard'], 'mailpoet_newsletters_invalid_group'],
      'empty group' => [['type' => 'standard', 'group' => ''], 'mailpoet_newsletters_invalid_group'],
      'unknown group' => [['type' => 'standard', 'group' => 'bogus'], 'mailpoet_newsletters_invalid_group'],
      'group of another type' => [['type' => 'standard', 'group' => 'active'], 'mailpoet_newsletters_invalid_group'],
    ];
    foreach ($cases as $name => [$scope, $code]) {
      $response = $this->post(self::BULK_ACTION_PATH, ['json' => array_merge([
        'action' => 'delete',
        'selection' => [],
        'select_all' => true,
      ], $scope)]);
      $this->assertIsArray($response, $name);
      $this->assertSame($code, $response['code'] ?? null, $name);
      $this->assertSame(400, $this->getErrorStatus($response), $name);
    }
    $this->assertNotNull($this->newslettersRepository->findOneById($newsletter->getId()));
  }

  public function testBulkActionSelectAllAcceptsListingGroups(): void {
    foreach (['all', 'trash', 'draft', 'scheduled', 'sending', 'sent'] as $group) {
      $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
        'action' => 'restore',
        'type' => 'standard',
        'group' => $group,
        'selection' => [],
        'select_all' => true,
        'search' => 'NoMatch_' . uniqid(),
      ]]);
      $this->assertIsArray($response, $group);
      $this->assertSame(0, $this->getPayload($response)['count'], $group);
    }
  }

  public function testBulkDeleteSelectAllInAllGroupLeavesTrashedEmailsAlone(): void {
    $suffix = uniqid();
    $active = (new NewsletterFactory())->withSubject("Scope_{$suffix}_a")->withType('standard')->create();
    $trashed = (new NewsletterFactory())->withSubject("Scope_{$suffix}_t")->withType('standard')->withDeleted()->create();
    $trashedId = (int)$trashed->getId();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'delete',
      'type' => 'standard',
      'group' => 'all',
      'search' => "Scope_{$suffix}",
      'selection' => [],
      'select_all' => true,
    ]]);

    $this->assertIsArray($response);
    $this->assertSame(1, $this->getPayload($response)['count']);
    $this->entityManager->clear();
    $this->assertNull($this->newslettersRepository->findOneById($active->getId()));
    $this->assertNotNull($this->newslettersRepository->findOneById($trashedId));
  }

  public function testBulkTrashSelectAllHonoursStatusFilter(): void {
    $suffix = uniqid();
    $draft = (new NewsletterFactory())->withSubject("Draft_{$suffix}")->withType('standard')->withStatus(NewsletterEntity::STATUS_DRAFT)->create();
    $sent = (new NewsletterFactory())->withSubject("Sent_{$suffix}")->withType('standard')->withStatus(NewsletterEntity::STATUS_SENT)->create();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'type' => 'standard',
      'group' => 'all',
      'selection' => [],
      'select_all' => true,
      'filter' => ['status' => ['draft']],
    ]]);

    $this->assertIsArray($response);
    $this->assertSame(1, $this->getPayload($response)['count']);
    $this->newslettersRepository->refresh($draft);
    $this->newslettersRepository->refresh($sent);
    $this->assertNotNull($draft->getDeletedAt());
    $this->assertNull($sent->getDeletedAt());
  }

  public function testBulkTrashSelectAllHonoursPausedFilter(): void {
    $suffix = uniqid();
    $paused = $this->createSending(ScheduledTaskEntity::STATUS_PAUSED, "Paused_{$suffix}");
    $running = $this->createSending(ScheduledTaskEntity::STATUS_COMPLETED, "Running_{$suffix}");

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'type' => 'standard',
      'group' => 'all',
      'selection' => [],
      'select_all' => true,
      'filter' => ['status' => 'paused'],
    ]]);

    $this->assertIsArray($response);
    $this->assertSame(1, $this->getPayload($response)['count']);
    $this->newslettersRepository->refresh($paused);
    $this->newslettersRepository->refresh($running);
    $this->assertNotNull($paused->getDeletedAt());
    $this->assertNull($running->getDeletedAt());
  }

  public function testListingFiltersBySentDayRangeAndListMembership(): void {
    $suffix = uniqid();
    $listA = $this->createList("A_{$suffix}");
    $listB = $this->createList("B_{$suffix}");
    $sentInA = $this->createSentWithLists("In_A_{$suffix}", '2025-01-10 12:00:00', [$listA]);
    $sentInBoth = $this->createSentWithLists("In_both_{$suffix}", '2025-01-31 23:59:59', [$listA, $listB]);
    $sentOnlyB = $this->createSentWithLists("In_B_{$suffix}", '2025-01-20 12:00:00', [$listB]);
    $this->createSentWithLists("Too_late_{$suffix}", '2025-02-01 00:00:00', [$listA]);
    $scheduled = (new NewsletterFactory())->withSubject("Scheduled_{$suffix}")->withType('standard')
      ->withStatus(NewsletterEntity::STATUS_SCHEDULED)->withSegments([$listA])->create();

    $dates = ['sent_at_from' => '2025-01-01', 'sent_at_to' => '2025-01-31'];
    $isAny = ['segment_ids' => [(string)$listA->getId()], 'segment_operator' => 'isAny'];
    $isNone = ['segment_ids' => [(string)$listA->getId()], 'segment_operator' => 'isNone'];
    $ids = function(array $filter): array {
      $ids = $this->listIds(['type' => 'standard', 'filter' => $filter]);
      sort($ids);
      return $ids;
    };

    $this->assertSame([$sentInA->getId(), $sentInBoth->getId(), $sentOnlyB->getId()], array_values(array_intersect($ids($dates), [$sentInA->getId(), $sentInBoth->getId(), $sentOnlyB->getId(), $scheduled->getId()])));
    $this->assertNotContains($scheduled->getId(), $ids($dates));
    $this->assertSame([$sentInA->getId(), $sentInBoth->getId()], $ids($dates + $isAny));
    $this->assertSame([$sentOnlyB->getId()], $ids($dates + $isNone));
  }

  public function testListingReturnsListOptionsWithoutCountsForNotificationHistory(): void {
    $suffix = uniqid();
    $list = $this->createList("History_{$suffix}");
    $parent = (new NewsletterFactory())->withSubject("Parent_{$suffix}")->withPostNotificationsType()->create();
    (new NewsletterFactory())->withSubject("Child_{$suffix}")->withPostNotificationHistoryType()
      ->withParent($parent)->withStatus(NewsletterEntity::STATUS_SENT)->withSegments([$list])->create();

    $response = $this->get(self::LISTING_PATH, ['query' => ['type' => 'notification_history', 'parent_id' => $parent->getId()]]);
    $this->assertIsArray($response);
    $options = $this->getPayload($response)['filters']['segment'] ?? [];

    $this->assertSame(
      [['label' => 'All Lists', 'value' => ''], ['label' => "History_{$suffix}", 'value' => $list->getId()]],
      $options
    );
  }

  public function testListingReturnsStatusOptionsWithCounts(): void {
    $suffix = uniqid();
    $parent = (new NewsletterFactory())->withSubject("Parent_{$suffix}")->withPostNotificationsType()->create();
    (new NewsletterFactory())->withSubject("Sent_{$suffix}")->withPostNotificationHistoryType()
      ->withParent($parent)->withStatus(NewsletterEntity::STATUS_SENT)->create();
    $this->createSending(ScheduledTaskEntity::STATUS_PAUSED, "Paused_{$suffix}", 'notification_history')->setParent($parent);
    $this->entityManager->flush();

    $response = $this->get(self::LISTING_PATH, ['query' => ['type' => 'notification_history', 'parent_id' => $parent->getId()]]);
    $this->assertIsArray($response);
    $filters = $this->getPayload($response)['filters'] ?? [];

    $this->assertSame([
      ['label' => 'Sending (0)', 'value' => 'sending'],
      ['label' => 'Paused (1)', 'value' => 'paused'],
      ['label' => 'Sent (1)', 'value' => 'sent'],
    ], $filters['status'] ?? null);
    $this->assertSame('', $filters['segment'][0]['value'] ?? null);
  }

  public function testFilterSetsAreCappedAtOneHundredValues(): void {
    $statuses = array_fill(0, 101, 'draft');
    $response = $this->get(self::LISTING_PATH, ['query' => ['type' => 'standard', 'filter' => ['status' => $statuses]]]);
    $this->assertIsArray($response);
    $this->assertSame('mailpoet_newsletters_invalid_status', $response['code'] ?? null);
    $this->assertSame(400, $this->getErrorStatus($response));

    $ids = array_map('strval', range(1, 101));
    $response = $this->get(self::LISTING_PATH, ['query' => ['type' => 'standard', 'filter' => ['segment_ids' => $ids, 'segment_operator' => 'isAny']]]);
    $this->assertIsArray($response);
    $this->assertSame('mailpoet_newsletters_invalid_segment_ids', $response['code'] ?? null);
    $this->assertSame(400, $this->getErrorStatus($response));

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'type' => 'standard',
      'group' => 'all',
      'selection' => [],
      'select_all' => true,
      'filter' => ['segment_ids' => array_slice($ids, 0, 100), 'segment_operator' => 'isNone', 'status' => array_fill(0, 101, 'draft')],
    ]]);
    $this->assertIsArray($response);
    $this->assertSame('mailpoet_newsletters_invalid_status', $response['code'] ?? null);
  }

  public function testFilterSetsOfOneHundredValuesAreAccepted(): void {
    $ids = array_map('strval', range(1, 100));
    $response = $this->get(self::LISTING_PATH, ['query' => ['type' => 'standard', 'filter' => ['segment_ids' => $ids, 'segment_operator' => 'isAny']]]);
    $this->assertIsArray($response);
    $this->assertArrayNotHasKey('code', $response, (string)json_encode($response));
    $this->assertArrayHasKey('items', $this->getPayload($response));

    $response = $this->get(self::LISTING_PATH, ['query' => ['type' => 'standard', 'filter' => ['status' => array_fill(0, 100, 'draft')]]]);
    $this->assertIsArray($response);
    $this->assertArrayNotHasKey('code', $response, (string)json_encode($response));
  }

  private function createList(string $name): SegmentEntity {
    $list = new SegmentEntity($name, SegmentEntity::TYPE_DEFAULT, 'Description');
    $this->entityManager->persist($list);
    $this->entityManager->flush();
    return $list;
  }

  /** @param SegmentEntity[] $lists */
  private function createSentWithLists(string $subject, string $sentAtUtc, array $lists): NewsletterEntity {
    $newsletter = (new NewsletterFactory())->withSubject($subject)->withType('standard')
      ->withStatus(NewsletterEntity::STATUS_SENT)->withSegments($lists)->create();
    $newsletter->setSentAt(new \DateTime($sentAtUtc, new \DateTimeZone('UTC')));
    $this->entityManager->flush();
    return $newsletter;
  }

  private function createSending(string $taskStatus, string $subject, string $type = 'standard'): NewsletterEntity {
    return (new NewsletterFactory())
      ->withSubject($subject)
      ->withType($type)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue(['status' => $taskStatus])
      ->create();
  }

  /** @return int[] */
  private function listIds(array $query): array {
    $response = $this->get(self::LISTING_PATH, ['query' => array_merge(['per_page' => 100], $query)]);
    $this->assertIsArray($response);
    $this->assertArrayNotHasKey('code', $response, (string)json_encode($response));
    $items = $this->getPayload($response)['items'] ?? [];
    $this->assertIsArray($items);
    return array_map(function($id): int {
      return (int)$id;
    }, array_column($items, 'id'));
  }

  private function getErrorStatus(array $response): ?int {
    $data = $response['data'] ?? null;
    $status = is_array($data) ? ($data['status'] ?? null) : null;
    return is_int($status) ? $status : null;
  }

  private function getPayload(array $response): array {
    $data = $response['data'] ?? null;
    $this->assertIsArray($data);
    return $data;
  }

  public function testListingReturnsItemsForTypeFilter(): void {
    $suffix = uniqid();
    (new NewsletterFactory())
      ->withSubject("Standard_{$suffix}")
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();
    (new NewsletterFactory())
      ->withSubject("Notification_{$suffix}")
      ->withType(NewsletterEntity::TYPE_NOTIFICATION)
      ->create();

    $response = $this->get(self::LISTING_PATH, ['query' => [
      'per_page' => 100,
      'type' => NewsletterEntity::TYPE_STANDARD,
    ]]);
    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $items = $payload['items'];
    $this->assertIsArray($items);
    $subjects = array_column($items, 'subject');
    $this->assertContains("Standard_{$suffix}", $subjects);
    $this->assertNotContains("Notification_{$suffix}", $subjects);
  }

  public function testListingSortsByCreatedAtAndReturnsIt(): void {
    $suffix = uniqid();
    foreach (['2026-03-01 10:00:00', '2026-01-01 10:00:00', '2026-02-01 10:00:00'] as $createdAt) {
      $newsletter = (new NewsletterFactory())
        ->withSubject("Created_{$createdAt}_{$suffix}")
        ->withType(NewsletterEntity::TYPE_STANDARD)
        ->create();
      $newsletter->setCreatedAt(new \DateTimeImmutable($createdAt));
    }
    $this->entityManager->flush();
    $this->entityManager->clear();

    $response = $this->get(self::LISTING_PATH, ['query' => [
      'per_page' => 100,
      'type' => NewsletterEntity::TYPE_STANDARD,
      'search' => $suffix,
      'orderby' => 'created_at',
      'order' => 'asc',
    ]]);
    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $items = $payload['items'];
    $this->assertIsArray($items);
    $this->assertSame(
      [
        "Created_2026-01-01 10:00:00_{$suffix}",
        "Created_2026-02-01 10:00:00_{$suffix}",
        "Created_2026-03-01 10:00:00_{$suffix}",
      ],
      array_column($items, 'subject')
    );
    $this->assertSame(
      ['2026-01-01 10:00:00', '2026-02-01 10:00:00', '2026-03-01 10:00:00'],
      array_column($items, 'created_at')
    );
  }

  public function testListingCarriesMailerEnvelopeFields(): void {
    $response = $this->get(self::LISTING_PATH, ['query' => [
      'per_page' => 10,
      'type' => NewsletterEntity::TYPE_STANDARD,
    ]]);
    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $this->assertArrayHasKey('mta_log', $payload);
    $this->assertArrayHasKey('mta_method', $payload);
    $this->assertArrayHasKey('cron_accessible', $payload);
    $this->assertArrayHasKey('current_time', $payload);
  }

  public function testListingReturnsStandardStatusGroups(): void {
    (new NewsletterFactory())
      ->withSubject('Draft_' . uniqid())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withDraftStatus()
      ->create();
    (new NewsletterFactory())
      ->withSubject('Scheduled_' . uniqid())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withScheduledStatus()
      ->create();
    (new NewsletterFactory())
      ->withSubject('Sent_' . uniqid())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withSentStatus()
      ->create();

    $response = $this->get(self::LISTING_PATH, ['query' => [
      'per_page' => 10,
      'type' => NewsletterEntity::TYPE_STANDARD,
    ]]);

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $groupsList = $payload['groups'];
    $this->assertIsArray($groupsList);
    $groups = array_column($groupsList, 'count', 'name');

    $this->assertArrayHasKey('all', $groups);
    $this->assertArrayHasKey(NewsletterEntity::STATUS_DRAFT, $groups);
    $this->assertArrayHasKey(NewsletterEntity::STATUS_SCHEDULED, $groups);
    $this->assertArrayHasKey(NewsletterEntity::STATUS_SENDING, $groups);
    $this->assertArrayHasKey(NewsletterEntity::STATUS_SENT, $groups);
    $this->assertArrayHasKey('trash', $groups);
  }

  public function testBulkTrashMovesSelectionToTrash(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Trash_' . uniqid())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();
    $id = (int)$newsletter->getId();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'type' => NewsletterEntity::TYPE_STANDARD,
      'selection' => [$id],
    ]]);

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $this->assertSame('trash', $payload['action']);
    $this->assertSame(1, $payload['count']);

    $this->newslettersRepository->refresh($newsletter);
    $this->assertNotNull($newsletter->getDeletedAt());
  }

  public function testBulkTrashCanSelectAllFilteredNewsletters(): void {
    $suffix = uniqid();
    $firstNewsletter = (new NewsletterFactory())
      ->withSubject("BulkAll_{$suffix}_1")
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();
    $secondNewsletter = (new NewsletterFactory())
      ->withSubject("BulkAll_{$suffix}_2")
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();
    $unmatchedNewsletter = (new NewsletterFactory())
      ->withSubject("Keep_{$suffix}")
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'type' => NewsletterEntity::TYPE_STANDARD,
      'group' => 'all',
      'search' => "BulkAll_{$suffix}",
      'selection' => [],
      'select_all' => true,
    ]]);

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $this->assertSame('trash', $payload['action']);
    $this->assertSame(2, $payload['count']);

    $this->newslettersRepository->refresh($firstNewsletter);
    $this->newslettersRepository->refresh($secondNewsletter);
    $this->newslettersRepository->refresh($unmatchedNewsletter);
    $this->assertNotNull($firstNewsletter->getDeletedAt());
    $this->assertNotNull($secondNewsletter->getDeletedAt());
    $this->assertNull($unmatchedNewsletter->getDeletedAt());
  }

  public function testBulkActionRejectsEmptySelection(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Keep_' . uniqid())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'type' => NewsletterEntity::TYPE_STANDARD,
      'selection' => [],
    ]]);

    $this->assertIsArray($response);
    $this->assertSame('mailpoet_newsletters_missing_selection', $response['code']);
    $errorData = $response['data'];
    $this->assertIsArray($errorData);
    $this->assertSame(400, $errorData['status']);

    $this->newslettersRepository->refresh($newsletter);
    $this->assertNull($newsletter->getDeletedAt());
  }

  public function testBulkActionRejectsMissingSelection(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Keep_' . uniqid())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'type' => NewsletterEntity::TYPE_STANDARD,
    ]]);

    $this->assertIsArray($response);
    $this->assertSame('mailpoet_newsletters_missing_selection', $response['code']);
    $errorData = $response['data'];
    $this->assertIsArray($errorData);
    $this->assertSame(400, $errorData['status']);

    $this->newslettersRepository->refresh($newsletter);
    $this->assertNull($newsletter->getDeletedAt());
  }

  public function testBulkActionWithUnknownActionReturnsError(): void {
    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'pretend-this-is-real',
      'type' => NewsletterEntity::TYPE_STANDARD,
      'selection' => [1],
    ]]);

    $this->assertIsArray($response);
    $this->assertSame('mailpoet_newsletters_invalid_bulk_action', $response['code']);
    $errorData = $response['data'];
    $this->assertIsArray($errorData);
    $this->assertSame(400, $errorData['status']);
  }

  public function testBulkDeleteIgnoresConfirmationEmailInSelection(): void {
    $standard = (new NewsletterFactory())
      ->withSubject('BulkStandard_' . uniqid())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();
    $confirmationEmail = (new NewsletterFactory())
      ->withSubject('BulkConfirmation_' . uniqid())
      ->withType(NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER)
      ->create();
    $standardId = (int)$standard->getId();
    $confirmationEmailId = (int)$confirmationEmail->getId();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'delete',
      'type' => NewsletterEntity::TYPE_STANDARD,
      'selection' => [$standardId, $confirmationEmailId],
    ]]);

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $this->assertSame(1, $payload['count']);

    $this->assertNull($this->newslettersRepository->findOneById($standardId));
    $this->assertInstanceOf(
      NewsletterEntity::class,
      $this->newslettersRepository->findOneById($confirmationEmailId)
    );
  }

  public function testStatusEndpointRejectsEmptyStatus(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Status_' . uniqid())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();

    // An absent `status` is rejected by the schema (`required()`); an empty
    // string passes schema validation and is caught by the handler's guard.
    $response = $this->put('/mailpoet/v1/newsletters/' . $newsletter->getId() . '/status', [
      'json' => ['status' => ''],
    ]);
    $this->assertIsArray($response);
    $this->assertSame('mailpoet_newsletters_missing_status', $response['code']);
  }

  public function testDuplicateEndpointCreatesACopy(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Original_' . uniqid())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();

    $response = $this->post(
      '/mailpoet/v1/newsletters/' . $newsletter->getId() . '/duplicate',
      ['json' => []]
    );

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $duplicateId = $payload['id'];
    $this->assertIsString($duplicateId);
    $this->assertNotSame((string)$newsletter->getId(), $duplicateId);
    $this->assertIsString($payload['subject']);

    $duplicate = $this->newslettersRepository->findOneById((int)$duplicateId);
    $this->assertInstanceOf(NewsletterEntity::class, $duplicate);
    $this->assertSame(NewsletterEntity::TYPE_STANDARD, $duplicate->getType());
  }

  public function testDuplicateEndpointReturnsNotFoundForUnknownId(): void {
    $response = $this->post('/mailpoet/v1/newsletters/9999999/duplicate', ['json' => []]);
    $this->assertIsArray($response);
    $this->assertSame('mailpoet_newsletters_not_found', $response['code']);
  }

  public function testStatusEndpointUpdatesNewsletterStatus(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Status_' . uniqid())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withSentStatus()
      ->create();

    $response = $this->put('/mailpoet/v1/newsletters/' . $newsletter->getId() . '/status', [
      'json' => ['status' => NewsletterEntity::STATUS_DRAFT],
    ]);

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $this->assertSame(NewsletterEntity::STATUS_DRAFT, $payload['status']);

    $this->newslettersRepository->refresh($newsletter);
    $this->assertSame(NewsletterEntity::STATUS_DRAFT, $newsletter->getStatus());
  }

  public function testStatusEndpointUpdatesNewsletterStatusWithPostRequest(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Status_' . uniqid())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withSentStatus()
      ->create();

    $response = $this->post('/mailpoet/v1/newsletters/' . $newsletter->getId() . '/status', [
      'json' => ['status' => NewsletterEntity::STATUS_DRAFT],
    ]);

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $this->assertSame(NewsletterEntity::STATUS_DRAFT, $payload['status']);

    $this->newslettersRepository->refresh($newsletter);
    $this->assertSame(NewsletterEntity::STATUS_DRAFT, $newsletter->getStatus());
  }

  public function testStatusEndpointReturnsNotFoundForUnknownId(): void {
    $response = $this->put('/mailpoet/v1/newsletters/9999999/status', [
      'json' => ['status' => NewsletterEntity::STATUS_DRAFT],
    ]);
    $this->assertIsArray($response);
    $this->assertSame('mailpoet_newsletters_not_found', $response['code']);
  }

  public function testExportStatsIsForbiddenWithoutDetailedAnalytics(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Export_' . uniqid())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withSentStatus()
      ->create();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'export_stats',
      'type' => NewsletterEntity::TYPE_STANDARD,
      'selection' => [(int)$newsletter->getId()],
    ]]);

    // Detailed analytics is a premium capability; the free test environment
    // has no premium key, so the bulk export is rejected before it queues.
    $this->assertIsArray($response);
    $this->assertSame('mailpoet_newsletters_export_forbidden', $response['code']);
    $errorData = $response['data'];
    $this->assertIsArray($errorData);
    $this->assertSame(403, $errorData['status']);
  }
}
