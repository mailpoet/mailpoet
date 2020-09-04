<?php

namespace MailPoet\Subscribers;

use Codeception\Stub;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

class ConfirmationEmailMailerTest extends \MailPoetTest {
  public function testItSendsConfirmationEmail() {
    $subcriptionUrlFacroryMock = $this->createMock(SubscriptionUrlFactory::class);
    $subcriptionUrlFacroryMock->method('getConfirmationUrl')->willReturn('http://example.com');

    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(
      'signup_confirmation.body',
      $settings->get('signup_confirmation.body') . "\nLists: [lists_to_confirm]"
    );

    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com',
      'status' => 'unconfirmed',
      'source' => 'api',
    ]);
    $subscriber->save();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' =>
        Stub\Expected::once(function($email, $subscriber, $extraParams) {
          expect($email['body']['html'])->contains('<strong>Test segment</strong>');
          expect($email['body']['html'])->contains('<a target="_blank" href="http://example.com">I confirm my subscription!</a>');
          expect($extraParams['meta'])->equals([
            'email_type' => 'confirmation',
            'subscriber_status' => 'unconfirmed',
            'subscriber_source' => 'api',
          ]);
          return ['response' => true];
        }),
    ], $this);

    $sender = new ConfirmationEmailMailer(
      $mailer,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $subcriptionUrlFacroryMock
    );


    $segment = Segment::createOrUpdate(
      [
        'name' => 'Test segment',
      ]
    );
    SubscriberSegment::subscribeToSegments(
      $subscriber,
      [$segment->id]
    );

    $result = $sender->sendConfirmationEmail($subscriber);
    expect($result)->true();
    codecept_debug($subscriber);
    expect($subscriber->countConfirmations)->equals(1);

    $sender->sendConfirmationEmailOnce($subscriber);
    $subscriberFromDb = Subscriber::findOne($subscriber->id);
    expect($subscriberFromDb->countConfirmations)->equals(1);
    expect($subscriber->countConfirmations)->equals(1);
  }

  public function testItSetsErrorsWhenConfirmationEmailCannotBeSent() {
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

    $sender = new ConfirmationEmailMailer(
      $mailer,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscriptionUrlFactory::class)
    );

    $sender->sendConfirmationEmail($subscriber);
    // error is set on the subscriber model object
    expect($subscriber->getErrors()[0])->equals('Something went wrong with your subscription. Please contact the website owner.');
  }

  public function testItDoesntSendWhenMSSIsActiveAndConfirmationEmailIsNotAuthorized() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com',
    ]);

    $mailer = $this->makeEmpty(Mailer::class, [
      'send' => Stub\Expected::never(),
    ]);

    $settings = SettingsController::getInstance();
    $settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, ['invalid_sender_address' => 'email@email.com']);
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $sender = new ConfirmationEmailMailer(
      $mailer,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscriptionUrlFactory::class)
    );

    $result = $sender->sendConfirmationEmail($subscriber);
    expect($result)->equals(false);
    $settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, null);
  }

  public function testItLimitsNumberOfConfirmationEmailsForNotLoggedInUser() {
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
        return ['response' => true];
      },
    ], $this);
    $sender = new ConfirmationEmailMailer(
      $mailer,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscriptionUrlFactory::class)
    );

    for ($i = 0; $i < $sender::MAX_CONFIRMATION_EMAILS; $i++) {
      expect($sender->sendConfirmationEmail($subscriber))->equals(true);
    }
    expect($sender->sendConfirmationEmail($subscriber))->equals(false);
  }

  public function testItDoesNotLimitNumberOfConfirmationEmailsForLoggedInUser() {
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
        return ['response' => true];
      },
    ], $this);
    $sender = new ConfirmationEmailMailer(
      $mailer,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscriptionUrlFactory::class)
    );

    for ($i = 0; $i < $sender::MAX_CONFIRMATION_EMAILS; $i++) {
      expect($sender->sendConfirmationEmail($subscriber))->equals(true);
    }
    expect($sender->sendConfirmationEmail($subscriber))->equals(true);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
  }
}
