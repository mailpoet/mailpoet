<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use Codeception\Stub;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
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
          verify($email['body']['html'])->stringContainsString('<strong>Test segment</strong>');
          verify($email['body']['html'])->stringContainsString('<a target="_blank" href="http://example.com">Click here to confirm your subscription.</a>');
          verify($extraParams['meta'])->equals([
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
    verify($result)->true();
    verify($this->subscriber->getConfirmationsCount())->equals(1);

    $sender->sendConfirmationEmailOnce($this->subscriber);
    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getConfirmationsCount())->equals(1);
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
    verify($exceptionMessage)->equals(__('There was an error when sending a confirmation email for your subscription. Please contact the website owner.', 'mailpoet'));
    $mailerLogError = MailerLog::getError();
    $this->assertIsArray($mailerLogError);
    verify($mailerLogError['operation'])->equals(MailerError::OPERATION_SEND);
    verify($mailerLogError['error_message'])->equals('Error message');
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
    verify($exceptionMessage)->equals(__('There was an error when sending a confirmation email for your subscription. Please contact the website owner.', 'mailpoet'));
    verify(MailerLog::getError())->null();
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
    verify($result)->equals(false);
    $settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, null);
  }

  public function testItLimitsNumberOfConfirmationEmailsForNotLoggedInUser() {
    wp_set_current_user(0);
    verify((new WPFunctions)->isUserLoggedIn())->false();

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
      verify($sender->sendConfirmationEmail($this->subscriber))->equals(true);
    }
    verify($sender->sendConfirmationEmail($this->subscriber))->equals(false);
  }

  public function testItDoesNotLimitNumberOfConfirmationEmailsForLoggedInUser() {
    wp_set_current_user(1);
    verify((new WPFunctions)->isUserLoggedIn())->true();

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
      verify($sender->sendConfirmationEmail($this->subscriber))->equals(true);
    }
    verify($sender->sendConfirmationEmail($this->subscriber))->equals(true);
  }

  public function testGetMailBodyWithCustomizerReplacesActivationShortcode() {
    $subcriptionUrlFactoryMock = $this->createMock(SubscriptionUrlFactory::class);
    $subcriptionUrlFactoryMock->method('getConfirmationUrl')->willReturn('https://example.com');

    $newsletterFactory = new NewsletterFactory();
    $newsletter = $newsletterFactory
      ->loadBodyFrom('newsletterThreeCols.json')
      ->withType(NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER)
      ->create();

    $body = $newsletter->getBody();
    $newBody = $body;
    $newBody['content']['blocks'][0]['blocks'][1]['blocks'][] =
      [
        'type' => 'button',
        'url' => '[activation_link]',
        'text' => 'Click here to confirm your subscription',
        'styles' => [
          'block' => [
            'backgroundColor' => '#2ea1cd',
            'borderColor' => '#0074a2',
            'borderWidth' => '1px',
            'borderRadius' => '5px',
            'borderStyle' => 'solid',
            'width' => '100%',
            'lineHeight' => '40px',
            'fontColor' => '#ffffff',
            'fontFamily' => 'Verdana',
            'fontSize' => '18px',
            'fontWeight' => 'normal',
            'textAlign' => 'center',
          ],
        ],
      ];

    $newsletter->setBody($newBody);

    $newsletterRepository = $this->diContainer->get(NewslettersRepository::class);
    $newsletterRepository->persist($newsletter);
    $newsletterRepository->flush();

    $settings = SettingsController::getInstance();
    $settings->set(ConfirmationEmailCustomizer::SETTING_ENABLE_EMAIL_CUSTOMIZER,
      true
    );
    $settings->set(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, $newsletter->getId());
    $confirmationEmailCustomizer = $this->diContainer->get(ConfirmationEmailCustomizer::class);

    $sender = new ConfirmationEmailMailer(
      $this->createMock(MailerFactory::class),
      $this->diContainer->get(WPFunctions::class),
      $settings,
      $this->diContainer->get(SubscribersRepository::class),
      $subcriptionUrlFactoryMock,
      $confirmationEmailCustomizer
    );

    $confirmationNewsletter = $confirmationEmailCustomizer->getNewsletter();
    verify($confirmationNewsletter->getId())->equals($newsletter->getId());
    $confirmationMailBody = $sender->getMailBodyWithCustomizer($this->subscriber, ['test_segment']);
    verify($confirmationMailBody['body']['html'])->stringContainsString('<a class="mailpoet_button" href="https://example.com"');


    // See MAILPOET-5253
    $newBody = $body;
    $newBody['content']['blocks'][0]['blocks'][1]['blocks'][] =
      [
        'type' => 'button',
        'url' => 'http://[activation_link]',
        'text' => 'Click here to confirm your subscription',
        'styles' => [
          'block' => [
            'backgroundColor' => '#2ea1cd',
            'borderColor' => '#0074a2',
            'borderWidth' => '1px',
            'borderRadius' => '5px',
            'borderStyle' => 'solid',
            'width' => '100%',
            'lineHeight' => '40px',
            'fontColor' => '#ffffff',
            'fontFamily' => 'Verdana',
            'fontSize' => '18px',
            'fontWeight' => 'normal',
            'textAlign' => 'center',
          ],
        ],
      ];

    $newsletter->setBody($newBody);

    $newsletterRepository->persist($newsletter);
    $newsletterRepository->flush();

    $confirmationNewsletter = $confirmationEmailCustomizer->getNewsletter();
    verify($confirmationNewsletter->getId())->equals($newsletter->getId());
    $confirmationMailBody = $sender->getMailBodyWithCustomizer($this->subscriber, ['test_segment']);
    verify($confirmationMailBody['body']['html'])->stringContainsString('<a class="mailpoet_button" href="https://example.com"');

  }
}
