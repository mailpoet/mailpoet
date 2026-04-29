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
use MailPoetVendor\Carbon\Carbon;

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
    $subscriptionUrlFactoryMock = $this->createMock(SubscriptionUrlFactory::class);
    $subscriptionUrlFactoryMock->method('getConfirmationUrl')->willReturn('http://example.com');

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
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $subscriptionUrlFactoryMock,
      $this->diContainer->get(ConfirmationEmailCustomizer::class),
      $this->diContainer->get(NewslettersRepository::class)
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
    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscribersRepository->flush();

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
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class),
      $this->diContainer->get(NewslettersRepository::class)
    );

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(__('There was an error when sending a confirmation email for your subscription. Please contact the website owner.', 'mailpoet'));
    $sender->sendConfirmationEmail($this->subscriber);
  }

  public function testSendConfirmationEmailThrowsAndLogHardErrorWhenSendReturnsFalse() {
    MailerLog::resetMailerLog();
    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscribersRepository->flush();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => ['response' => false, 'error' => new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, 'Error message')],
    ], $this);

    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class),
      $this->diContainer->get(NewslettersRepository::class)
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
    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscribersRepository->flush();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => ['response' => false, 'error' => new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_SOFT, 'Error message')],
    ], $this);

    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class),
      $this->diContainer->get(NewslettersRepository::class)
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
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class),
      $this->diContainer->get(NewslettersRepository::class)
    );

    $result = $sender->sendConfirmationEmail($this->subscriber);
    verify($result)->equals(false);
    $settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, null);
  }

  public function testItDoesNotSendAdminConfirmationEmailWhenMaxCountIsReached() {
    wp_set_current_user(0);
    verify((new WPFunctions)->isUserLoggedIn())->false();
    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscriber->setConfirmationsCount(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS);
    $this->subscribersRepository->flush();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => Stub\Expected::never(),
    ], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class),
      $this->diContainer->get(NewslettersRepository::class)
    );

    $result = $sender->sendAdminConfirmationEmail($this->subscriber);
    verify($result['status'])->equals('skipped');
    verify($result['reason'] ?? null)->equals('max_confirmations_reached');
  }

  public function testItRecordsAndThrottlesAdminConfirmationEmailsForLoggedInUser() {
    wp_set_current_user(1);
    verify((new WPFunctions)->isUserLoggedIn())->true();
    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscribersRepository->flush();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => Stub\Expected::once(function() {
        return ['response' => true];
      }),
    ], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class),
      $this->diContainer->get(NewslettersRepository::class)
    );

    verify($sender->sendAdminConfirmationEmail($this->subscriber)['status'])->equals('sent');
    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getConfirmationsCount())->equals(1);
    verify($this->subscriber->getLastConfirmationEmailSentAt())->notNull();

    $result = $sender->sendAdminConfirmationEmail($this->subscriber);
    verify($result['status'])->equals('skipped');
    verify($result['reason'] ?? null)->equals('recently_sent');
    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getConfirmationsCount())->equals(1);
  }

  public function testGenericConfirmationEmailKeepsApiCompatibilityWithoutAdminThrottle(): void {
    wp_set_current_user(1);
    $previousSentAt = Carbon::now()->subDay()->millisecond(0);
    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscriber->setConfirmationsCount(1);
    $this->subscriber->setLastConfirmationEmailSentAt($previousSentAt);
    $this->subscribersRepository->flush();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => Stub\Expected::once(function() {
        return ['response' => true];
      }),
    ], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );

    verify($sender->sendConfirmationEmail($this->subscriber))->true();
    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getConfirmationsCount())->equals(1);
    $lastConfirmationEmailSentAt = $this->subscriber->getLastConfirmationEmailSentAt();
    $this->assertNotNull($lastConfirmationEmailSentAt);
    verify($lastConfirmationEmailSentAt->format('Y-m-d H:i:s'))->equals($previousSentAt->format('Y-m-d H:i:s'));
  }

  public function testAdminConfirmationEmailReleaseDoesNotEraseNewerClaim(): void {
    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscriber->setConfirmationsCount(1);
    $this->subscriber->setLastConfirmationEmailSentAt(Carbon::now()->subDays(8));
    $this->subscribersRepository->flush();

    $claim = $this->subscribersRepository->claimAdminConfirmationEmailResend(
      $this->subscriber,
      ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS,
      Carbon::now()->subDays(ConfirmationEmailMailer::ADMIN_CONFIRMATION_RESEND_INTERVAL_DAYS)->millisecond(0)
    );
    $this->assertTrue($claim['claimed']);
    $this->assertArrayHasKey('claim_time', $claim);
    $this->assertArrayHasKey('previous_count_confirmations', $claim);

    $newerClaimTime = Carbon::now()->addSecond()->millisecond(0)->format('Y-m-d H:i:s');
    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE $subscriberTable
       SET `count_confirmations` = :count_confirmations,
         `last_confirmation_email_sent_at` = :last_confirmation_email_sent_at
       WHERE `id` = :id",
      [
        'count_confirmations' => 3,
        'last_confirmation_email_sent_at' => $newerClaimTime,
        'id' => $this->subscriber->getId(),
      ]
    );

    $this->subscribersRepository->releaseAdminConfirmationEmailResendClaim(
      $this->subscriber,
      (string)$claim['claim_time'],
      $claim['previous_last_confirmation_email_sent_at'] ?? null,
      (int)$claim['previous_count_confirmations']
    );

    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getConfirmationsCount())->equals(3);
    $lastConfirmationEmailSentAt = $this->subscriber->getLastConfirmationEmailSentAt();
    $this->assertNotNull($lastConfirmationEmailSentAt);
    verify($lastConfirmationEmailSentAt->format('Y-m-d H:i:s'))->equals($newerClaimTime);
  }

  public function testFailedAdminConfirmationEmailReleasesClaimedCountAndTimestamp(): void {
    wp_set_current_user(1);
    $previousSentAt = Carbon::now()->subDays(8)->millisecond(0);
    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscriber->setConfirmationsCount(1);
    $this->subscriber->setLastConfirmationEmailSentAt($previousSentAt);
    $this->subscribersRepository->flush();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => Stub\Expected::once(function() use ($previousSentAt) {
        $this->subscribersRepository->refresh($this->subscriber);
        verify($this->subscriber->getConfirmationsCount())->equals(2);
        $claimedSentAt = $this->subscriber->getLastConfirmationEmailSentAt();
        $this->assertNotNull($claimedSentAt);
        $this->assertNotSame($previousSentAt->format('Y-m-d H:i:s'), $claimedSentAt->format('Y-m-d H:i:s'));
        throw new \Exception('send error');
      }),
    ], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );

    try {
      $sender->sendAdminConfirmationEmail($this->subscriber);
      $this->fail('Expected admin confirmation send to fail.');
    } catch (\Exception $e) {
      verify($e->getMessage())->equals(
        __('There was an error when sending a confirmation email for your subscription. Please contact the website owner.', 'mailpoet')
      );
    }

    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getConfirmationsCount())->equals(1);
    $lastConfirmationEmailSentAt = $this->subscriber->getLastConfirmationEmailSentAt();
    $this->assertNotNull($lastConfirmationEmailSentAt);
    verify($lastConfirmationEmailSentAt->format('Y-m-d H:i:s'))->equals($previousSentAt->format('Y-m-d H:i:s'));
  }

  public function testItLimitsAndRecordsPublicConfirmationEmailsForLoggedInUsers() {
    wp_set_current_user(1);
    verify((new WPFunctions)->isUserLoggedIn())->true();
    $this->subscriber->setConfirmationsCount(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS - 1);
    $this->subscribersRepository->flush();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => Stub\Expected::once(function() {
        return ['response' => true];
      }),
    ], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class),
      $this->diContainer->get(NewslettersRepository::class)
    );

    verify($sender->sendConfirmationEmail($this->subscriber, null, null, true))->equals(true);
    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getConfirmationsCount())->equals(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS);
    verify($this->subscriber->getLastConfirmationEmailSentAt())->notNull();
    $lastConfirmationEmailSentAt = $this->subscriber->getLastConfirmationEmailSentAt();

    verify($sender->sendConfirmationEmail($this->subscriber, null, null, true))->equals(false);
    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getConfirmationsCount())->equals(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS);
    verify($this->subscriber->getLastConfirmationEmailSentAt())->equals($lastConfirmationEmailSentAt);
  }

  public function testFailedPublicConfirmationEmailDoesNotUpdateCountOrTimestamp() {
    $this->subscriber->setConfirmationsCount(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS - 1);
    $this->subscribersRepository->flush();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => Stub\Expected::once(function() {
        throw new \Exception('send error');
      }),
    ], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class),
      $this->diContainer->get(NewslettersRepository::class)
    );

    try {
      $sender->sendConfirmationEmail($this->subscriber, null, null, true);
    } catch (\Exception $e) {
      verify($e->getMessage())->equals(__('There was an error when sending a confirmation email for your subscription. Please contact the website owner.', 'mailpoet'));
    }

    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getConfirmationsCount())->equals(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS - 1);
    verify($this->subscriber->getLastConfirmationEmailSentAt())->null();
  }

  public function testPublicConfirmationCapUsesClaimedDatabaseCount(): void {
    $this->subscriber->setConfirmationsCount(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS - 1);
    $this->subscribersRepository->flush();
    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE $subscriberTable SET `count_confirmations` = :count_confirmations WHERE `id` = :id",
      [
        'id' => $this->subscriber->getId(),
        'count_confirmations' => ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS,
      ]
    );

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => Stub\Expected::never(),
    ], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new ConfirmationEmailMailer(
      $mailerFactory,
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class),
      $this->diContainer->get(NewslettersRepository::class)
    );

    verify($sender->sendConfirmationEmail($this->subscriber, null, null, true))->false();

    verify($this->subscriber->getConfirmationsCount())->equals(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS);
  }

  public function testAvailableWooCommerceFailedPublicSendFallsBackToDefaultMailer(): void {
    $this->subscriber->setConfirmationsCount(1);
    $this->subscribersRepository->flush();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => Stub\Expected::once(function() {
        return ['response' => true];
      }),
    ], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new class(
      $mailerFactory,
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class),
      $this->diContainer->get(NewslettersRepository::class)
    ) extends ConfirmationEmailMailer {
      protected function sendWCConfirmationEmail(SubscriberEntity $subscriber, ?int $confirmationPageId = null): string {
        return self::WC_CONFIRMATION_FAILED;
      }
    };

    verify($sender->sendConfirmationEmail($this->subscriber, null, null, true))->true();
    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getConfirmationsCount())->equals(2);
    verify($this->subscriber->getLastConfirmationEmailSentAt())->notNull();
  }

  public function testAvailableWooCommercePublicSendRecordsSuccessWithoutFallback(): void {
    $this->subscriber->setConfirmationsCount(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS - 1);
    $this->subscribersRepository->flush();

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' => Stub\Expected::never(),
    ], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sender = new class(
      $mailerFactory,
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriptionUrlFactory::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class),
      $this->diContainer->get(NewslettersRepository::class)
    ) extends ConfirmationEmailMailer {
      protected function sendWCConfirmationEmail(SubscriberEntity $subscriber, ?int $confirmationPageId = null): string {
        return self::WC_CONFIRMATION_SENT;
      }
    };

    verify($sender->sendConfirmationEmail($this->subscriber, null, null, true))->true();
    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getConfirmationsCount())->equals(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS);
    verify($this->subscriber->getLastConfirmationEmailSentAt())->notNull();
  }

  public function testGetMailBodyWithCustomizerReplacesActivationShortcode() {
    $subscriptionUrlFactoryMock = $this->createMock(SubscriptionUrlFactory::class);
    $subscriptionUrlFactoryMock->method('getConfirmationUrl')->willReturn('https://example.com');

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
    $settings->set(
      ConfirmationEmailCustomizer::SETTING_ENABLE_EMAIL_CUSTOMIZER,
      true
    );
    $settings->set(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, $newsletter->getId());
    $confirmationEmailCustomizer = $this->diContainer->get(ConfirmationEmailCustomizer::class);

    $sender = new ConfirmationEmailMailer(
      $this->createMock(MailerFactory::class),
      $settings,
      $this->diContainer->get(SubscribersRepository::class),
      $subscriptionUrlFactoryMock,
      $confirmationEmailCustomizer,
      $this->diContainer->get(NewslettersRepository::class)
    );

    $confirmationNewsletter = $confirmationEmailCustomizer->getNewsletter();
    verify($confirmationNewsletter->getId())->equals($newsletter->getId());
    $confirmationMailBody = $sender->getMailBodyWithCustomizer($this->subscriber, ['test_segment']);
    verify($confirmationMailBody['body']['html'])->stringMatchesRegExp('/<a class="mailpoet_button" .* href="https:\/\/example\.com".*>Click here to confirm your subscription<\/a>/');


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
    verify($confirmationMailBody['body']['html'])->stringMatchesRegExp('/<a class="mailpoet_button" .* href="https:\/\/example\.com".*>Click here to confirm your subscription<\/a>/');

  }
}
