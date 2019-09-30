<?php

namespace MailPoet\Test\Mailer;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;
use MailPoet\WP\Functions as WPFunctions;

class MailerErrorTest extends \MailPoetUnitTest {

  function _before() {
    WPFunctions::set(Stub::make(new WPFunctions, [
      '__' => function ($value) {
        return $value;
      },
    ]));
  }

  function testItCanComposeErrorMessageWithoutSubscribers() {
    $error = new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, 'Some Message');
    expect($error->getMessageWithFailedSubscribers())->equals('Some Message');
  }

  function testItCanComposeErrorMessageWithOneSubscriber() {
    $subscriber_error = new SubscriberError('email@example.com', 'Subscriber message');
    $error = new MailerError(
      MailerError::OPERATION_SEND,
      MailerError::LEVEL_HARD,
      'Some Message',
      null,
      [$subscriber_error]
    );
    expect($error->getMessageWithFailedSubscribers())->equals('Some Message Unprocessed subscriber: (email@example.com: Subscriber message)');
  }

  function testItCanComposeErrorMessageWithMultipleSubscriberErrors() {
    $subscriber_error_1 = new SubscriberError('email1@example.com', 'Subscriber 1 message');
    $subscriber_error_2 = new SubscriberError('email2@example.com', null);
    $error = new MailerError(
      MailerError::OPERATION_SEND,
      MailerError::LEVEL_HARD,
      'Some Message',
      null,
      [$subscriber_error_1, $subscriber_error_2]
    );
    expect($error->getMessageWithFailedSubscribers())->equals(
      'Some Message Unprocessed subscribers: (email1@example.com: Subscriber 1 message), (email2@example.com)'
    );
  }

  function _after() {
    WPFunctions::set(new WPFunctions);
  }
}
