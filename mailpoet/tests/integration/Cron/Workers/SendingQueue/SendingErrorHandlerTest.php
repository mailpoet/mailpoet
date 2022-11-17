<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;
use MailPoet\Tasks\Sending as SendingTask;

class SendingErrorHandlerTest extends \MailPoetTest {

  /** @var SendingErrorHandler */
  private $errorHandler;

  public function _before() {
    parent::_before();
    $this->errorHandler = $this->diContainer->get(SendingErrorHandler::class);
  }

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

    $sendingTask = Stub::make(
      SendingTask::class,
      [
        'saveSubscriberError' => Expected::exactly(
          2,
          function($id, $message) {
            if ($id === 2) {
              expect($message)->equals('Error Message');
            } else {
              expect($message)->equals('Subscriber Message');
            }
          }
        ),
      ],
      $this
    );

    $this->errorHandler->processError($error, $sendingTask, $subscriberIds, $subscribers);
  }
}
