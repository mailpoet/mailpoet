<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\Newsletters;

use MailPoet\Entities\NewsletterEntity;
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
