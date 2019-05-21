<?php

namespace MailPoet\Subscribers;

use AspectMock\Test as Mock;
use Codeception\Stub;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class ConfirmationEmailMailerTest extends \MailPoetTest {

  function testItSendsConfirmationEmail() {
    Mock::double('MailPoet\Subscription\Url', [
      'getConfirmationUrl' => 'http://example.com',
    ]);
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com',
    ]);

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' =>
        Stub\Expected::once(function($email) {
          expect($email['body']['html'])->contains('<strong>Test segment</strong>');
          expect($email['body']['html'])->contains('<a target="_blank" href="http://example.com">Click here to confirm your subscription.</a>');
        }),
    ], $this);

    $sender = new ConfirmationEmailMailer($mailer, new WPFunctions);


    $segment = Segment::createOrUpdate(
      [
        'name' => 'Test segment',
      ]
    );
    SubscriberSegment::subscribeToSegments(
      $subscriber,
      [$segment->id]
    );

    $sender->sendConfirmationEmail($subscriber);
  }

  function testItSetsErrorsWhenConfirmationEmailCannotBeSent() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com',
    ]);

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' =>
        Stub\Expected::once(function () {
          throw new \Exception('send error');
        }),
    ], $this);

    $sender = new ConfirmationEmailMailer($mailer);

    $sender->sendConfirmationEmail($subscriber);
    // error is set on the subscriber model object
    expect($subscriber->getErrors()[0])->equals('send error');
  }

  function testItDoesntSendWhenMSSIsActiveAndConfirmationEmailIsNotAuthorized() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com',
    ]);

    $mailer = $this->makeEmpty(Mailer::class, [
      'send' => Stub\Expected::never(),
    ]);

    $settings = new SettingsController;
    $settings->set(Bridge::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING_NAME, ['invalid_confirmation_address' => 'email@email.com']);
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $sender = new ConfirmationEmailMailer($mailer);

    $result = $sender->sendConfirmationEmail($subscriber);
    expect($result)->equals(false);
    $settings->set(Bridge::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING_NAME, null);
  }

  function testItLimitsNumberOfConfirmationEmailsForNotLoggedInUser() {
    wp_set_current_user(0);
    expect((new WPFunctions)->isUserLoggedIn())->false();
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com',
    ]);

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => function() {
        return true;
      },
    ], $this);
    $sender = new ConfirmationEmailMailer($mailer);

    for ($i = 0; $i < $sender::MAX_CONFIRMATION_EMAILS; $i++) {
      expect($sender->sendConfirmationEmail($subscriber))->equals(true);
    }
    expect($sender->sendConfirmationEmail($subscriber))->equals(false);
  }

  function testItDoesNotLimitNumberOfConfirmationEmailsForLoggedInUser() {
    wp_set_current_user(1);
    expect((new WPFunctions)->isUserLoggedIn())->true();
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com',
    ]);

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => function() {
        return true;
      },
    ], $this);
    $sender = new ConfirmationEmailMailer($mailer);

    for ($i = 0; $i < $sender::MAX_CONFIRMATION_EMAILS; $i++) {
      expect($sender->sendConfirmationEmail($subscriber))->equals(true);
    }
    expect($sender->sendConfirmationEmail($subscriber))->equals(true);
  }

  function _after() {
    Mock::clean();
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
  }

}
