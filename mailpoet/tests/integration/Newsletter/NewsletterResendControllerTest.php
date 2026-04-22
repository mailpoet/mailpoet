<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\StatisticsNewsletters as StatisticsNewslettersFactory;
use MailPoet\Test\DataFactories\StatisticsOpens as StatisticsOpensFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\UnexpectedValueException;
use MailPoetVendor\Carbon\Carbon;

class NewsletterResendControllerTest extends \MailPoetTest {
  /** @var NewsletterResendController */
  private $controller;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->controller = $this->diContainer->get(NewsletterResendController::class);
    $this->settings = SettingsController::getInstance();
    $this->settings->set('sender', [
      'name' => 'John Doe',
      'address' => 'john.doe@example.com',
    ]);
  }

  public function testResendToNonOpenersHappyPath() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $subscribers = $this->createSubscribers(10);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    for ($i = 0; $i < 4; $i++) {
      (new StatisticsOpensFactory($newsletter, $subscribers[$i]))->create();
    }

    $duplicate = $this->controller->resendToNonOpeners($newsletter, 'Re: Test Subject');

    verify($duplicate->getSubject())->equals('Re: Test Subject');
    verify($duplicate->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
    verify($duplicate->getParent())->notNull();
    verify($duplicate->getParent()->getId())->equals($newsletter->getId());

    $queue = $duplicate->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);

    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getStatus())->null();

    $taskId = $task->getId();
    $this->assertIsInt($taskId);
    $taskSubscribers = $this->getTaskSubscriberCount($taskId);
    verify($taskSubscribers)->equals(6);
  }

  public function testMachineOpensCountAsOpens() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $subscribers = $this->createSubscribers(10);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    foreach ($subscribers as $subscriber) {
      (new StatisticsOpensFactory($newsletter, $subscriber))
        ->withMachineUserAgentType()
        ->create();
    }

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('All recipients have already opened this email.');
    $this->controller->resendToNonOpeners($newsletter, 'Re: Test Subject');
  }

  public function testThrowsForZeroNonOpeners() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $subscribers = $this->createSubscribers(5);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    foreach ($subscribers as $subscriber) {
      (new StatisticsOpensFactory($newsletter, $subscriber))->create();
    }

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('All recipients have already opened this email.');
    $this->controller->resendToNonOpeners($newsletter, 'Re: Test Subject');
  }

  public function testThrowsForWrongType() {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Notification')
      ->withStatus(NewsletterEntity::STATUS_SENT)
      ->withSendingQueue()
      ->create();
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $this->entityManager->flush();

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Only standard newsletters can be resent.');
    $this->controller->resendToNonOpeners($newsletter, 'Re: Notification');
  }

  public function testThrowsForWrongStatus() {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Draft Newsletter')
      ->withDraftStatus()
      ->withSendingQueue()
      ->create();

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Only sent newsletters can be resent.');
    $this->controller->resendToNonOpeners($newsletter, 'Re: Draft Newsletter');
  }

  public function testThrowsWhenAlreadyResent() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $subscribers = $this->createSubscribers(5);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    $this->controller->resendToNonOpeners($newsletter, 'Re: Test Subject');
    $this->entityManager->refresh($newsletter);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('This email has already been resent.');
    $this->controller->resendToNonOpeners($newsletter, 'Another Re: Test Subject');
  }

  public function testThrowsWhenResendingAResend() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $subscribers = $this->createSubscribers(5);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    $duplicate = $this->controller->resendToNonOpeners($newsletter, 'Re: Test Subject');
    $duplicate->setStatus(NewsletterEntity::STATUS_SENT);
    $duplicate->setSentAt(Carbon::now()->subHours(36));
    $queue = $duplicate->getLatestQueue();
    if ($queue) {
      $queue->setCountToProcess(0);
    }
    $this->entityManager->flush();

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('A resent email cannot be resent again.');
    $this->controller->resendToNonOpeners($duplicate, 'Re: Re: Test Subject');
  }

  public function testThrowsForSameSubject() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $subscribers = $this->createSubscribers(5);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('The subject line must be different from the original email.');
    $this->controller->resendToNonOpeners($newsletter, 'Test Subject');
  }

  public function testThrowsForSameSubjectCaseInsensitive() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $subscribers = $this->createSubscribers(5);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('The subject line must be different from the original email.');
    $this->controller->resendToNonOpeners($newsletter, '  TEST SUBJECT  ');
  }

  public function testThrowsForEmptySubject() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $subscribers = $this->createSubscribers(5);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Subject line is required.');
    $this->controller->resendToNonOpeners($newsletter, '   ');
  }

  public function testSubjectAndWpPostTitleUpdated() {
    $wpPostId = wp_insert_post([
      'post_title' => 'Original Subject',
      'post_type' => 'mailpoet_page',
      'post_status' => 'publish',
    ]);

    $newsletter = (new NewsletterFactory())
      ->withSubject('Original Subject')
      ->withSentStatus()
      ->withSendingQueue()
      ->withWpPostId($wpPostId)
      ->create();
    $newsletter->setSentAt(Carbon::now()->subHours(36));
    $this->entityManager->flush();

    $subscribers = $this->createSubscribers(3);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    $duplicate = $this->controller->resendToNonOpeners($newsletter, 'Re: Original Subject');

    verify($duplicate->getSubject())->equals('Re: Original Subject');

    $duplicateWpPostId = $duplicate->getWpPostId();
    $this->assertNotNull($duplicateWpPostId);
    $wpPost = get_post($duplicateWpPostId);
    $this->assertInstanceOf(\WP_Post::class, $wpPost);
    verify($wpPost->post_title)->equals('Re: Original Subject'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testTaskAndQueueCreated() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $subscribers = $this->createSubscribers(5);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    $duplicate = $this->controller->resendToNonOpeners($newsletter, 'Re: Test Subject');

    $queue = $duplicate->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $queueNewsletter = $queue->getNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $queueNewsletter);
    verify($queueNewsletter->getId())->equals($duplicate->getId());

    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getType())->equals('sending');
    verify($task->getStatus())->null();
  }

  public function testPostInsertZeroGuardCleansUp() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $subscribers = $this->createSubscribers(3);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    foreach ($subscribers as $subscriber) {
      $subscriber->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    }
    $this->entityManager->flush();

    $newsletterCountBefore = $this->getTableCount(NewsletterEntity::class);
    $taskCountBefore = $this->getTableCount(ScheduledTaskEntity::class);
    $queueCountBefore = $this->getTableCount(SendingQueueEntity::class);

    $thrown = false;
    try {
      $this->controller->resendToNonOpeners($newsletter, 'Re: Test Subject');
    } catch (UnexpectedValueException $e) {
      $thrown = true;
    }
    $this->assertTrue($thrown);

    verify($this->getTableCount(NewsletterEntity::class))->equals($newsletterCountBefore);
    verify($this->getTableCount(ScheduledTaskEntity::class))->equals($taskCountBefore);
    verify($this->getTableCount(SendingQueueEntity::class))->equals($queueCountBefore);
  }

  public function testThrowsWhenTooSoonAfterSending() {
    $newsletter = $this->createSentNewsletter('Test Subject', Carbon::now()->subHours(12));
    $subscribers = $this->createSubscribers(5);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('You can resend this email at least 1 day after it was sent.');
    $this->controller->resendToNonOpeners($newsletter, 'Re: Test Subject');
  }

  public function testThrowsWhenTooLongAfterSending() {
    $newsletter = $this->createSentNewsletter('Test Subject', Carbon::now()->subHours(80));
    $subscribers = $this->createSubscribers(5);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('You can only resend this email within 3 days of sending it.');
    $this->controller->resendToNonOpeners($newsletter, 'Re: Test Subject');
  }

  public function testCanResendHookIsCallable() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $this->controller->canResend($newsletter);
    $this->addToAssertionCount(1);
  }

  private function createSentNewsletter(string $subject, ?Carbon $sentAt = null): NewsletterEntity {
    $newsletter = (new NewsletterFactory())
      ->withSubject($subject)
      ->withSentStatus()
      ->withSendingQueue()
      ->create();
    $newsletter->setSentAt($sentAt ?? Carbon::now()->subHours(36));
    $this->entityManager->flush();
    return $newsletter;
  }

  /** @return SubscriberEntity[] */
  private function createSubscribers(int $count): array {
    $subscribers = [];
    for ($i = 0; $i < $count; $i++) {
      $subscribers[] = (new SubscriberFactory())
        ->withEmail("subscriber-{$i}@example.com")
        ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
        ->create();
    }
    return $subscribers;
  }

  /** @param SubscriberEntity[] $subscribers */
  private function createStatisticsNewsletters(NewsletterEntity $newsletter, array $subscribers): void {
    foreach ($subscribers as $subscriber) {
      (new StatisticsNewslettersFactory($newsletter, $subscriber))->create();
    }
  }

  private function getTaskSubscriberCount(int $taskId): int {
    $connection = $this->entityManager->getConnection();
    $table = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $result = $connection->executeQuery(
      "SELECT COUNT(*) FROM $table WHERE task_id = ?",
      [$taskId]
    );
    return intval($result->fetchOne());
  }

  /** @param class-string $entityClass */
  private function getTableCount(string $entityClass): int {
    $connection = $this->entityManager->getConnection();
    $table = $this->entityManager->getClassMetadata($entityClass)->getTableName();
    $result = $connection->executeQuery("SELECT COUNT(*) FROM $table");
    return intval($result->fetchOne());
  }
}
