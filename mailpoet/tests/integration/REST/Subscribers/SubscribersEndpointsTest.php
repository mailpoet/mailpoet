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
 * {@see \MailPoet\Test\Subscribers\BulkActionControllerTest}; this file only
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
    $this->assertIsArray($payload['items']);
    $this->assertIsArray($payload['meta']);
    $this->assertArrayHasKey('count', $payload['meta']);
    $this->assertArrayHasKey('pages', $payload['meta']);
    $this->assertIsArray($payload['groups']);
    $groups = array_column($payload['groups'], 'count', 'name');
    $this->assertArrayHasKey('all', $groups);
    $this->assertArrayHasKey('subscribed', $groups);

    $emails = array_column($payload['items'], 'email');
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
    $emails = array_column($response['data']['items'], 'email');
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
    $data = $response['data'];
    $this->assertIsArray($data);
    $this->assertSame('trash', $data['action']);
    $this->assertSame(1, $data['count']);
    $this->assertNull($data['segment']);
    $this->assertNull($data['tag']);

    $this->subscribersRepository->refresh($subscriber);
    $this->assertNotNull($subscriber->getDeletedAt());
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

    $this->assertSame('mailpoet_subscribers_invalid_group', $response['code']);
    $this->assertSame(400, $response['data']['status']);
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
