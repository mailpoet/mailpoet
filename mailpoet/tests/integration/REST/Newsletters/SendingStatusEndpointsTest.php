<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\Newsletters;

use Codeception\Util\Fixtures;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\REST\Test;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber as TaskSubscriberFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

require_once __DIR__ . '/../Test.php';

/**
 * Covers the contract surface of the sending-status REST endpoints
 * (`GET /newsletters/{id}/sending-status` and its `resend` action). Asserts
 * the HTTP layer wires the shared listing repository and the resend logic and
 * returns the expected envelope shape.
 */
class SendingStatusEndpointsTest extends Test {
  private function listingPath(int $newsletterId): string {
    return "/mailpoet/v1/newsletters/{$newsletterId}/sending-status";
  }

  private function resendPath(int $newsletterId): string {
    return "/mailpoet/v1/newsletters/{$newsletterId}/sending-status/resend";
  }

  /**
   * @param mixed $response
   * @return array<mixed, mixed>
   */
  private function payload($response): array {
    $this->assertIsArray($response);
    $this->assertArrayHasKey('data', $response);
    $payload = $response['data'];
    $this->assertIsArray($payload);
    return $payload;
  }

  public function _before() {
    parent::_before();
    wp_set_current_user(1);
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
  }

  public function testListingReturnsTaskSubscribersForTheNewsletter(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('SendingStatus_' . uniqid())
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withSendingQueue()
      ->create();
    $task = $newsletter->getLatestQueue()->getTask();

    $subscriberFactory = new SubscriberFactory();
    $taskSubscriberFactory = new TaskSubscriberFactory();
    $sentSubscriber = $subscriberFactory->withEmail('sent_' . uniqid() . '@example.com')->create();
    $failedSubscriber = $subscriberFactory->withEmail('failed_' . uniqid() . '@example.com')->create();
    $taskSubscriberFactory->createProcessed($task, $sentSubscriber);
    $taskSubscriberFactory->createFailed($task, $failedSubscriber, 'Something went wrong!');

    $payload = $this->payload($this->get($this->listingPath((int)$newsletter->getId()), ['query' => [
      'per_page' => 100,
    ]]));
    $items = $payload['items'];
    $this->assertIsArray($items);
    $meta = $payload['meta'];
    $this->assertIsArray($meta);
    $emails = array_column($items, 'email');
    $this->assertContains($sentSubscriber->getEmail(), $emails);
    $this->assertContains($failedSubscriber->getEmail(), $emails);
    $this->assertSame(2, $meta['count']);
  }

  public function testListingScopesItemsToTheRequestedNewsletter(): void {
    $subscriberFactory = new SubscriberFactory();
    $taskSubscriberFactory = new TaskSubscriberFactory();

    $newsletter = (new NewsletterFactory())
      ->withSubject('Wanted_' . uniqid())
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withSendingQueue()
      ->create();
    $wantedSubscriber = $subscriberFactory->withEmail('wanted_' . uniqid() . '@example.com')->create();
    $taskSubscriberFactory->createProcessed($newsletter->getLatestQueue()->getTask(), $wantedSubscriber);

    $otherNewsletter = (new NewsletterFactory())
      ->withSubject('Other_' . uniqid())
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withSendingQueue()
      ->create();
    $otherSubscriber = $subscriberFactory->withEmail('other_' . uniqid() . '@example.com')->create();
    $taskSubscriberFactory->createProcessed($otherNewsletter->getLatestQueue()->getTask(), $otherSubscriber);

    $payload = $this->payload($this->get($this->listingPath((int)$newsletter->getId()), ['query' => ['per_page' => 100]]));
    $items = $payload['items'];
    $this->assertIsArray($items);
    $emails = array_column($items, 'email');
    $this->assertContains($wantedSubscriber->getEmail(), $emails);
    $this->assertNotContains($otherSubscriber->getEmail(), $emails);
  }

  public function testListingReturnsEmptyForNewsletterWithoutSendingTasks(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Unsent_' . uniqid())
      ->create();

    $payload = $this->payload($this->get($this->listingPath((int)$newsletter->getId()), ['query' => ['per_page' => 100]]));
    $meta = $payload['meta'];
    $this->assertIsArray($meta);
    $this->assertSame([], $payload['items']);
    $this->assertSame(0, $meta['count']);
  }

  public function testListingCarriesMailerEnvelopeFields(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Envelope_' . uniqid())
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withSendingQueue()
      ->create();

    $payload = $this->payload($this->get($this->listingPath((int)$newsletter->getId()), ['query' => ['per_page' => 10]]));
    $this->assertArrayHasKey('mta_log', $payload);
    $this->assertArrayHasKey('mta_method', $payload);
    $this->assertArrayHasKey('cron_accessible', $payload);
    $this->assertArrayHasKey('current_time', $payload);
  }

  public function testListingReturnsStatusGroups(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Groups_' . uniqid())
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withSendingQueue()
      ->create();
    $task = $newsletter->getLatestQueue()->getTask();

    $subscriberFactory = new SubscriberFactory();
    $taskSubscriberFactory = new TaskSubscriberFactory();
    $taskSubscriberFactory->createProcessed($task, $subscriberFactory->withEmail('s_' . uniqid() . '@example.com')->create());
    $taskSubscriberFactory->createFailed($task, $subscriberFactory->withEmail('f_' . uniqid() . '@example.com')->create(), 'Boom');
    $taskSubscriberFactory->createUnprocessed($task, $subscriberFactory->withEmail('u_' . uniqid() . '@example.com')->create());

    $payload = $this->payload($this->get($this->listingPath((int)$newsletter->getId()), ['query' => ['per_page' => 10]]));
    $groupsList = $payload['groups'];
    $this->assertIsArray($groupsList);
    $groups = array_column($groupsList, 'count', 'name');
    $this->assertSame(3, $groups['all']);
    $this->assertSame(1, $groups[ScheduledTaskSubscriberEntity::SENDING_STATUS_SENT]);
    $this->assertSame(1, $groups[ScheduledTaskSubscriberEntity::SENDING_STATUS_FAILED]);
    $this->assertSame(1, $groups[ScheduledTaskSubscriberEntity::SENDING_STATUS_UNPROCESSED]);
  }

  public function testListingFiltersByGroup(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Filter_' . uniqid())
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withSendingQueue()
      ->create();
    $task = $newsletter->getLatestQueue()->getTask();

    $subscriberFactory = new SubscriberFactory();
    $taskSubscriberFactory = new TaskSubscriberFactory();
    $taskSubscriberFactory->createProcessed($task, $subscriberFactory->withEmail('s_' . uniqid() . '@example.com')->create());
    $failedSubscriber = $subscriberFactory->withEmail('f_' . uniqid() . '@example.com')->create();
    $taskSubscriberFactory->createFailed($task, $failedSubscriber, 'Boom');

    $payload = $this->payload($this->get($this->listingPath((int)$newsletter->getId()), ['query' => [
      'per_page' => 10,
      'group' => ScheduledTaskSubscriberEntity::SENDING_STATUS_FAILED,
    ]]));
    $items = $payload['items'];
    $this->assertIsArray($items);
    $this->assertCount(1, $items);
    $this->assertIsArray($items[0]);
    $this->assertSame($failedSubscriber->getEmail(), $items[0]['email']);
  }

  public function testResendResetsAFailedTaskSubscriber(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Resend_' . uniqid())
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withSendingQueue()
      ->create();
    $task = $newsletter->getLatestQueue()->getTask();

    $subscriberFactory = new SubscriberFactory();
    $failedSubscriber = $subscriberFactory->withEmail('resend_' . uniqid() . '@example.com')->create();
    $failedTaskSubscriber = (new TaskSubscriberFactory())->createFailed($task, $failedSubscriber, 'Boom');

    $response = $this->post($this->resendPath((int)$newsletter->getId()), ['json' => [
      'task_id' => (int)$task->getId(),
      'subscriber_id' => (int)$failedSubscriber->getId(),
    ]]);
    $this->assertIsArray($response);
    $this->assertArrayNotHasKey('code', $response);

    $this->entityManager->refresh($failedTaskSubscriber);
    $this->assertNull($failedTaskSubscriber->getError());
    $this->assertEquals(0, $failedTaskSubscriber->getFailed());
    $this->assertEquals(0, $failedTaskSubscriber->getProcessed());

    $this->entityManager->refresh($newsletter);
    $this->assertSame(NewsletterEntity::STATUS_SENDING, $newsletter->getStatus());
  }

  public function testResendReturnsNotFoundForANonFailedSubscriber(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('ResendOk_' . uniqid())
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withSendingQueue()
      ->create();
    $task = $newsletter->getLatestQueue()->getTask();

    $subscriberFactory = new SubscriberFactory();
    $sentSubscriber = $subscriberFactory->withEmail('ok_' . uniqid() . '@example.com')->create();
    (new TaskSubscriberFactory())->createProcessed($task, $sentSubscriber);

    $response = $this->post($this->resendPath((int)$newsletter->getId()), ['json' => [
      'task_id' => (int)$task->getId(),
      'subscriber_id' => (int)$sentSubscriber->getId(),
    ]]);
    $this->assertIsArray($response);
    $this->assertSame('mailpoet_sending_status_task_not_found', $response['code']);
  }

  public function testResendIsRejectedWhenTaskBelongsToAnotherNewsletter(): void {
    $routeNewsletter = (new NewsletterFactory())
      ->withSubject('RouteNewsletter_' . uniqid())
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withSendingQueue()
      ->create();

    $otherNewsletter = (new NewsletterFactory())
      ->withSubject('OtherNewsletter_' . uniqid())
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withSendingQueue()
      ->create();
    $otherTask = $otherNewsletter->getLatestQueue()->getTask();

    $subscriberFactory = new SubscriberFactory();
    $failedSubscriber = $subscriberFactory->withEmail('cross_' . uniqid() . '@example.com')->create();
    $failedTaskSubscriber = (new TaskSubscriberFactory())->createFailed($otherTask, $failedSubscriber, 'Boom');

    // Address the other newsletter's failed task through the route of the
    // first newsletter — the endpoint must reject the mismatch.
    $response = $this->post($this->resendPath((int)$routeNewsletter->getId()), ['json' => [
      'task_id' => (int)$otherTask->getId(),
      'subscriber_id' => (int)$failedSubscriber->getId(),
    ]]);
    $this->assertIsArray($response);
    $this->assertSame('mailpoet_sending_status_task_not_found', $response['code']);

    // The other newsletter's task subscriber must be untouched.
    $this->entityManager->refresh($failedTaskSubscriber);
    $this->assertEquals(1, $failedTaskSubscriber->getFailed());
  }

  public function testResendIsRejectedForInactiveAutomationEmail(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Automation_' . uniqid())
      ->withType(NewsletterEntity::TYPE_AUTOMATION)
      ->withStatus(NewsletterEntity::STATUS_DRAFT)
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withSendingQueue()
      ->create();
    $task = $newsletter->getLatestQueue()->getTask();

    $subscriberFactory = new SubscriberFactory();
    $failedSubscriber = $subscriberFactory->withEmail('inactive_' . uniqid() . '@example.com')->create();
    (new TaskSubscriberFactory())->createFailed($task, $failedSubscriber, 'Boom');

    $response = $this->post($this->resendPath((int)$newsletter->getId()), ['json' => [
      'task_id' => (int)$task->getId(),
      'subscriber_id' => (int)$failedSubscriber->getId(),
    ]]);
    $this->assertIsArray($response);
    $this->assertSame('mailpoet_sending_status_email_not_active', $response['code']);
  }
}
