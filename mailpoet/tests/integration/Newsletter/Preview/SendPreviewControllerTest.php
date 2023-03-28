<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Preview;

use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Newsletter\Url;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

class SendPreviewControllerTest extends \MailPoetTest {
  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var Url */
  private $newsletterUrl;

  public function _before() {
    parent::_before();
    $this->newsletterUrl = $this->diContainer->get(Url::class);
    $this->subscriptionUrlFactory = SubscriptionUrlFactory::getInstance();
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('My Standard Newsletter SendPreviewControllerTest');
    $newsletter->setPreheader('preheader');
    $body = json_decode(Fixtures::get('newsletter_body_template'), true);
    $this->assertIsArray($body);
    $newsletter->setBody($body);
    $newsletter->setHash(Security::generateHash());
    $this->entityManager->persist($newsletter);

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('test@subscriber.com');
    $subscriber->setWpUserId(5);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();

    $wpUser = new \stdClass();
    $wpUser->ID = 5;
    $wp = $this->make(WPFunctions::class, ['wpGetCurrentUser' => $wpUser]);
    WPFunctions::set($wp);

    $this->newsletter = $newsletter;
  }

  public function _after() {
    WPFunctions::set(new WPFunctions());
  }

  public function testItCanSendAPreview() {
    $mailer = $this->makeEmpty(Mailer::class, [
      'send' => Expected::once(
        function ($newsletter, $subscriber, $extraParams) {
          $unsubscribeLink = $this->subscriptionUrlFactory->getConfirmUnsubscribeUrl(null);
          $manageLink = $this->subscriptionUrlFactory->getManageUrl(null);
          $viewInBrowserLink = $this->newsletterUrl->getViewInBrowserUrl($this->newsletter);
          $mailerMetaInfo = new MetaInfo;

          expect(is_array($newsletter))->true();
          expect($newsletter['body']['text'])->stringContainsString('Hello test');
          expect($subscriber)->equals($subscriber);
          expect($extraParams['unsubscribe_url'])->equals(home_url());
          expect($extraParams['meta'])->equals($mailerMetaInfo->getPreviewMetaInfo());

          // system links are replaced with hashes
          expect($newsletter['body']['html'])->stringContainsString('href="' . $viewInBrowserLink . '">View in browser');
          expect($newsletter['body']['html'])->stringContainsString('href="' . $unsubscribeLink . '">Unsubscribe');
          expect($newsletter['body']['html'])->stringContainsString('href="' . $manageLink . '">Manage subscription');
          return ['response' => true];
        }
      ),
    ]);

    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $shortcodes = $this->diContainer->get(Shortcodes::class);
    $shortcodes->setQueue(null);
    $sendPreviewController = new SendPreviewController(
      $mailerFactory,
      new MetaInfo(),
      $this->diContainer->get(Renderer::class),
      new WPFunctions(),
      $this->diContainer->get(SubscribersRepository::class),
      $shortcodes
    );
    $sendPreviewController->sendPreview($this->newsletter, 'test@subscriber.com');
  }

  public function testItThrowsWhenSendingFailed() {
    $mailer = $this->makeEmpty(Mailer::class, [
      'send' => function ($newsletter, $subscriber) {
        expect(is_array($newsletter))->true();
        expect($newsletter['body']['text'])->stringContainsString('Hello test');
        expect($subscriber)->equals($subscriber);
        return [
          'response' => false,
          'error' => $this->make(MailerError::class, [
            'getMessage' => 'failed',
          ]),
        ];
      },
    ]);

    $this->expectException(SendPreviewException::class);
    $this->expectExceptionMessage('The email could not be sent: failed');

    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $sendPreviewController = new SendPreviewController(
      $mailerFactory,
      new MetaInfo(),
      $this->diContainer->get(Renderer::class),
      new WPFunctions(),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(Shortcodes::class)
    );
    $sendPreviewController->sendPreview($this->newsletter, 'test@subscriber.com');
  }
}
