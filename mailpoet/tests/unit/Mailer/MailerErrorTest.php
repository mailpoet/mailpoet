<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;
use MailPoet\WP\Functions as WPFunctions;

class MailerErrorTest extends \MailPoetUnitTest {
  public function _before() {
    WPFunctions::set(Stub::make(new WPFunctions, [
      '__' => function ($value) {
        return $value;
      },
    ]));
  }

  public function testItCanComposeErrorMessageWithoutSubscribers() {
    $error = new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, 'Some Message');
    expect($error->getMessageWithFailedSubscribers())->equals('Some Message');
  }

  public function testItCanComposeErrorMessageWithOneSubscriber() {
    $subscriberError = new SubscriberError('email@example.com', 'Subscriber message');
    $error = new MailerError(
      MailerError::OPERATION_SEND,
      MailerError::LEVEL_HARD,
      'Some Message',
      null,
      [$subscriberError]
    );
    expect($error->getMessageWithFailedSubscribers())->equals('Some Message Unprocessed subscriber: (email@example.com: Subscriber message)');
  }

  public function testItCanComposeErrorMessageWithMultipleSubscriberErrors() {
    $subscriberError1 = new SubscriberError('email1@example.com', 'Subscriber 1 message');
    $subscriberError2 = new SubscriberError('email2@example.com', null);
    $error = new MailerError(
      MailerError::OPERATION_SEND,
      MailerError::LEVEL_HARD,
      'Some Message',
      null,
      [$subscriberError1, $subscriberError2]
    );
    expect($error->getMessageWithFailedSubscribers())->equals(
      'Some Message Unprocessed subscribers: (email1@example.com: Subscriber 1 message), (email2@example.com)'
    );
  }

  public function _after() {
    parent::_after();
    WPFunctions::set(new WPFunctions);
  }
}
