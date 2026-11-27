<?php declare(strict_types = 1);

namespace MailPoet\Mailer\WordPress;

use Codeception\Stub\Expected;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Mailer\Methods\AmazonSES;
use MailPoet\Mailer\Methods\MailPoet;
use MailPoet\Mailer\Methods\SMTP;
use MailPoet\Subscribers\ApiSourcePluginResolver;
use MailPoet\Subscribers\SourceUrlResolver;
use MailPoet\Subscribers\SubscribersRepository;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPUnit\Framework\MockObject\MockObject;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class WordpressMailerTest extends \MailPoetTest {
  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var ApiSourcePluginResolver */
  private $sourcePluginResolver;

  /** @var SourceUrlResolver */
  private $sourceUrlResolver;

  public function _before() {
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->sourcePluginResolver = $this->diContainer->get(ApiSourcePluginResolver::class);
    $this->sourceUrlResolver = $this->diContainer->get(SourceUrlResolver::class);
  }

  public function testItDoesNotSendWhenPreSendCheckFails() {
    $mailerFactoryMock = $this->createMock(MailerFactory::class);
    $mailerFactoryMock->expects($this->never())->method('buildMailer');

    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    $wpMailer->From = 'email-from@example.com';
    $this->expectException(PHPMailerException::class);
    $wpMailer->send();
  }

  public function testItFormatsTextNewsletterForMailer() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->with($this->equalTo([
        'subject' => 'Subject',
        'body' => [
          'text' => 'Email Text Body',
        ],
      ]))
      ->willReturn(['response' => true]);
    $mailerFactoryMock = $this->createMailerFactoryMock($mailer);

    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Subject = 'Subject';
    $wpMailer->Body = 'Email Text Body';
    $wpMailer->isHTML(false);
    $wpMailer->send();
  }

  public function testItFormatsSubscriberForMailer() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->with($this->anything(), [
        'full_name' => 'Full Name',
        'address' => 'email@example.com',
      ])
      ->willReturn(['response' => true]);
    $mailerFactoryMock = $this->createMailerFactoryMock($mailer);

    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    $wpMailer->addAddress('email@example.com', 'Full Name');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Subject = 'Subject';
    $wpMailer->Body = 'Body';
    $wpMailer->isHTML(false);
    $wpMailer->send();
  }

  public function testItPassesAdditionalToCcAndBccRecipientsToMailer() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->with(
        $this->anything(),
        ['address' => 'to1@example.com'],
        $this->callback(function(array $extraParams): bool {
          return $extraParams['recipients'] === [
            'to' => [['address' => 'to2@example.com', 'name' => 'To 2']],
            'cc' => [['address' => 'cc@example.com', 'name' => '']],
            'bcc' => [['address' => 'bcc@example.com', 'name' => 'Bcc']],
          ];
        })
      )
      ->willReturn(['response' => true]);
    $mailerFactoryMock = $this->createMailerFactoryMock($mailer);

    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    $wpMailer->addAddress('to1@example.com');
    $wpMailer->addAddress('to2@example.com', 'To 2');
    $wpMailer->addCC('cc@example.com');
    $wpMailer->addBCC('bcc@example.com', 'Bcc');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Subject = 'Subject';
    $wpMailer->Body = 'Body';
    $wpMailer->isHTML(false);
    $wpMailer->send();
  }

  public function testItCapsToCcAndBccRecipientsToFiveForTheSendingService() {
    $mailer = $this->createMock(Mailer::class);
    $mailer->mailerMethod = $this->createMock(MailPoet::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->with(
        $this->anything(),
        $this->anything(),
        $this->callback(function(array $extraParams): bool {
          $addresses = function(array $entries): array {
            return array_column($entries, 'address');
          };
          return $addresses($extraParams['recipients']['to']) === ['to2@example.com', 'to3@example.com', 'to4@example.com', 'to5@example.com']
            && $addresses($extraParams['recipients']['cc']) === ['cc1@example.com', 'cc2@example.com', 'cc3@example.com', 'cc4@example.com', 'cc5@example.com']
            && $addresses($extraParams['recipients']['bcc']) === ['bcc1@example.com', 'bcc2@example.com', 'bcc3@example.com', 'bcc4@example.com', 'bcc5@example.com'];
        })
      )
      ->willReturn(['response' => true]);
    $mailerFactoryMock = $this->createMailerFactoryMock($mailer);

    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    for ($i = 1; $i <= 6; $i++) {
      $wpMailer->addAddress("to$i@example.com");
      $wpMailer->addCC("cc$i@example.com");
      $wpMailer->addBCC("bcc$i@example.com");
    }
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Subject = 'Subject';
    $wpMailer->Body = 'Body';
    $wpMailer->isHTML(false);
    $wpMailer->send();
  }

  public function testItDoesNotCapRecipientsForSmtp() {
    $mailer = $this->createMock(Mailer::class);
    $mailer->mailerMethod = $this->createMock(SMTP::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->with(
        $this->anything(),
        $this->anything(),
        $this->callback(function(array $extraParams): bool {
          return count($extraParams['recipients']['to']) === 5
            && count($extraParams['recipients']['cc']) === 6
            && count($extraParams['recipients']['bcc']) === 6;
        })
      )
      ->willReturn(['response' => true]);

    $wpMailer = $this->createWordPressMailerWithRecipients($this->createMailerFactoryMock($mailer), 6, 6, 6);
    $wpMailer->send();
  }

  public function testItCapsAmazonSesAtFiftyRecipientsPerEmail() {
    $mailer = $this->createMock(Mailer::class);
    $mailer->mailerMethod = $this->createMock(AmazonSES::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->with(
        $this->anything(),
        $this->anything(),
        $this->callback(function(array $extraParams): bool {
          return count($extraParams['recipients']['to']) === 29
            && count($extraParams['recipients']['cc']) === 20
            && !isset($extraParams['recipients']['bcc']);
        })
      )
      ->willReturn(['response' => true]);

    $wpMailer = $this->createWordPressMailerWithRecipients($this->createMailerFactoryMock($mailer), 30, 30, 5);
    $wpMailer->send();
  }

  private function createWordPressMailerWithRecipients(MailerFactory $mailerFactory, int $to, int $cc, int $bcc): WordPressMailer {
    $wpMailer = new WordPressMailer($mailerFactory, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    for ($i = 1; $i <= $to; $i++) {
      $wpMailer->addAddress("to$i@example.com");
    }
    for ($i = 1; $i <= $cc; $i++) {
      $wpMailer->addCC("cc$i@example.com");
    }
    for ($i = 1; $i <= $bcc; $i++) {
      $wpMailer->addBCC("bcc$i@example.com");
    }
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Subject = 'Subject';
    $wpMailer->Body = 'Body';
    $wpMailer->isHTML(false);
    return $wpMailer;
  }

  public function testItFormatsHtmlNewsletterForMailer() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->with($this->equalTo([
        'subject' => 'Subject',
        'body' => [
          'text' => 'Email Html Body',
          'html' => 'Email Html Body',
        ],
      ]))
      ->willReturn(['response' => true]);

    $mailerFactoryMock = $this->createMailerFactoryMock($mailer);
    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Subject = 'Subject';
    $wpMailer->Body = 'Email Html Body';
    $wpMailer->isHTML(true);
    $wpMailer->send();
  }

  public function testItFormatsMultipartNewsletterForMailer() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->with($this->equalTo([
        'subject' => 'Subject',
        'body' => [
          'text' => 'Email Text Body',
          'html' => 'Email Html Body',
        ],
      ]))
      ->willReturn(['response' => true]);

    $mailerFactoryMock = $this->createMailerFactoryMock($mailer);
    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Subject = 'Subject';
    $wpMailer->Body = 'Email Html Body';
    $wpMailer->AltBody = 'Email Text Body';
    $wpMailer->isHTML(true);
    $wpMailer->ContentType = 'multipart/alternative';
    $wpMailer->send();
  }

  public function testItReturnsOnSuccess() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->willReturn(['response' => true]);
    $mailerFactoryMock = $this->createMailerFactoryMock($mailer);

    $wpMailer = $this->construct(
      WordPressMailer::class,
      [
        'mailerFactory' => $mailerFactoryMock,
        'mailerMetaInfo' => new MetaInfo,
        'subscribersRepository' => $this->subscribersRepository,
        'sourcePluginResolver' => $this->sourcePluginResolver,
        'sourceUrlResolver' => $this->sourceUrlResolver,
      ],
      [
        'postSend' => Expected::never(), // When Mailer instance provided by MailPoet sends successfully postSend is not called
      ]
    );

    $wpMailer->addAddress('email@example.com');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'body';
    verify($wpMailer->send())->true();
  }

  public function testItUsesBackupMailerWhenPrimaryFails() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->willThrowException(new \Exception());

    $mailerFactoryMock = $this->createMailerFactoryMock($mailer);

    $wpMailer = $this->construct(
      WordPressMailer::class,
      [
        'mailerFactory' => $mailerFactoryMock,
        'mailerMetaInfo' => new MetaInfo,
        'subscribersRepository' => $this->subscribersRepository,
        'sourcePluginResolver' => $this->sourcePluginResolver,
        'sourceUrlResolver' => $this->sourceUrlResolver,
      ],
      [
        'postSend' => Expected::once(true),
      ]
    );

    $wpMailer->addAddress('email@example.com');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'body';
    verify($wpMailer->send())->true();
  }

  public function testItThrowsPhpmailerExceptionOnError() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->willReturn(['response' => false, 'error' => new MailerError('send', MailerError::LEVEL_HARD, 'Error from primary mailer')]);
    $mailerFactoryMock = $this->createMailerFactoryMock($mailer);

    $wpMailer = $this->construct(
      WordPressMailer::class,
      [
        'mailerFactory' => $mailerFactoryMock,
        'mailerMetaInfo' => new MetaInfo,
        'subscribersRepository' => $this->subscribersRepository,
        'sourcePluginResolver' => $this->sourcePluginResolver,
        'sourceUrlResolver' => $this->sourceUrlResolver,
      ],
      [
        'postSend' => function() { throw new \Exception('Some strange error in mailer');
        },
      ]
    );

    $wpMailer->addAddress('email@example.com');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'body';

    $errorMessage = null;
    try {
      $wpMailer->send();
    } catch (PHPMailerException $e) {
      $errorMessage = $e->getMessage();
    }

    // ensure error from primary mailer is thrown
    verify($errorMessage)->same('Error from primary mailer');
  }

  public function testItThrowsOnUnknownContentType() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->never())
      ->method('send');

    $mailerFactoryMock = $this->createMailerFactoryMock($mailer);
    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Body = 'body';
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->ContentType = 'application/json';
    $this->expectException(PHPMailerException::class);
    $wpMailer->send();
  }

  public function testItAddSubscriberMetaInfo() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->with($this->anything(), $this->anything(), [
        'meta' => [
          'email_type' => 'transactional',
          'subscriber_status' => 'subscribed',
          'subscriber_source' => 'form',
          'subscriber_source_plugin' => 'mailpoet',
        ],
      ])
      ->willReturn(['response' => true]);
    $mailerFactoryMock = $this->createMailerFactoryMock($mailer);

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('email@example.com');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setSource('form');
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();

    $pluginResolver = $this->createMock(ApiSourcePluginResolver::class);
    $pluginResolver->method('resolve')->willReturn(null);
    $urlResolver = $this->createMock(SourceUrlResolver::class);
    $urlResolver->method('resolve')->willReturn(null);

    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository, $pluginResolver, $urlResolver);
    $wpMailer->addAddress('email@example.com', 'Full Name');
    $wpMailer->Subject = 'Subject';
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'Body';
    $wpMailer->send();
  }

  public function testItAddsSourcePluginAndUrlToMetaInfo() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->with($this->anything(), $this->anything(), [
        'meta' => [
          'email_type' => 'transactional',
          'subscriber_status' => 'unknown',
          'subscriber_source' => 'unknown',
          'subscriber_source_plugin' => 'woocommerce',
          'subscriber_source_url' => 'https://example.com/checkout/',
        ],
      ])
      ->willReturn(['response' => true]);
    $pluginResolver = $this->createMock(ApiSourcePluginResolver::class);
    $pluginResolver->method('resolve')->willReturn('woocommerce');
    $urlResolver = $this->createMock(SourceUrlResolver::class);
    $urlResolver->method('resolve')->willReturn('https://example.com/checkout/');

    $wpMailer = new WordPressMailer($this->createMailerFactoryMock($mailer), new MetaInfo, $this->subscribersRepository, $pluginResolver, $urlResolver);
    $wpMailer->addAddress('not-a-subscriber@example.com');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'Body';
    $wpMailer->send();
  }

  /*
   * Test for issue https://mailpoet.atlassian.net/browse/MAILPOET-3707
   */
  public function testItUsesWPReplyTo() {
    $mailerFactory = $this->getMailerFactoryInstanceForReplyToTests();
    $mailerFactory->expects($this->once())
      ->method('buildMailer')
      ->with($this->isNull(), $this->isNull(), $this->equalTo([
        'address' => 'reply-to@example.com',
        'name' => 'Reply To',
      ]));

    $wpMailer = new WordPressMailer($mailerFactory, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    $wpMailer->addAddress('email@example.com', 'Full Name');
    $wpMailer->addReplyTo('reply-to@example.com', 'Reply To');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'Body';
    $wpMailer->send();
  }

  /*
   * Test for issue https://mailpoet.atlassian.net/browse/MAILPOET-3707
   */
  public function testItDoesntPassAnyReplyToToMailerFactoryWhenNoReplyToAddressConfigured() {
    $mailerFactory = $this->getMailerFactoryInstanceForReplyToTests();
    $mailerFactory->expects($this->once())
      ->method('buildMailer')
      ->with($this->isNull(), $this->isNull(), $this->isNull());

    $wpMailer = new WordPressMailer($mailerFactory, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    $wpMailer->addAddress('email@example.com', 'Full Name');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'Body';
    $wpMailer->send();
  }

  /*
   * Test for issue https://mailpoet.atlassian.net/browse/MAILPOET-3707
   */
  public function testItChangesReplyToEmailOnDifferentCalls() {
    $mailerFactory = $this->getMailerFactoryInstanceForReplyToTests();

    $mailerFactory->expects($this->exactly(2))
      ->method('buildMailer')
      ->withConsecutive(
        [null, null, ['address' => 'reply-to@example.com', 'name' => 'Reply To',]],
        [null, null, null]
      );

    $wpMailer = new WordPressMailer($mailerFactory, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    $wpMailer->addAddress('email@example.com', 'Full Name');
    $wpMailer->addReplyTo('reply-to@example.com', 'Reply To');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'Body';
    $wpMailer->send();

    $wpMailer = new WordPressMailer($mailerFactory, new MetaInfo, $this->subscribersRepository, $this->sourcePluginResolver, $this->sourceUrlResolver);
    $wpMailer->addAddress('email@example.com', 'Full Name');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'Body';
    $wpMailer->send();
  }

  private function getMailerFactoryInstanceForReplyToTests() {
    $mailer = $this->createMock(Mailer::class);
    $mailer->expects($this->any())->method('send')->willReturn(['response' => true]);
    return $this->createMailerFactoryMock($mailer);
  }

  /**
   * @return MailerFactory|MockObject
   */
  private function createMailerFactoryMock(Mailer $mailerMock) {
    $mock = $this->createMock(MailerFactory::class);
    $mock->method('buildMailer')->willReturn($mailerMock);
    return $mock;
  }
}
// phpcs:enable
