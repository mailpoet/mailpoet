<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\REST\Test;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

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
    $this->assertArrayHasKey('subscribed', $groups);

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
}
