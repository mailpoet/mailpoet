<?php

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;
use MailPoet\Tasks\Sending as SendingTask;

class SendingErrorHandlerTest extends \MailPoetTest {

  /** @var SendingErrorHandler */
  private $error_handler;

  function _before() {
    parent::_before();
    $this->error_handler = new SendingErrorHandler();
  }

  function testItShouldProcessSoftErrorCorrectly() {
    $subscribers = [
      'john@doe.com',
      'john@rambo.com',
    ];
    $subscriber_ids = [1, 2];
    $subscriber_errors = [
      new SubscriberError('john@doe.com', 'Subscriber Message'),
      new SubscriberError('john@rambo.com', null),
    ];
    $error = new MailerError(
      MailerError::OPERATION_SEND,
      MailerError::LEVEL_SOFT,
      'Error Message',
      null, $subscriber_errors
    );

    $sending_task = Stub::make(
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

    $this->error_handler->processError($error, $sending_task, $subscriber_ids, $subscribers);
  }
}
