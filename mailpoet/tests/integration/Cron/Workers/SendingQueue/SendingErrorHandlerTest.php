<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;

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
      null, $subscriberErrors
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
      SendingErrorHandler::class, [
        'scheduledTaskSubscribersRepository' => $scheduledTaskSubscribersRepository,
      ]
    );
    $errorHandler->processError($error, new ScheduledTaskEntity(), $subscriberIds, $subscribers);
  }
}
