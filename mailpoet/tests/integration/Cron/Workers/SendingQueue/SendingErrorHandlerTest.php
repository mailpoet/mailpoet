<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoetVendor\Monolog\Logger;

class SendingErrorHandlerTest extends \MailPoetTest {
  public function testItShouldProcessSoftErrorCorrectly() {
    $subscribers = [
      'john@doe.com',
      'john@rambo.com',
    ];
    $subscriberIds = [1, 2];
    $subscriberErrors = [
      new SubscriberError('john@doe.com', 'Subscriber Message'),
      new SubscriberError('john@rambo.com', null),
    ];
    $error = new MailerError(
      MailerError::OPERATION_SEND,
      MailerError::LEVEL_SOFT,
      'Error Message',
      null,
      $subscriberErrors
    );

    $scheduledTaskSubscribersRepository = Stub::make(
      ScheduledTaskSubscribersRepository::class,
      [
        'saveError' => Expected::exactly(
          2,
          function ($task, $id, $message) {
            if ($id === 2) {
              verify($message)->equals('Error Message');
            } else {
              verify($message)->equals('Subscriber Message');
            }
          }
        ),
      ],
      $this
    );

    $errorHandler = $this->getServiceWithOverrides(
      SendingErrorHandler::class,
      [
        'scheduledTaskSubscribersRepository' => $scheduledTaskSubscribersRepository,
      ]
    );
    $errorHandler->processError($error, new ScheduledTaskEntity(), $subscriberIds, $subscribers);
  }

  public function testItShouldProcessSoftErrorForDomainAuthorizationCorrectly() {
    $error = new MailerError(
      MailerError::OPERATION_DOMAIN_AUTHORIZATION,
      MailerError::LEVEL_SOFT,
      'Email violates Sender Domain requirements. Please authenticate the sender domain.',
      null,
      []
    );
    $sendingQueuesRepository = Stub::make(
      SendingQueuesRepository::class,
      ['pause' => Expected::once()],
    );

    $errorHandler = $this->getServiceWithOverrides(
      SendingErrorHandler::class,
      [
        'sendingQueuesRepository' => $sendingQueuesRepository,
        'loggerFactory' => Stub::makeEmpty(
          LoggerFactory::class,
          ['getLogger' => Stub::makeEmpty(Logger::class, ['info' => Expected::once()])]
        ),
      ]
    );

    $sendingQueue = new SendingQueueEntity();
    $taskEntity = Stub::make(ScheduledTaskEntity::class, ['getSendingQueue' => $sendingQueue]);

    $errorHandler->processError($error, $taskEntity, [], []);
  }

  public function testItPausesTaskWithSenderDomainReasonAndClearsDomainCache() {
    $error = new MailerError(
      MailerError::OPERATION_DOMAIN_AUTHORIZATION,
      MailerError::LEVEL_SOFT,
      'Email violates Sender Domain requirements. Please authenticate the sender domain.',
      null,
      []
    );
    $senderDomainController = Stub::make(AuthorizedSenderDomainController::class, ['clearCache' => Expected::once()], $this);
    $errorHandler = $this->getServiceWithOverrides(
      SendingErrorHandler::class,
      ['senderDomainController' => $senderDomainController]
    );
    $task = $this->createTaskWithQueue();

    $errorHandler->processError($error, $task, [], []);

    $this->entityManager->refresh($task);
    verify($task->getStatus())->same(ScheduledTaskEntity::STATUS_PAUSED);
    verify($task->getPauseReason())->same(ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN);
  }

  public function testItDoesNotMarkTaskOrClearDomainCacheForOtherSoftErrors() {
    $error = new MailerError(
      MailerError::OPERATION_SEND,
      MailerError::LEVEL_SOFT,
      'Error Message',
      null,
      []
    );
    $senderDomainController = Stub::make(AuthorizedSenderDomainController::class, ['clearCache' => Expected::never()], $this);
    $errorHandler = $this->getServiceWithOverrides(
      SendingErrorHandler::class,
      ['senderDomainController' => $senderDomainController]
    );
    $task = $this->createTaskWithQueue();

    $errorHandler->processError($error, $task, [], []);

    $this->entityManager->refresh($task);
    verify($task->getStatus())->notEquals(ScheduledTaskEntity::STATUS_PAUSED);
    verify($task->getPauseReason())->null();
  }

  private function createTaskWithQueue(): ScheduledTaskEntity {
    $newsletter = (new NewsletterFactory())->withSendingQueue()->create();
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $queue->setCountTotal(2);
    $queue->setCountProcessed(0);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $task->setStatus(null);
    $this->entityManager->flush();
    $this->entityManager->refresh($queue);
    $this->entityManager->refresh($task);
    return $task;
  }
}
