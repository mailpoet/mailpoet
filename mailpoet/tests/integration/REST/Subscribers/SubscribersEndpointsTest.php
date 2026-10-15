<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\Subscribers;

use DateTimeImmutable;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\REST\Test;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberTextField;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Test\DataFactories\Tag as TagFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\EntityManager;

require_once __DIR__ . '/../Test.php';

/**
 * Covers the contract surface of the subscribers REST endpoints. Per-action
 * behaviour for the bulk dispatcher lives in
 * {@see \MailPoet\Subscribers\BulkActionControllerTest}; this file only
 * asserts that the HTTP layer wires those services and returns the expected
 * envelope shape.
 */
class SubscribersEndpointsTest extends Test {
  private const LISTING_PATH = '/mailpoet/v1/subscribers';
  private const BULK_ACTION_PATH = '/mailpoet/v1/subscribers/bulk-action';

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    wp_set_current_user(1);
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
  }

  public function testListingReturnsItemsMetaAndGroups(): void {
    $suffix = uniqid();
    (new SubscriberFactory())
      ->withEmail("rest-listing-{$suffix}@example.com")
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    $response = $this->get(self::LISTING_PATH, ['query' => ['per_page' => 100]]);
    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $items = $payload['items'];
    $this->assertIsArray($items);
    $meta = $payload['meta'];
    $this->assertIsArray($meta);
    $this->assertArrayHasKey('count', $meta);
    $this->assertArrayHasKey('pages', $meta);
    $groupsList = $payload['groups'];
    $this->assertIsArray($groupsList);
    $groups = array_column($groupsList, 'count', 'name');
    $this->assertArrayHasKey('all', $groups);
    $this->assertArrayHasKey('trash', $groups);
    $this->assertCount(2, $groups);

    $emails = array_column($items, 'email');
    $this->assertContains("rest-listing-{$suffix}@example.com", $emails);
  }

  public function testListingFiltersBySearch(): void {
    $suffix = uniqid();
    (new SubscriberFactory())
      ->withEmail("rest-search-needle-{$suffix}@example.com")
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    (new SubscriberFactory())
      ->withEmail("rest-search-other-{$suffix}@example.com")
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    $response = $this->get(self::LISTING_PATH, ['query' => [
      'per_page' => 100,
      'search' => "needle-{$suffix}",
    ]]);
    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $items = $payload['items'];
    $this->assertIsArray($items);
    $emails = array_column($items, 'email');
    $this->assertContains("rest-search-needle-{$suffix}@example.com", $emails);
    $this->assertNotContains("rest-search-other-{$suffix}@example.com", $emails);
  }

  public function testBulkTrashMovesSelectionToTrash(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('rest-bulk-trash-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $id = (int)$subscriber->getId();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'group' => 'all',
      'selection' => [$id],
    ]]);

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $this->assertSame('trash', $payload['action']);
    $this->assertSame(1, $payload['count']);
    $this->assertNull($payload['segment']);
    $this->assertNull($payload['tag']);

    $this->subscribersRepository->refresh($subscriber);
    $this->assertNotNull($subscriber->getDeletedAt());
  }

  public function testBulkAutomationHooksRequireExplicitOptIn(): void {
    $segment = (new SegmentFactory())->withName('REST Bulk Automation Opt-In')->create();
    $omitted = (new SubscriberFactory())
      ->withEmail('rest-bulk-automation-omitted-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $disabled = (new SubscriberFactory())
      ->withEmail('rest-bulk-automation-disabled-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $enabled = (new SubscriberFactory())
      ->withEmail('rest-bulk-automation-enabled-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    $wp = new WPFunctions();
    $wp->removeAllActions('mailpoet_segment_subscribed');
    $firedFor = [];
    $wp->addAction('mailpoet_segment_subscribed', function (SubscriberSegmentEntity $subscriberSegment) use (&$firedFor): void {
      $subscriber = $subscriberSegment->getSubscriber();
      $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
      $firedFor[] = (int)$subscriber->getId();
    });

    $baseRequest = [
      'action' => 'addToList',
      'group' => 'subscribed',
      'segment_id' => (int)$segment->getId(),
    ];
    $omittedResponse = $this->post(self::BULK_ACTION_PATH, ['json' => $baseRequest + [
      'selection' => [(int)$omitted->getId()],
    ]]);
    $disabledResponse = $this->post(self::BULK_ACTION_PATH, ['json' => $baseRequest + [
      'selection' => [(int)$disabled->getId()],
      'trigger_automations' => false,
    ]]);
    $enabledResponse = $this->post(self::BULK_ACTION_PATH, ['json' => $baseRequest + [
      'selection' => [(int)$enabled->getId()],
      'trigger_automations' => true,
    ]]);

    $this->assertSame(1, $omittedResponse['data']['count']);
    $this->assertSame(1, $disabledResponse['data']['count']);
    $this->assertSame(1, $enabledResponse['data']['count']);
    $this->assertSame([(int)$enabled->getId()], $firedFor);
    $wp->removeAllActions('mailpoet_segment_subscribed');
  }

  public function testBulkActionWithEmptySelectionAndNoSelectAllReturnsError(): void {
    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'group' => 'all',
      'selection' => [],
    ]]);

    $this->assertIsArray($response);
    $this->assertSame('mailpoet_subscribers_no_selection', $response['code']);
    $errorData = $response['data'];
    $this->assertIsArray($errorData);
    $this->assertSame(400, $errorData['status']);
  }

  public function testBulkActionWithSelectAllTrashesAllMatchingInGroup(): void {
    $suffix = uniqid();
    $first = (new SubscriberFactory())
      ->withEmail("rest-select-all-1-{$suffix}@example.com")
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $second = (new SubscriberFactory())
      ->withEmail("rest-select-all-2-{$suffix}@example.com")
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'group' => 'subscribed',
      'selection' => [],
      'select_all' => true,
    ]]);

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $this->assertSame('trash', $payload['action']);
    $this->assertGreaterThanOrEqual(2, $payload['count']);

    $this->subscribersRepository->refresh($first);
    $this->subscribersRepository->refresh($second);
    $this->assertNotNull($first->getDeletedAt());
    $this->assertNotNull($second->getDeletedAt());
  }

  public function testBulkActionWithSelectAllRequiresValidGroup(): void {
    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'selection' => [],
      'select_all' => true,
    ]]);

    $this->assertIsArray($response);
    $this->assertSame('mailpoet_subscribers_invalid_select_all_group', $response['code']);
    $errorData = $response['data'];
    $this->assertIsArray($errorData);
    $this->assertSame(400, $errorData['status']);
  }

  public function testBulkDeleteWithSelectAllIsRejectedOutsideTrash(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('rest-select-all-delete-live-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $subscriberId = (int)$subscriber->getId();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'delete',
      'group' => 'all',
      'selection' => [],
      'select_all' => true,
    ]]);

    $this->assertIsArray($response);
    $this->assertSame('mailpoet_subscribers_invalid_select_all_scope', $response['code']);
    $errorData = $response['data'];
    $this->assertIsArray($errorData);
    $this->assertSame(400, $errorData['status']);
    $this->assertInstanceOf(SubscriberEntity::class, $this->subscribersRepository->findOneById($subscriberId));
  }

  public function testBulkDeleteWithSelectAllIgnoresSubmittedSelection(): void {
    $live = (new SubscriberFactory())
      ->withEmail('rest-select-all-delete-selection-live-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $trashed = (new SubscriberFactory())
      ->withEmail('rest-select-all-delete-selection-trash-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withDeletedAt(new \DateTimeImmutable())
      ->create();
    $liveId = (int)$live->getId();
    $trashedId = (int)$trashed->getId();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'delete',
      'group' => 'trash',
      'selection' => [$liveId],
      'select_all' => true,
    ]]);

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $this->assertSame('delete', $payload['action']);
    $this->assertGreaterThanOrEqual(1, $payload['count']);
    $this->entityManager->clear();

    $liveSubscriber = $this->subscribersRepository->findOneById($liveId);
    $this->assertInstanceOf(SubscriberEntity::class, $liveSubscriber);
    $this->assertNull($liveSubscriber->getDeletedAt());
    $this->assertNull($this->subscribersRepository->findOneById($trashedId));
  }

  public function testBulkDeleteReturnsKeptCountForLinkedSubscribers(): void {
    $deletable = (new SubscriberFactory())
      ->withEmail('rest-empty-trash-plain-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->withDeletedAt(new \DateTimeImmutable())
      ->create();
    $wpUser = (new SubscriberFactory())
      ->withEmail('rest-empty-trash-wp-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->withWpUserId(1)
      ->withDeletedAt(new \DateTimeImmutable())
      ->create();
    $deletableId = (int)$deletable->getId();
    $wpUserId = (int)$wpUser->getId();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'delete',
      'group' => 'trash',
      'selection' => [],
      'select_all' => true,
    ]]);

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $this->assertArrayHasKey('kept', $payload);
    $this->assertGreaterThanOrEqual(1, $payload['count']);
    $this->assertGreaterThanOrEqual(1, $payload['kept']);
    $this->entityManager->clear();
    $this->assertNull($this->subscribersRepository->findOneById($deletableId));
    $this->assertInstanceOf(
      SubscriberEntity::class,
      $this->subscribersRepository->findOneById($wpUserId)
    );
  }

  public function testBulkTrashWithSelectAllIsRejectedFromTrash(): void {
    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'group' => 'trash',
      'selection' => [],
      'select_all' => true,
    ]]);

    $this->assertIsArray($response);
    $this->assertSame('mailpoet_subscribers_invalid_select_all_scope', $response['code']);
    $errorData = $response['data'];
    $this->assertIsArray($errorData);
    $this->assertSame(400, $errorData['status']);
  }

  public function testBulkActionWithExplicitSelectionIgnoresSelectAllFlagWhenAbsent(): void {
    $kept = (new SubscriberFactory())
      ->withEmail('rest-explicit-keep-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $trashed = (new SubscriberFactory())
      ->withEmail('rest-explicit-trash-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'group' => 'all',
      'selection' => [(int)$trashed->getId()],
    ]]);

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $this->assertSame(1, $payload['count']);

    $this->subscribersRepository->refresh($kept);
    $this->assertNull($kept->getDeletedAt());
  }

  public function testBulkActionWithUnknownActionReturnsError(): void {
    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'pretend-this-is-real',
      'group' => 'all',
      'selection' => [1],
    ]]);

    $this->assertIsArray($response);
    $this->assertSame('mailpoet_subscribers_invalid_bulk_action', $response['code']);
    $errorData = $response['data'];
    $this->assertIsArray($errorData);
    $this->assertSame(400, $errorData['status']);
  }

  public function testBulkResendConfirmationOutsideUnconfirmedReturnsError(): void {
    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'resendConfirmationEmails',
      'group' => 'all',
      'selection' => [1],
    ]]);

    $this->assertIsArray($response);
    $this->assertSame('mailpoet_subscribers_invalid_group', $response['code']);
    $errorData = $response['data'];
    $this->assertIsArray($errorData);
    $this->assertSame(400, $errorData['status']);
  }

  public function testBulkResendConfirmationWithOtherStatusFilterReturnsError(): void {
    foreach ([['subscribed'], ['unconfirmed', 'subscribed'], []] as $statuses) {
      $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
        'action' => 'resendConfirmationEmails',
        'selection' => [1],
        'filter' => ['status_in' => $statuses],
      ]]);
      $error = $this->asError($response);
      $this->assertSame('mailpoet_subscribers_invalid_group', $error['code']);
      $this->assertSame(400, $error['data']['status']);
    }
    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'resendConfirmationEmails',
      'group' => 'trash',
      'selection' => [1],
      'filter' => ['status_in' => ['unconfirmed']],
    ]]);
    $this->assertSame('mailpoet_subscribers_invalid_group', $this->asError($response)['code']);
  }

  public function testBulkResendConfirmationWithStatusFilterQueuesOnlyUnconfirmed(): void {
    $this->diContainer->get(SettingsController::class)->set('signup_confirmation.enabled', true);
    $segment = (new SegmentFactory())->withName('REST Resend Status Filter ' . uniqid())->create();
    $unconfirmed = (new SubscriberFactory())
      ->withEmail('rest-resend-status-1-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withSegments([$segment])
      ->create();
    $subscribed = (new SubscriberFactory())
      ->withEmail('rest-resend-status-2-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();

    $selectAll = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'resendConfirmationEmails',
      'group' => 'all',
      'select_all' => true,
      'selection' => [],
      'filter' => ['status_in' => ['unconfirmed'], 'segment_in' => [$segment->getId()]],
    ]]);
    $this->assertSame(1, $this->bulkPayload($selectAll)['queue']['queued_count']);

    $selection = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'resendConfirmationEmails',
      'selection' => [(int)$unconfirmed->getId(), (int)$subscribed->getId()],
      'filter' => ['status_in' => ['unconfirmed']],
    ]]);
    $this->assertSame(1, $this->bulkPayload($selection)['queue']['queued_count']);
  }

  public function testBulkResendConfirmationWithLegacyGroupAndOtherStatusFilterQueuesNothing(): void {
    $this->diContainer->get(SettingsController::class)->set('signup_confirmation.enabled', true);
    $segment = (new SegmentFactory())->withName('REST Resend Legacy Group ' . uniqid())->create();
    (new SubscriberFactory())
      ->withEmail('rest-resend-legacy-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withSegments([$segment])
      ->create();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'resendConfirmationEmails',
      'group' => 'unconfirmed',
      'select_all' => true,
      'selection' => [],
      'filter' => ['status_in' => ['subscribed'], 'segment_in' => [$segment->getId()]],
    ]]);
    $this->assertSame(0, $this->bulkPayload($response)['queue']['queued_count']);
  }

  public function testBulkResendConfirmationWithSelectAllQueuesAllMatching(): void {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('signup_confirmation.enabled', true);

    $suffix = uniqid();
    (new SubscriberFactory())
      ->withEmail("rest-resend-select-all-1-{$suffix}@example.com")
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();
    (new SubscriberFactory())
      ->withEmail("rest-resend-select-all-2-{$suffix}@example.com")
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'resendConfirmationEmails',
      'group' => 'unconfirmed',
      'selection' => [],
      'select_all' => true,
    ]]);

    $this->assertIsArray($response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    $this->assertSame('resendConfirmationEmails', $payload['action']);
    $queue = $payload['queue'];
    $this->assertIsArray($queue);
    $this->assertArrayHasKey('queued_count', $queue);
    $this->assertGreaterThanOrEqual(2, $queue['queued_count']);
  }

  public function testBulkResendConfirmationWithoutSelectionOrSelectAllReturnsError(): void {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('signup_confirmation.enabled', true);

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'resendConfirmationEmails',
      'group' => 'unconfirmed',
      'selection' => [],
    ]]);

    $this->assertIsArray($response);
    $this->assertSame('mailpoet_subscribers_no_selection', $response['code']);
    $errorData = $response['data'];
    $this->assertIsArray($errorData);
    $this->assertSame(400, $errorData['status']);
  }

  public function testResendConfirmationReturnsDisabledErrorWhenSignupConfirmationIsOff(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('rest-resend-confirmation-disabled-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('signup_confirmation.enabled', false);

    $response = $this->post(self::LISTING_PATH . '/' . $subscriber->getId() . '/resend-confirmation-email');

    $settings->set('signup_confirmation.enabled', true);
    $this->assertSame('mailpoet_subscribers_confirmation_disabled', $response['code']);
    $this->assertSame(400, $response['data']['status']);
  }

  public function testListingAppliesNewFilterKeys(): void {
    $data = $this->createFilterData();
    $sfx = $data['suffix'];

    $this->assertSame([$data['a'], $data['b']], $this->listingEmails($sfx, ['status_in' => ['subscribed', 'unconfirmed']]));
    $this->assertSame([$data['c']], $this->listingEmails($sfx, ['status_not_in' => ['subscribed', 'unconfirmed']]));
    $this->assertSame([$data['b']], $this->listingEmails($sfx, ['source_in' => ['api']]));
    $this->assertSame([$data['a'], $data['c']], $this->listingEmails($sfx, ['source_not_in' => ['api']]));
    $this->assertSame([$data['b'], $data['c']], $this->listingEmails($sfx, ['score_gt' => '10']));
    $this->assertSame([$data['a']], $this->listingEmails($sfx, ['score_lt' => '30']));
    $this->assertSame([$data['b'], $data['c']], $this->listingEmails($sfx, ['score_min' => '30']));
    $this->assertSame([$data['a'], $data['b']], $this->listingEmails($sfx, ['score_max' => '30']));
    $this->assertSame([$data['b']], $this->listingEmails($sfx, ['created_from' => '2026-01-02', 'created_to' => '2026-01-02']));
    $this->assertSame([$data['b'], $data['c']], $this->listingEmails($sfx, ['subscribed_from' => '2026-02-02']));
    $this->assertSame([$data['a']], $this->listingEmails($sfx, ['subscribed_to' => '2026-02-01']));
    $this->assertSame([$data['a'], $data['c']], $this->listingEmails($sfx, ['segment_in' => [$data['segmentId']]]));
    $this->assertSame([$data['b']], $this->listingEmails($sfx, ['segment_not_in' => [$data['segmentId']]]));
    $this->assertSame([$data['a']], $this->listingEmails($sfx, ['tag_in' => [$data['tagId']]]));
    $this->assertSame([$data['b'], $data['c']], $this->listingEmails($sfx, ['tag_not_in' => [$data['tagId']]]));
    $this->assertSame([$data['a'], $data['b'], $data['c']], $this->listingEmails($sfx, ['segment_in' => [(string)$data['segmentId'], 'without-list']]));
  }

  public function testListingCombinesNewFilterKeys(): void {
    $data = $this->createFilterData();
    $sfx = $data['suffix'];

    $this->assertSame([$data['c']], $this->listingEmails($sfx, [
      'segment_in' => [$data['segmentId']],
      'tag_not_in' => [$data['tagId']],
      'score_min' => '30',
    ]));
    $this->assertSame([$data['b']], $this->listingEmails($sfx, [
      'status_in' => ['unconfirmed'],
      'source_in' => ['api'],
      'created_from' => '2026-01-01',
      'subscribed_to' => '2026-12-31',
    ]));
    $this->assertSame([], $this->listingEmails($sfx, ['status_in' => ['subscribed'], 'source_in' => ['api']]));
  }

  public function testListingCountMatchesRowsForAZeroScoreFilter(): void {
    foreach ([0, 40, 50] as $i => $score) {
      (new SubscriberFactory())
        ->withEmail("rest-zero-{$i}-" . uniqid() . '@example.com')
        ->withEngagementScore($score)
        ->create();
    }
    $payload = $this->listingPayload($this->get(self::LISTING_PATH, ['query' => ['filter' => ['score_gt' => '0']]]));
    $this->assertCount(2, $payload['items']);
    $this->assertSame(2, $payload['meta']['count']);
    $groups = array_column($payload['groups'] ?? [], 'count', 'name');
    $this->assertSame(2, $groups['all'] ?? null);
  }

  public function testListingEmptyResultHasNoItemsAndNoPages(): void {
    $response = $this->get(self::LISTING_PATH, ['query' => [
      'search' => 'no-such-subscriber-' . uniqid(),
      'filter' => ['status_in' => ['bounced']],
    ]]);
    $payload = $this->listingPayload($response);
    $this->assertSame([], $payload['items']);
    $this->assertSame(0, $payload['meta']['count']);
    $this->assertSame(0, $payload['meta']['pages']);
  }

  public function testListingPaginatesFilteredResults(): void {
    $suffix = uniqid();
    for ($i = 1; $i <= 5; $i++) {
      (new SubscriberFactory())
        ->withEmail("rest-page-{$i}-{$suffix}@example.com")
        ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
        ->withEngagementScore(10 * $i)
        ->create();
    }
    $query = [
      'search' => $suffix,
      'per_page' => 2,
      'orderby' => 'email',
      'order' => 'asc',
      'filter' => ['status_in' => ['subscribed'], 'score_min' => '20'],
    ];
    $second = $this->get(self::LISTING_PATH, ['query' => $query + ['page' => 2]]);
    $payload = $this->listingPayload($second);
    $this->assertSame(4, $payload['meta']['count']);
    $this->assertSame(2, $payload['meta']['pages']);
    $this->assertSame(
      ["rest-page-4-{$suffix}@example.com", "rest-page-5-{$suffix}@example.com"],
      array_column($payload['items'], 'email')
    );
  }

  public function testListingSortsByEveryAllowedFieldInBothDirections(): void {
    $suffix = uniqid();
    $rows = [
      ['m', SubscriberEntity::STATUS_SUBSCRIBED, 10, '2026-01-03', '2026-02-05'],
      ['z', SubscriberEntity::STATUS_UNCONFIRMED, 50, '2026-01-01', '2026-02-04'],
      ['a', SubscriberEntity::STATUS_UNSUBSCRIBED, 30, '2026-01-02', '2026-02-06'],
    ];
    $ids = [];
    foreach ($rows as [$name, $status, $score, $created, $subscribed]) {
      $subscriber = (new SubscriberFactory())
        ->withEmail("{$name}-sort-{$suffix}@example.com")
        ->withStatus($status)
        ->withEngagementScore($score)
        ->withCreatedAt(new DateTimeImmutable($created . ' 10:00:00'))
        ->withLastSubscribedAt(new DateTimeImmutable($subscribed . ' 10:00:00'))
        ->create();
      $ids[$name] = (int)$subscriber->getId();
    }
    $expectedAsc = [
      'id' => ['m', 'z', 'a'],
      'email' => ['a', 'm', 'z'],
      'status' => ['m', 'z', 'a'],
      'engagement_score' => ['m', 'a', 'z'],
      'last_subscribed_at' => ['z', 'm', 'a'],
      'created_at' => ['z', 'a', 'm'],
    ];
    foreach ($expectedAsc as $field => $order) {
      foreach (['asc' => $order, 'desc' => array_reverse($order)] as $direction => $expected) {
        foreach ([['orderby', 'order'], ['sort_by', 'sort_order']] as [$sortParam, $orderParam]) {
          $response = $this->get(self::LISTING_PATH, ['query' => ['search' => $suffix, $sortParam => $field, $orderParam => $direction]]);
          $expectedIds = [];
          foreach ($expected as $name) {
            $expectedIds[] = $ids[$name] ?? 0;
          }
          $this->assertSame(
            $expectedIds,
            $this->listingIds($response),
            "$field $direction"
          );
        }
      }
    }
  }

  public function testListingSortUsesIdAsTiebreakerOnTies(): void {
    $suffix = uniqid();
    $ids = [];
    for ($i = 1; $i <= 3; $i++) {
      $ids[] = (int)(new SubscriberFactory())
        ->withEmail("rest-tie-{$i}-{$suffix}@example.com")
        ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
        ->withEngagementScore(20)
        ->withCreatedAt(new DateTimeImmutable('2026-01-01 10:00:00'))
        ->withLastSubscribedAt(new DateTimeImmutable('2026-01-01 10:00:00'))
        ->create()
        ->getId();
    }
    foreach (['status', 'engagement_score', 'last_subscribed_at', 'created_at'] as $field) {
      foreach (['asc' => $ids, 'desc' => array_reverse($ids)] as $direction => $expected) {
        $response = $this->get(self::LISTING_PATH, ['query' => ['search' => $suffix, 'orderby' => $field, 'order' => $direction]]);
        $this->assertSame($expected, $this->listingIds($response), "$field $direction");
      }
    }
  }

  /**
   * @dataProvider invalidListingQueryProvider
   * @param array<string, mixed> $query
   */
  public function testListingRejectsInvalidRequests(array $query, string $code): void {
    $response = $this->get(self::LISTING_PATH, ['query' => $query]);
    $error = $this->asError($response);
    $this->assertSame($code, $error['code']);
    $this->assertSame(400, $error['data']['status']);
  }

  /** @return array<string, array{0: array<string, mixed>, 1: string}> */
  public function invalidListingQueryProvider(): array {
    return [
      'unknown orderby' => [['orderby' => 'foo'], 'mailpoet_subscribers_invalid_orderby'],
      'unknown sort_by' => [['sort_by' => 'first_name'], 'mailpoet_subscribers_invalid_orderby'],
      'unknown order' => [['order' => 'sideways'], 'mailpoet_subscribers_invalid_order'],
      'unknown sort_order' => [['sort_order' => 'up'], 'mailpoet_subscribers_invalid_order'],
      'bad page' => [['page' => '0'], 'mailpoet_subscribers_invalid_page'],
      'unknown group' => [['group' => 'bogus'], 'mailpoet_subscribers_invalid_group'],
      'unknown filter key' => [['filter' => ['bogus' => '1']], 'mailpoet_subscribers_invalid_filter'],
      'minUpdatedAt over REST' => [['filter' => ['minUpdatedAt' => '2026-01-01']], 'mailpoet_subscribers_invalid_filter'],
      'bad status' => [['filter' => ['status_in' => ['nope']]], 'mailpoet_subscribers_invalid_status_in'],
      'bad source' => [['filter' => ['source_not_in' => ['nope']]], 'mailpoet_subscribers_invalid_source_not_in'],
      'bad segment' => [['filter' => ['segment_in' => ['abc']]], 'mailpoet_subscribers_invalid_segment_in'],
      'bad date' => [['filter' => ['created_from' => 'yesterday']], 'mailpoet_subscribers_invalid_created_from'],
      'reversed dates' => [['filter' => ['subscribed_from' => '2026-03-01', 'subscribed_to' => '2026-02-01']], 'mailpoet_subscribers_invalid_date_range'],
      'bad score' => [['filter' => ['score_gt' => 'high']], 'mailpoet_subscribers_invalid_score_gt'],
      'reversed scores' => [['filter' => ['score_min' => '60', 'score_max' => '10']], 'mailpoet_subscribers_invalid_score_range'],
      'single status key' => [['filter' => ['status' => 'subscribed']], 'mailpoet_subscribers_invalid_filter'],
      'single source key' => [['filter' => ['source' => 'form']], 'mailpoet_subscribers_invalid_filter'],
      'too many values' => [['filter' => ['tag_in' => range(1, 101)]], 'mailpoet_subscribers_invalid_tag_in'],
    ];
  }

  /**
   * @dataProvider legacyListingQueryProvider
   * @param array<string, mixed> $query
   */
  public function testListingAcceptsRequestsSentByTheCurrentFrontend(array $query): void {
    $response = $this->get(self::LISTING_PATH, ['query' => $query]);
    $this->listingPayload($response);
  }

  /** @return array<string, array{0: array<string, mixed>}> */
  public function legacyListingQueryProvider(): array {
    return [
      'default view' => [['page' => '1', 'per_page' => '20', 'orderby' => 'created_at', 'order' => 'desc', 'group' => 'all', 'search' => '']],
      'sort asc' => [['orderby' => 'created_at', 'order' => 'asc', 'group' => 'all']],
      'sort_by and sort_order' => [['sort_by' => 'created_at', 'sort_order' => 'asc']],
      'unconfirmed group' => [['group' => 'unconfirmed']],
      'subscribed group' => [['group' => 'subscribed']],
      'unsubscribed group' => [['group' => 'unsubscribed']],
      'inactive group' => [['group' => 'inactive']],
      'bounced group' => [['group' => 'bounced']],
      'trash group' => [['group' => 'trash']],
      'search' => [['search' => 'someone@example.com']],
      'all lists dropdown' => [['filter' => ['segment' => '']]],
      'all tags dropdown' => [['filter' => ['tag' => '']]],
      'all dropdowns' => [['filter' => ['segment' => '', 'tag' => '']]],
      'list dropdown' => [['filter' => ['segment' => '1']]],
      'without list' => [['filter' => ['segment' => 'without-list']]],
      'tag dropdown' => [['filter' => ['tag' => '1']]],
      'list and tag' => [['filter' => ['segment' => '1', 'tag' => '2']]],
      'legacy status and engagement filters' => [['filter' => [
        'statusInclude' => 'subscribed',
        'statusExclude' => ['bounced'],
        'engagementScoreInclude' => ['good'],
        'engagementScoreExclude' => 'low',
        'createdAtFrom' => '2026-01-01 00:00:00',
        'createdAtTo' => '2026-12-31 23:59:59',
      ]]],
      'score filters with tracking off' => [['orderby' => 'engagement_score', 'filter' => ['score_min' => '10']]],
    ];
  }

  public function testListingLegacySegmentTagAndGroupFiltersStillWork(): void {
    $data = $this->createFilterData();
    $sfx = $data['suffix'];
    $this->assertSame([$data['a'], $data['c']], $this->listingEmails($sfx, ['segment' => (string)$data['segmentId']]));
    $this->assertSame([$data['a']], $this->listingEmails($sfx, ['tag' => (string)$data['tagId']]));
    $response = $this->get(self::LISTING_PATH, ['query' => ['search' => $sfx, 'group' => 'unconfirmed', 'orderby' => 'email', 'order' => 'asc']]);
    $this->assertSame([$data['b']], array_column($this->listingPayload($response)['items'], 'email'));
  }

  public function testListingReturnsOnlyAllAndTrashGroups(): void {
    $data = $this->createGroupsData();
    $sfx = $data['suffix'];

    $response = $this->get(self::LISTING_PATH, ['query' => ['per_page' => 100]]);
    $payload = $this->listingPayload($response);
    $this->assertSame(['all', 'trash'], array_column($this->groupsOf($payload), 'name'));
    $this->assertSame(['All', 'Trash'], array_column($this->groupsOf($payload), 'label'));
    $this->assertSame($this->groupsOf($payload)[0]['count'], $payload['meta']['count']);

    $scenarios = [
      'search' => [['search' => $sfx], 3, 3, 1],
      'search and tag' => [['search' => $sfx, 'filter' => ['tag_in' => [$data['tagId']]]], 1, 1, 1],
      'search and dynamic segment' => [['search' => $sfx, 'filter' => ['segment_in' => [$data['dynamicId']]]], 1, 1, 1],
      'search, tag and dynamic segment' => [['search' => $sfx, 'filter' => ['segment_in' => [$data['dynamicId']], 'tag_in' => [$data['tagId']]]], 1, 1, 1],
      'search, list and tag' => [['search' => $sfx, 'filter' => ['segment_in' => [$data['segmentId']], 'tag_in' => [$data['tagId']]]], 1, 1, 0],
      'legacy unconfirmed group' => [['search' => $sfx, 'group' => 'unconfirmed'], 1, 3, 1],
      'legacy group with dynamic segment' => [['search' => $sfx, 'group' => 'subscribed', 'filter' => ['segment_in' => [$data['dynamicId']]]], 1, 1, 1],
      'trash group' => [['search' => $sfx, 'group' => 'trash'], 1, 3, 1],
    ];
    foreach ($scenarios as $name => [$query, $count, $all, $trash]) {
      $payload = $this->listingPayload($this->get(self::LISTING_PATH, ['query' => $query]));
      $groups = $this->groupsOf($payload);
      $this->assertSame(['all', 'trash'], array_column($groups, 'name'), $name);
      $this->assertSame($count, $payload['meta']['count'], "$name: meta.count");
      $this->assertSame([$all, $trash], array_column($groups, 'count'), "$name: groups");
    }
  }

  public function testListingGroupsUseTheSingleListCacheForAPlainListFilter(): void {
    $data = $this->createGroupsData();
    $payload = $this->listingPayload($this->get(self::LISTING_PATH, ['query' => ['filter' => ['segment_in' => [$data['segmentId']]]]]));
    $this->assertSame(['all', 'trash'], array_column($this->groupsOf($payload), 'name'));
    $this->assertSame([2, 0], array_column($this->groupsOf($payload), 'count'));
    $this->assertSame(2, $payload['meta']['count']);
  }

  /**
   * @param array<string, mixed> $payload
   * @return array<int, array{name: string, label: string, count: int}>
   */
  private function groupsOf(array $payload): array {
    $this->assertIsArray($payload['groups']);
    /** @var array<int, array{name: string, label: string, count: int}> $groups */
    $groups = $payload['groups'];
    return $groups;
  }

  /** @return array{suffix: string, segmentId: int, tagId: int, dynamicId: int} */
  private function createGroupsData(): array {
    $suffix = uniqid();
    $segment = (new SegmentFactory())->withName('REST Groups ' . $suffix)->create();
    $tag = (new TagFactory())->create();
    $definitions = [
      ['subscribed-tagged', SubscriberEntity::STATUS_SUBSCRIBED, [$segment], [$tag], false],
      ['subscribed', SubscriberEntity::STATUS_SUBSCRIBED, [$segment], [], false],
      ['unconfirmed', SubscriberEntity::STATUS_UNCONFIRMED, [], [], false],
      ['trashed-tagged', SubscriberEntity::STATUS_SUBSCRIBED, [], [$tag], true],
    ];
    foreach ($definitions as [$name, $status, $segments, $tags, $trashed]) {
      $factory = (new SubscriberFactory())
        ->withEmail("{$name}-groups-{$suffix}@example.com")
        ->withStatus($status)
        ->withSegments($segments)
        ->withTags($tags);
      if ($trashed) {
        $factory->withDeletedAt(new DateTimeImmutable());
      }
      $factory->create();
    }

    // Matches the two e-mails containing “tagged”, one of them trashed.
    $dynamic = new SegmentEntity('REST Dynamic ' . $suffix, SegmentEntity::TYPE_DYNAMIC, '');
    $entityManager = $this->diContainer->get(EntityManager::class);
    $entityManager->persist($dynamic);
    $filter = new DynamicSegmentFilterEntity($dynamic, new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_USER_ROLE,
      SubscriberTextField::EMAIL,
      ['operator' => 'contains', 'value' => 'tagged', 'action' => SubscriberTextField::EMAIL, 'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND]
    ));
    $dynamic->addDynamicFilter($filter);
    $entityManager->persist($filter);
    $entityManager->flush();

    return [
      'suffix' => $suffix,
      'segmentId' => (int)$segment->getId(),
      'tagId' => (int)$tag->getId(),
      'dynamicId' => (int)$dynamic->getId(),
    ];
  }

  public function testBulkActionWithSelectAllHonoursSetFilters(): void {
    $segment = (new SegmentFactory())->withName('REST Bulk Set Filter ' . uniqid())->create();
    $tag = (new TagFactory())->create();
    $tagged = (new SubscriberFactory())
      ->withEmail('rest-bulk-tagged-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->withTags([$tag])
      ->create();
    $untagged = (new SubscriberFactory())
      ->withEmail('rest-bulk-untagged-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();

    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'group' => 'all',
      'select_all' => true,
      'selection' => [],
      'filter' => ['segment_in' => [$segment->getId()], 'tag_not_in' => [$tag->getId()]],
    ]]);
    $this->assertSame(1, $this->bulkPayload($response)['count']);

    $this->subscribersRepository->refresh($tagged);
    $this->subscribersRepository->refresh($untagged);
    $this->assertNull($tagged->getDeletedAt());
    $this->assertNotNull($untagged->getDeletedAt());
  }

  public function testBulkActionAcceptsFrontendFilterShapes(): void {
    foreach ([[], ['segment' => ''], ['segment' => '', 'tag' => '']] as $filter) {
      $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
        'action' => 'trash',
        'group' => 'all',
        'search' => '',
        'selection' => [PHP_INT_MAX],
        'select_all' => false,
        'filter' => $filter,
      ]]);
      $this->assertIsArray($response);
      $this->assertArrayNotHasKey('code', $response);
    }
  }

  public function testBulkActionRejectsInvalidFilter(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('rest-bulk-invalid-filter-' . uniqid() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    foreach ([true, false] as $selectAll) {
      $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
        'action' => 'trash',
        'group' => 'all',
        'select_all' => $selectAll,
        'selection' => $selectAll ? [] : [(int)$subscriber->getId()],
        'filter' => ['bogus' => '1'],
      ]]);
      $error = $this->asError($response);
      $this->assertSame('mailpoet_subscribers_invalid_filter', $error['code']);
      $this->assertSame(400, $error['data']['status']);
    }
    $response = $this->post(self::BULK_ACTION_PATH, ['json' => [
      'action' => 'trash',
      'group' => 'all',
      'select_all' => true,
      'selection' => [],
      'filter' => ['status_in' => ['nope']],
    ]]);
    $this->assertSame('mailpoet_subscribers_invalid_status_in', $this->asError($response)['code']);
    $this->subscribersRepository->refresh($subscriber);
    $this->assertNull($subscriber->getDeletedAt());
  }

  public function testListingAndBulkActionRequireTheManageSubscribersPermission(): void {
    wp_set_current_user(0);
    $listing = $this->get(self::LISTING_PATH, ['query' => ['orderby' => 'foo']]);
    $this->assertContains($this->asError($listing)['data']['status'], [401, 403]);
    $bulk = $this->post(self::BULK_ACTION_PATH, ['json' => ['action' => 'trash', 'selection' => [1]]]);
    $this->assertContains($this->asError($bulk)['data']['status'], [401, 403]);
  }

  /**
   * @return array{suffix: string, a: string, b: string, c: string, segmentId: int, tagId: int}
   */
  private function createFilterData(): array {
    $suffix = uniqid();
    $segment = (new SegmentFactory())->withName('REST Filters ' . $suffix)->create();
    $tag = (new TagFactory())->create();
    $emails = [];
    $definitions = [
      'a' => [SubscriberEntity::STATUS_SUBSCRIBED, Source::FORM, 10, '2026-01-01', '2026-02-01', [$segment], [$tag]],
      'b' => [SubscriberEntity::STATUS_UNCONFIRMED, Source::API, 30, '2026-01-02', '2026-02-02', [], []],
      'c' => [SubscriberEntity::STATUS_UNSUBSCRIBED, Source::IMPORTED, 50, '2026-01-03', '2026-02-03', [$segment], []],
    ];
    foreach ($definitions as $name => [$status, $source, $score, $created, $subscribed, $segments, $tags]) {
      $emails[$name] = "{$name}-filter-{$suffix}@example.com";
      (new SubscriberFactory())
        ->withEmail($emails[$name])
        ->withStatus($status)
        ->withSource($source)
        ->withEngagementScore($score)
        ->withCreatedAt(new DateTimeImmutable($created . ' 12:00:00'))
        ->withLastSubscribedAt(new DateTimeImmutable($subscribed . ' 12:00:00'))
        ->withSegments($segments)
        ->withTags($tags)
        ->create();
    }
    return [
      'suffix' => $suffix,
      'a' => $emails['a'] ?? '',
      'b' => $emails['b'] ?? '',
      'c' => $emails['c'] ?? '',
      'segmentId' => (int)$segment->getId(),
      'tagId' => (int)$tag->getId(),
    ];
  }

  /**
   * @param mixed $response
   * @return int[]
   */
  private function listingIds($response): array {
    $ids = [];
    foreach ($this->listingPayload($response)['items'] as $item) {
      $id = $item['id'];
      $this->assertIsScalar($id);
      $ids[] = (int)$id;
    }
    return $ids;
  }

  /**
   * @param mixed $response
   * @return array{code: string, data: array{status: int}}
   */
  private function asError($response): array {
    $this->assertIsArray($response);
    $this->assertArrayHasKey('code', $response);
    /** @var array{code: string, data: array{status: int}} $response */
    return $response;
  }

  /**
   * @param mixed $response
   * @return array{items: array<int, array<string, mixed>>, meta: array{count: int, pages: int}}
   */
  private function listingPayload($response): array {
    $this->assertIsArray($response);
    $this->assertArrayNotHasKey('code', $response, json_encode($response) ?: '');
    /** @var array{data: array{items: array<int, array<string, mixed>>, meta: array{count: int, pages: int}}} $response */
    return $response['data'];
  }

  /**
   * @param mixed $response
   * @return array{count: int, queue: array{queued_count: int}}
   */
  private function bulkPayload($response): array {
    $this->assertIsArray($response);
    $this->assertArrayNotHasKey('code', $response, json_encode($response) ?: '');
    /** @var array{data: array{count: int, queue: array{queued_count: int}}} $response */
    return $response['data'];
  }

  /**
   * @param array<string, mixed> $filter
   * @return string[] Emails ordered by email ascending.
   */
  private function listingEmails(string $suffix, array $filter): array {
    $response = $this->get(self::LISTING_PATH, ['query' => [
      'search' => $suffix,
      'per_page' => 100,
      'orderby' => 'email',
      'order' => 'asc',
      'filter' => $filter,
    ]]);
    $emails = [];
    foreach ($this->listingPayload($response)['items'] as $item) {
      $email = $item['email'];
      $this->assertIsString($email);
      $emails[] = $email;
    }
    return $emails;
  }
}
