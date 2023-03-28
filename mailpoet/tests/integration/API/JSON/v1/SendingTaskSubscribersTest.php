<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Util\Fixtures;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\SendingTaskSubscribers;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber as TaskSubscriberFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class SendingTaskSubscribersTest extends \MailPoetTest {
  /** @var SubscriberEntity */
  private $unprocessedSubscriber;

  /** @var SubscriberEntity */
  private $failedSubscriber;

  /** @var ScheduledTaskSubscriberEntity */
  private $failedSubscriberTask;

  /** @var SubscriberEntity */
  private $sentSubscriber;

  /** @var ScheduledTaskEntity */
  private $task;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var SendingTaskSubscribers */
  private $endpoint;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  /** @var TaskSubscriberFactory */
  private $taskSubscriberFactory;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(SendingTaskSubscribers::class);
    $this->subscriberFactory = new SubscriberFactory();
    $this->taskSubscriberFactory = new TaskSubscriberFactory();


    $this->newsletter = (new NewsletterFactory())->withSubject('My Standard Newsletter')
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withSendingQueue()
      ->create();

    $queue = $this->newsletter->getLatestQueue();
    $this->task = $queue->getTask();

    $this->sentSubscriber = $this->subscriberFactory
      ->withEmail('sent@example.com')
      ->withFirstName('Sent')
      ->withLastName('Test')
      ->create();
    $this->taskSubscriberFactory->createProcessed($this->task, $this->sentSubscriber);

    $this->failedSubscriber = $this->subscriberFactory
      ->withEmail('failed@example.com')
      ->withFirstName('Failed')
      ->withLastName('Test')
      ->create();
    $this->failedSubscriberTask = $this->taskSubscriberFactory->createFailed($this->task, $this->failedSubscriber, 'Something went wrong!');

    $this->unprocessedSubscriber = $this->subscriberFactory
      ->withEmail('unprocessed@example.com')
      ->withFirstName('Unprocessed')
      ->withLastName('Test')
      ->create();
    $this->taskSubscriberFactory->createUnprocessed($this->task, $this->unprocessedSubscriber);
  }

  public function testListingReturnsErrorIfMissingNewsletter() {
    $res = $this->endpoint->listing([
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletter->getId() + 1],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($res->errors[0]['message'])
      ->equals('This email has not been sent yet.');
  }

  public function testListingReturnsErrorIfNewsletterNotBeingSent() {
    $newsletterWithoutTask = ((new NewsletterFactory()))->create();
    $res = $this->endpoint->listing([
      'sort_by' => 'created_at',
      'params' => ['id' => $newsletterWithoutTask->getId()],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($res->errors[0]['message'])
      ->equals('This email has not been sent yet.');
  }

  public function testItReturnsListing() {
    $sentSubscriberStatus = [
      'error' => null,
      'failed' => 0,
      'processed' => 1,
      'taskId' => $this->task->getId(),
      'email' => $this->sentSubscriber->getEmail(),
      'subscriberId' => $this->sentSubscriber->getId(),
      'lastName' => $this->sentSubscriber->getLastName(),
      'firstName' => $this->sentSubscriber->getFirstName(),
    ];
    $unprocessedSubscriberStatus = [
      'error' => null,
      'failed' => 0,
      'processed' => 0,
      'taskId' => $this->task->getId(),
      'email' => $this->unprocessedSubscriber->getEmail(),
      'subscriberId' => $this->unprocessedSubscriber->getId(),
      'lastName' => $this->unprocessedSubscriber->getLastName(),
      'firstName' => $this->unprocessedSubscriber->getFirstName(),
    ];
    $failedSubscriberStatus = [
      'error' => 'Something went wrong!',
      'failed' => 1,
      'processed' => 1,
      'taskId' => $this->task->getId(),
      'email' => $this->failedSubscriber->getEmail(),
      'subscriberId' => $this->failedSubscriber->getId(),
      'lastName' => $this->failedSubscriber->getLastName(),
      'firstName' => $this->failedSubscriber->getFirstName(),
    ];

    $res = $this->endpoint->listing([
      'sort_by' => 'subscriber',
      'params' => ['id' => $this->newsletter->getId()],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);
    expect($res->data)->equals([
      $sentSubscriberStatus,
      $failedSubscriberStatus,
      $unprocessedSubscriberStatus,
    ]);

    $res = $this->endpoint->listing([
      'group' => 'sent',
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletter->getId()],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);
    expect($res->data)->equals([
      $sentSubscriberStatus,
    ]);

    $res = $this->endpoint->listing([
      'group' => 'failed',
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletter->getId()],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);
    expect($res->data)->equals([
      $failedSubscriberStatus,
    ]);

    $res = $this->endpoint->listing([
      'group' => 'unprocessed',
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletter->getId()],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);
    expect($res->data)->equals([
      $unprocessedSubscriberStatus,
    ]);
  }

  public function testResendReturnsErrorIfWrongData() {
    $res = $this->endpoint->resend([
      'taskId' => $this->task->getId() + 1,
      'subscriberId' => $this->sentSubscriber->getId(),
    ]);
    expect($res->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($res->errors[0]['message'])
      ->equals('Failed sending task not found!');

    $res = $this->endpoint->resend([
      'taskId' => $this->task->getId(),
      'subscriberId' => $this->sentSubscriber->getId(),
    ]);
    expect($res->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($res->errors[0]['message'])
      ->equals('Failed sending task not found!');
  }

  public function testItCanResend() {
    $res = $this->endpoint->resend([
      'taskId' => $this->task->getId(),
      'subscriberId' => $this->failedSubscriber->getId(),
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);

    $this->entityManager->refresh($this->failedSubscriberTask);
    expect($this->failedSubscriberTask->getError())->equals(null);
    expect($this->failedSubscriberTask->getFailed())->equals(0);
    expect($this->failedSubscriberTask->getProcessed())->equals(0);

    $this->entityManager->refresh($this->task);
    expect($this->task->getStatus())->equals(null);

    $this->entityManager->refresh($this->newsletter);
    expect($this->newsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
  }
}
