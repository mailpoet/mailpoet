<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\StatisticsNewsletters as StatisticsNewslettersFactory;
use MailPoet\Test\DataFactories\StatisticsOpens as StatisticsOpensFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\UnexpectedValueException;

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

    $duplicate = $this->controller->resendToNonOpeners($newsletter);

    verify($duplicate->getSubject())->equals('(Resent) Test Subject');
    verify($duplicate->getStatus())->equals(NewsletterEntity::STATUS_SENDING);

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

  public function testResendExcludesMachineOpens() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $subscribers = $this->createSubscribers(10);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    foreach ($subscribers as $subscriber) {
      (new StatisticsOpensFactory($newsletter, $subscriber))
        ->withMachineUserAgentType()
        ->create();
    }

    $duplicate = $this->controller->resendToNonOpeners($newsletter);

    $queue = $duplicate->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $taskId = $task->getId();
    $this->assertIsInt($taskId);
    $taskSubscribers = $this->getTaskSubscriberCount($taskId);
    verify($taskSubscribers)->equals(10);
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
    $this->controller->resendToNonOpeners($newsletter);
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
    $this->controller->resendToNonOpeners($newsletter);
  }

  public function testThrowsForWrongStatus() {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Draft Newsletter')
      ->withDraftStatus()
      ->withSendingQueue()
      ->create();

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Only sent newsletters can be resent.');
    $this->controller->resendToNonOpeners($newsletter);
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

    $subscribers = $this->createSubscribers(3);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    $duplicate = $this->controller->resendToNonOpeners($newsletter);

    verify($duplicate->getSubject())->equals('(Resent) Original Subject');

    $duplicateWpPostId = $duplicate->getWpPostId();
    $this->assertNotNull($duplicateWpPostId);
    $wpPost = get_post($duplicateWpPostId);
    $this->assertInstanceOf(\WP_Post::class, $wpPost);
    verify($wpPost->post_title)->equals('(Resent) Original Subject'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testTaskAndQueueCreated() {
    $newsletter = $this->createSentNewsletter('Test Subject');
    $subscribers = $this->createSubscribers(5);
    $this->createStatisticsNewsletters($newsletter, $subscribers);

    $duplicate = $this->controller->resendToNonOpeners($newsletter);

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

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('All non-openers have since unsubscribed or been deleted.');
    $this->controller->resendToNonOpeners($newsletter);
  }

  private function createSentNewsletter(string $subject): NewsletterEntity {
    return (new NewsletterFactory())
      ->withSubject($subject)
      ->withSentStatus()
      ->withSendingQueue()
      ->create();
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
}
