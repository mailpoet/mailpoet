<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\Logs;

use MailPoet\REST\Test;
use MailPoet\Test\DataFactories\Log as LogFactory;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/../Test.php';

class LogsEndpointsTest extends Test {
  private const BASE_PATH = '/mailpoet/v1/logs';

  /** @var int */
  private $editorUserId;

  public function _before() {
    parent::_before();
    wp_set_current_user(1);
    $userId = wp_create_user('logs_subscriber_' . uniqid(), 'password', 'logs-subscriber-' . uniqid() . '@localhost.test');
    $this->assertIsNumeric($userId);
    $user = new \WP_User($userId);
    $user->add_role('subscriber');
    $this->editorUserId = (int)$userId;
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
    is_multisite() ? wpmu_delete_user($this->editorUserId) : wp_delete_user($this->editorUserId);
  }

  public function testGetReturnsLogsEnvelopeAndNormalizesNullableFields(): void {
    $suffix = uniqid();
    $logWithNullName = (new LogFactory())
      ->withName(null)
      ->withMessage("nullable-name-{$suffix}")
      ->create();
    $logWithNullMessage = (new LogFactory())
      ->withName("nullable-message-{$suffix}")
      ->withMessage(null)
      ->create();

    $data = $this->get(self::BASE_PATH, ['query' => [
      'search' => $suffix,
      'per_page' => 100,
    ]]);

    $this->assertIsArray($data['data']);
    $this->assertArrayHasKey('items', $data['data']);
    $this->assertArrayHasKey('meta', $data['data']);
    $this->assertArrayHasKey('filters', $data['data']);
    $this->assertArrayHasKey('groups', $data['data']);
    $this->assertSame([], $data['data']['filters']);
    $this->assertSame([], $data['data']['groups']);
    $this->assertSame(2, $data['data']['meta']['count']);
    $this->assertSame(1, $data['data']['meta']['pages']);

    $itemsById = [];
    foreach ($data['data']['items'] as $item) {
      $itemsById[(int)$item['id']] = $item;
    }
    $this->assertSame('', $itemsById[(int)$logWithNullName->getId()]['name']);
    $this->assertSame("nullable-name-{$suffix}", $itemsById[(int)$logWithNullName->getId()]['message']);
    $this->assertSame("nullable-message-{$suffix}", $itemsById[(int)$logWithNullMessage->getId()]['name']);
    $this->assertSame('', $itemsById[(int)$logWithNullMessage->getId()]['message']);
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $itemsById[(int)$logWithNullName->getId()]['created_at']);
  }

  public function testGetSearchesNameAndMessageWithLiteralWildcards(): void {
    $suffix = uniqid();
    $percent = (new LogFactory())->withName("literal%{$suffix}")->withMessage('plain')->create();
    $underscore = (new LogFactory())->withName('plain')->withMessage("literal_{$suffix}")->create();
    $backslash = (new LogFactory())->withName("literal\\{$suffix}")->withMessage('plain')->create();
    (new LogFactory())->withName("literalA{$suffix}")->withMessage("literalB{$suffix}")->create();

    $percentIds = $this->getIdsForSearch("literal%{$suffix}");
    $underscoreIds = $this->getIdsForSearch("literal_{$suffix}");
    $backslashIds = $this->getIdsForSearch(wp_slash("literal\\{$suffix}"));

    $this->assertSame([(int)$percent->getId()], $percentIds);
    $this->assertSame([(int)$underscore->getId()], $underscoreIds);
    $this->assertSame([(int)$backslash->getId()], $backslashIds);
  }

  public function testGetSearchesZeroAsLiteralTerm(): void {
    $match = (new LogFactory())->withName('0')->withMessage('matching zero log')->create();
    $nonMatch = (new LogFactory())->withName('one')->withMessage('matching one log')->create();

    $ids = $this->getIdsForSearch('0');

    $this->assertContains((int)$match->getId(), $ids);
    $this->assertNotContains((int)$nonMatch->getId(), $ids);
  }

  public function testGetAppliesInclusiveDateFiltersAndNewestFirstOrdering(): void {
    $suffix = uniqid();
    $before = (new LogFactory())
      ->withName("date-before-{$suffix}")
      ->withCreatedAt(new Carbon('2025-01-09 23:59:59'))
      ->create();
    $start = (new LogFactory())
      ->withName("date-start-{$suffix}")
      ->withCreatedAt(new Carbon('2025-01-10 00:00:00'))
      ->create();
    $end = (new LogFactory())
      ->withName("date-end-{$suffix}")
      ->withCreatedAt(new Carbon('2025-01-10 23:59:59'))
      ->create();
    $after = (new LogFactory())
      ->withName("date-after-{$suffix}")
      ->withCreatedAt(new Carbon('2025-01-11 00:00:00'))
      ->create();

    $data = $this->get(self::BASE_PATH, ['query' => [
      'search' => $suffix,
      'filter' => [
        'from' => '2025-01-10',
        'to' => '2025-01-10',
      ],
      'per_page' => 100,
    ]]);

    $ids = array_map('intval', array_column($data['data']['items'], 'id'));
    $this->assertSame([(int)$end->getId(), (int)$start->getId()], $ids);
    $this->assertNotContains((int)$before->getId(), $ids);
    $this->assertNotContains((int)$after->getId(), $ids);
  }

  public function testGetIgnoresEmptyDateFilters(): void {
    $suffix = uniqid();
    $old = (new LogFactory())
      ->withName("empty-date-old-{$suffix}")
      ->withCreatedAt(new Carbon('2025-01-01 00:00:00'))
      ->create();
    $new = (new LogFactory())
      ->withName("empty-date-new-{$suffix}")
      ->withCreatedAt(new Carbon('2025-01-02 00:00:00'))
      ->create();

    $data = $this->get(self::BASE_PATH, ['query' => [
      'search' => $suffix,
      'filter' => [
        'from' => '',
        'to' => '',
      ],
      'per_page' => 100,
    ]]);

    $ids = array_map('intval', array_column($data['data']['items'], 'id'));
    $this->assertSame([(int)$new->getId(), (int)$old->getId()], $ids);
  }

  public function testGetSupportsCanonicalAndLegacyPagination(): void {
    $suffix = uniqid();
    $oldest = (new LogFactory())->withName("pagination-oldest-{$suffix}")->withCreatedAt(new Carbon('2025-02-01 00:00:00'))->create();
    $middle = (new LogFactory())->withName("pagination-middle-{$suffix}")->withCreatedAt(new Carbon('2025-02-02 00:00:00'))->create();
    $newest = (new LogFactory())->withName("pagination-newest-{$suffix}")->withCreatedAt(new Carbon('2025-02-03 00:00:00'))->create();

    $pageTwo = $this->get(self::BASE_PATH, ['query' => [
      'search' => $suffix,
      'page' => 2,
      'per_page' => 2,
    ]]);

    $this->assertSame(3, $pageTwo['data']['meta']['count']);
    $this->assertSame(2, $pageTwo['data']['meta']['pages']);
    $this->assertSame([(int)$oldest->getId()], array_map('intval', array_column($pageTwo['data']['items'], 'id')));

    $legacy = $this->get(self::BASE_PATH, ['query' => [
      'search' => $suffix,
      'offset' => 1,
      'limit' => 1,
    ]]);

    $this->assertSame([(int)$middle->getId()], array_map('intval', array_column($legacy['data']['items'], 'id')));
    $this->assertSame(3, $legacy['data']['meta']['count']);
    $this->assertSame(3, $legacy['data']['meta']['pages']);
    $this->assertNotSame((int)$newest->getId(), (int)$oldest->getId());
  }

  public function testGetDefaultsEmptySortOrderToNewestFirst(): void {
    $suffix = uniqid();
    $old = (new LogFactory())->withName("empty-order-old-{$suffix}")->withCreatedAt(new Carbon('2025-03-01 00:00:00'))->create();
    $new = (new LogFactory())->withName("empty-order-new-{$suffix}")->withCreatedAt(new Carbon('2025-03-02 00:00:00'))->create();

    $order = $this->get(self::BASE_PATH, ['query' => [
      'search' => $suffix,
      'order' => '',
      'per_page' => 100,
    ]]);
    $sortOrder = $this->get(self::BASE_PATH, ['query' => [
      'search' => $suffix,
      'sort_order' => '',
      'per_page' => 100,
    ]]);

    $expectedIds = [(int)$new->getId(), (int)$old->getId()];
    $this->assertSame($expectedIds, array_map('intval', array_column($order['data']['items'], 'id')));
    $this->assertSame($expectedIds, array_map('intval', array_column($sortOrder['data']['items'], 'id')));
  }

  public function testGetUsesStableOrderingForLogsWithSameTimestamp(): void {
    $suffix = uniqid();
    $createdAt = new Carbon('2025-03-03 00:00:00');
    $first = (new LogFactory())->withName("same-time-first-{$suffix}")->withCreatedAt($createdAt)->create();
    $second = (new LogFactory())->withName("same-time-second-{$suffix}")->withCreatedAt($createdAt)->create();
    $third = (new LogFactory())->withName("same-time-third-{$suffix}")->withCreatedAt($createdAt)->create();

    $pageOne = $this->get(self::BASE_PATH, ['query' => [
      'search' => $suffix,
      'page' => 1,
      'per_page' => 2,
    ]]);
    $pageTwo = $this->get(self::BASE_PATH, ['query' => [
      'search' => $suffix,
      'page' => 2,
      'per_page' => 2,
    ]]);

    $this->assertSame([(int)$third->getId(), (int)$second->getId()], array_map('intval', array_column($pageOne['data']['items'], 'id')));
    $this->assertSame([(int)$first->getId()], array_map('intval', array_column($pageTwo['data']['items'], 'id')));
  }

  public function testGetRejectsInvalidListingParams(): void {
    $this->assertSame('mailpoet_logs_invalid_orderby', $this->get(self::BASE_PATH, ['query' => ['orderby' => 'name']])['code']);
    $this->assertSame('mailpoet_logs_invalid_orderby', $this->get(self::BASE_PATH, ['query' => ['sort_by' => 'name']])['code']);
    $this->assertSame('mailpoet_logs_invalid_order', $this->get(self::BASE_PATH, ['query' => ['order' => 'asc']])['code']);
    $this->assertSame('mailpoet_logs_invalid_order', $this->get(self::BASE_PATH, ['query' => ['sort_order' => 'asc']])['code']);
    $this->assertSame('mailpoet_logs_invalid_page', $this->get(self::BASE_PATH, ['query' => ['page' => 0]])['code']);
    $this->assertSame('mailpoet_logs_invalid_per_page', $this->get(self::BASE_PATH, ['query' => ['per_page' => 101]])['code']);
    $this->assertSame('mailpoet_logs_invalid_limit', $this->get(self::BASE_PATH, ['query' => ['limit' => 0]])['code']);
    $this->assertSame('mailpoet_logs_invalid_offset', $this->get(self::BASE_PATH, ['query' => ['offset' => 100001]])['code']);
  }

  public function testGetRejectsInvalidDateFilters(): void {
    $this->assertSame('mailpoet_logs_invalid_from', $this->get(self::BASE_PATH, ['query' => ['filter' => ['from' => '2025-02-30']]])['code']);
    $this->assertSame('mailpoet_logs_invalid_to', $this->get(self::BASE_PATH, ['query' => ['filter' => ['to' => '2025-2-01']]])['code']);
    $this->assertSame('mailpoet_logs_invalid_date_range', $this->get(self::BASE_PATH, ['query' => ['filter' => ['from' => '2025-02-02', 'to' => '2025-02-01']]])['code']);
    $this->assertSame('mailpoet_logs_invalid_filter', $this->get(self::BASE_PATH, ['query' => ['filter' => ['level' => 'error']]])['code']);
  }

  public function testGetRejectsUsersWithoutPermission(): void {
    wp_set_current_user($this->editorUserId);

    $data = $this->get(self::BASE_PATH);
    $this->assertSame('rest_forbidden', $data['code']);
  }

  /** @return int[] */
  private function getIdsForSearch(string $search): array {
    $data = $this->get(self::BASE_PATH, ['query' => [
      'search' => $search,
      'per_page' => 100,
    ]]);

    return array_map('intval', array_column($data['data']['items'], 'id'));
  }
}
