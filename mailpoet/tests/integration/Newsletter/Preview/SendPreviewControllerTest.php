<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Preview;

use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTagManager;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\PersonalizationTagLinkResolver;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\WpPostEntity;
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

  public function testItCanSendAPreview() {
    $mailer = $this->makeEmpty(Mailer::class, [
      'send' => Expected::once(
        function ($newsletter, $subscriber, $extraParams) {
          $unsubscribeLink = $this->subscriptionUrlFactory->getConfirmUnsubscribeUrl(null);
          $manageLink = $this->subscriptionUrlFactory->getManageUrl(null);
          $viewInBrowserLink = $this->newsletterUrl->getViewInBrowserUrl($this->newsletter);
          $mailerMetaInfo = new MetaInfo;

          verify(is_array($newsletter))->true();
          verify($newsletter['body']['text'])->stringContainsString('Hello test');
          verify($subscriber)->equals($subscriber);
          verify($extraParams['unsubscribe_url'])->equals(home_url());
          verify($extraParams['meta'])->equals($mailerMetaInfo->getPreviewMetaInfo());

          // system links are replaced with hashes
          verify($newsletter['body']['html'])->stringContainsString('href="' . $viewInBrowserLink . '">View in browser');
          verify($newsletter['body']['html'])->stringContainsString('href="' . $unsubscribeLink . '">Unsubscribe');
          verify($newsletter['body']['html'])->stringContainsString('href="' . $manageLink . '">Manage subscription');
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
      $shortcodes,
      $this->diContainer->get(PersonalizationTagManager::class),
      $this->diContainer->get(WooCommerceDummyData::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class)
    );
    $sendPreviewController->sendPreview($this->newsletter, 'test@subscriber.com');
  }

  public function testItPersonalizesSubjectHtmlAndTextWithProperEncoding() {
    $postId = wp_insert_post([
      'post_type' => 'mailpoet_email',
      'post_status' => 'private',
      'post_title' => 'Preview personalization',
      'post_content' => '',
    ]);
    $this->assertIsInt($postId);
    $this->assertGreaterThan(0, $postId);
    $this->newsletter->setWpPost($this->entityManager->getReference(WpPostEntity::class, $postId));
    $this->newsletter->setSubject('Hello <!--[mailpoet/subscriber-firstname default="subscriber"]-->');

    $subscriber = $this->diContainer->get(SubscribersRepository::class)->findOneBy(['email' => 'test@subscriber.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscriber->setFirstName('Tom & <b>Jerry</b>');
    $this->entityManager->flush();

    $renderer = $this->makeEmpty(Renderer::class, [
      'renderAsPreview' => [
        'html' => '<p>Hi <!--[mailpoet/subscriber-firstname default="subscriber"]--></p>',
        'text' => 'Hi <!--[mailpoet/subscriber-firstname default="subscriber"]-->',
      ],
    ]);

    $mailer = $this->makeEmpty(Mailer::class, [
      'send' => Expected::once(
        function ($newsletter) {
          verify($newsletter['subject'])->equals('Hello Tom & <b>Jerry</b>');
          verify($newsletter['body']['text'])->equals('Hi Tom & <b>Jerry</b>');
          verify($newsletter['body']['html'])->equals('<p>Hi Tom &amp; &lt;b&gt;Jerry&lt;/b&gt;</p>');
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
      $renderer,
      new WPFunctions(),
      $this->diContainer->get(SubscribersRepository::class),
      $shortcodes,
      $this->diContainer->get(PersonalizationTagManager::class),
      $this->diContainer->get(WooCommerceDummyData::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class)
    );
    $sendPreviewController->sendPreview($this->newsletter, 'test@subscriber.com');
  }

  public function testItThrowsWhenSendingFailed() {
    $mailer = $this->makeEmpty(Mailer::class, [
      'send' => function ($newsletter, $subscriber) {
        verify(is_array($newsletter))->true();
        verify($newsletter['body']['text'])->stringContainsString('Hello test');
        verify($subscriber)->equals($subscriber);
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
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(PersonalizationTagManager::class),
      $this->diContainer->get(WooCommerceDummyData::class),
      $this->diContainer->get(PersonalizationTagLinkResolver::class)
    );
    $sendPreviewController->sendPreview($this->newsletter, 'test@subscriber.com');
  }
}
