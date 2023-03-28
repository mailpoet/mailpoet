<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use Codeception\Stub;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

class ConfirmationEmailMailerTest extends \MailPoetTest {

  /** @var SegmentFactory */
  private $segmentFactory;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  public function _before() {
    parent::_before();

    $this->segmentFactory = new SegmentFactory();
    $this->subscriberFactory = new SubscriberFactory();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);

    $this->subscriber = $this->subscriberFactory
      ->withFirstName('John')
      ->withLastName('Mailer')
      ->withEmail('john@mailpoet.com')
      ->create();
  }

  public function testItSendsConfirmationEmail() {
    $subcriptionUrlFactoryMock = $this->createMock(SubscriptionUrlFactory::class);
    $subcriptionUrlFactoryMock->method('getConfirmationUrl')->willReturn('http://example.com');

    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(
      'signup_confirmation.body',
      $settings->get('signup_confirmation.body') . "\nLists: [lists_to_confirm]"
    );

    $this->subscriber->setStatus('unconfirmed');
    $this->subscriber->setSource('api');
    $this->subscribersRepository->persist($this->subscriber);
    $this->subscribersRepository->flush();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' =>
        Stub\Expected::once(function($email, $subscriber, $extraParams) {
          expect($email['body']['html'])->stringContainsString('<strong>Test segment</strong>');
          expect($email['body']['html'])->stringContainsString('<a target="_blank" href="http://example.com">Click here to confirm your subscription.</a>');
          expect($extraParams['meta'])->equals([
            'email_type' => 'confirmation',
            'subscriber_status' => 'unconfirmed',
            'subscriber_source' => 'api',
          ]);
          return ['response' => true];
        }),
    ], $this);

    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $subcriptionUrlFactoryMock,
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );

    $segment = $this->segmentFactory->withName('Test segment')->create();
    $this->subscriberSegmentRepository->subscribeToSegments($this->subscriber, [$segment]);

    $result = $sender->sendConfirmationEmail($this->subscriber);
    expect($result)->true();
    expect($this->subscriber->getConfirmationsCount())->equals(1);

    $sender->sendConfirmationEmailOnce($this->subscriber);
    $this->subscribersRepository->refresh($this->subscriber);
    expect($this->subscriber->getConfirmationsCount())->equals(1);
  }

  public function testItThrowsExceptionWhenConfirmationEmailCannotBeSent() {
    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' =>
        Stub\Expected::once(function () {
          throw new \Exception('send error');
        }),
    ], $this);

    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(__('There was an error when sending a confirmation email for your subscription. Please contact the website owner.', 'mailpoet'));
    $sender->sendConfirmationEmail($this->subscriber);
  }

  public function testSendConfirmationEmailThrowsAndLogHardErrorWhenSendReturnsFalse() {
    MailerLog::resetMailerLog();
    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => ['response' => false, 'error' => new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, 'Error message')],
    ], $this);

    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );
    $exceptionMessage = '';
    try {
      $sender->sendConfirmationEmail($this->subscriber);
    } catch (\Exception $e) {
      $exceptionMessage = $e->getMessage();
    }
    expect($exceptionMessage)->equals(__('There was an error when sending a confirmation email for your subscription. Please contact the website owner.', 'mailpoet'));
    $mailerLogError = MailerLog::getError();
    $this->assertIsArray($mailerLogError);
    expect($mailerLogError['operation'])->equals(MailerError::OPERATION_SEND);
    expect($mailerLogError['error_message'])->equals('Error message');
  }

  public function testSendConfirmationEmailThrowsAndIgnoresSoftErrorWhenSendReturnsFalse() {
    MailerLog::resetMailerLog();
    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => ['response' => false, 'error' => new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_SOFT, 'Error message')],
    ], $this);

    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );
    $exceptionMessage = '';
    try {
      $sender->sendConfirmationEmail($this->subscriber);
    } catch (\Exception $e) {
      $exceptionMessage = $e->getMessage();
    }
    expect($exceptionMessage)->equals(__('There was an error when sending a confirmation email for your subscription. Please contact the website owner.', 'mailpoet'));
    expect(MailerLog::getError())->null();
  }

  public function testItDoesntSendWhenMSSIsActiveAndConfirmationEmailIsNotAuthorized() {
    $mailer = $this->makeEmpty(Mailer::class, [
      'send' => Stub\Expected::never(),
    ]);

    $settings = SettingsController::getInstance();
    $settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, ['invalid_sender_address' => 'email@email.com']);
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );

    $result = $sender->sendConfirmationEmail($this->subscriber);
    expect($result)->equals(false);
    $settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, null);
  }

  public function testItLimitsNumberOfConfirmationEmailsForNotLoggedInUser() {
    wp_set_current_user(0);
    expect((new WPFunctions)->isUserLoggedIn())->false();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => function() {
        return ['response' => true];
      },
    ], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );

    for ($i = 0; $i < $sender::MAX_CONFIRMATION_EMAILS; $i++) {
      expect($sender->sendConfirmationEmail($this->subscriber))->equals(true);
    }
    expect($sender->sendConfirmationEmail($this->subscriber))->equals(false);
  }

  public function testItDoesNotLimitNumberOfConfirmationEmailsForLoggedInUser() {
    wp_set_current_user(1);
    expect((new WPFunctions)->isUserLoggedIn())->true();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => function() {
        return ['response' => true];
      },
    ], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );

    for ($i = 0; $i < $sender::MAX_CONFIRMATION_EMAILS; $i++) {
      expect($sender->sendConfirmationEmail($this->subscriber))->equals(true);
    }
    expect($sender->sendConfirmationEmail($this->subscriber))->equals(true);
  }
}
