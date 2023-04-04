<?php declare(strict_types = 1);

namespace MailPoet\Mailer\WordPress;

use Codeception\Stub\Expected;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Subscribers\SubscribersRepository;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPUnit\Framework\MockObject\MockObject;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class WordpressMailerTest extends \MailPoetTest {
  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
  }

  public function testItDoesNotSendWhenPreSendCheckFails() {
    $mailerFactoryMock = $this->createMock(MailerFactory::class);
    $mailerFactoryMock->expects($this->never())->method('buildMailer');

    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository);
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

    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository);
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

    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository);
    $wpMailer->addAddress('email@example.com', 'Full Name');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Subject = 'Subject';
    $wpMailer->Body = 'Body';
    $wpMailer->isHTML(false);
    $wpMailer->send();
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
    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository);
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
    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository);
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
      ],
      [
        'postSend' => Expected::never(), // When Mailer instance provided by MailPoet sends successfully postSend is not called
      ]
    );

    $wpMailer->addAddress('email@example.com');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'body';
    expect($wpMailer->send())->true();
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
      ],
      [
        'postSend' => Expected::once(true),
      ]
    );

    $wpMailer->addAddress('email@example.com');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'body';
    expect($wpMailer->send())->true();
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
    expect($errorMessage)->same('Error from primary mailer');
  }

  public function testItThrowsOnUnknownContentType() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->never())
      ->method('send');

    $mailerFactoryMock = $this->createMailerFactoryMock($mailer);
    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository);
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

    $wpMailer = new WordPressMailer($mailerFactoryMock, new MetaInfo, $this->subscribersRepository);
    $wpMailer->addAddress('email@example.com', 'Full Name');
    $wpMailer->Subject = 'Subject';
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

    $wpMailer = new WordPressMailer($mailerFactory, new MetaInfo, $this->subscribersRepository);
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

    $wpMailer = new WordPressMailer($mailerFactory, new MetaInfo, $this->subscribersRepository);
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
    $mailerFactory->expects($this->at(0))
      ->method('buildMailer')
      ->with($this->isNull(), $this->isNull(), $this->equalTo([
        'address' => 'reply-to@example.com',
        'name' => 'Reply To',
      ]));
    $mailerFactory->expects($this->at(1))
      ->method('buildMailer')
      ->with($this->isNull(), $this->isNull(), $this->isNull());

    $wpMailer = new WordPressMailer($mailerFactory, new MetaInfo, $this->subscribersRepository);
    $wpMailer->addAddress('email@example.com', 'Full Name');
    $wpMailer->addReplyTo('reply-to@example.com', 'Reply To');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'Body';
    $wpMailer->send();

    $wpMailer = new WordPressMailer($mailerFactory, new MetaInfo, $this->subscribersRepository);
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

  public function _after() {
    parent::_after();
  }
}
// phpcs:enable
