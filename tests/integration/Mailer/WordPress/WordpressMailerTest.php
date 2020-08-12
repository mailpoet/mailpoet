<?php

namespace MailPoet\Mailer\WordPress;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Subscribers\SubscribersRepository;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
class WordpressMailerTest extends \MailPoetTest {
  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscribersRepository->truncate();
  }

  public function testItdoesNotSendWhenPreSendCheckFails() {
    $mailer = $this->createMock(Mailer::class);
    $mailer->expects($this->never())->method('send');
    $fallbackMailer = $this->createMock(FallbackMailer::class);
    $fallbackMailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallbackMailer, new MetaInfo, $this->subscribersRepository);
    $wpMailer->From = 'email-from@example.com';
    $this->expectException(\phpmailerException::class);
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
    $fallbackMailer = $this->createMock(FallbackMailer::class);
    $fallbackMailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallbackMailer, new MetaInfo, $this->subscribersRepository);
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
    $fallbackMailer = $this->createMock(FallbackMailer::class);
    $fallbackMailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallbackMailer, new MetaInfo, $this->subscribersRepository);
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
    $fallbackMailer = $this->createMock(FallbackMailer::class);
    $fallbackMailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallbackMailer, new MetaInfo, $this->subscribersRepository);
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
    $fallbackMailer = $this->createMock(FallbackMailer::class);
    $fallbackMailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallbackMailer, new MetaInfo, $this->subscribersRepository);
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
    $fallbackMailer = $this->createMock(FallbackMailer::class);
    $fallbackMailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallbackMailer, new MetaInfo, $this->subscribersRepository);
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

    $fallbackMailer = $this->createMock(FallbackMailer::class);
    $fallbackMailer
      ->expects($this->once())
      ->method('send')
      ->willReturn(['response' => true]);

    $wpMailer = new WordPressMailer($mailer, $fallbackMailer, new MetaInfo, $this->subscribersRepository);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'body';
    expect($wpMailer->send())->true();
  }

  public function testItThrowsOnError() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->willReturn(['response' => false, 'error' => new MailerError('send', MailerError::LEVEL_HARD, 'Error from primary mailer')]);
    $fallbackMailer = $this->createMock(FallbackMailer::class);
    $fallbackMailer
      ->expects($this->once())
      ->method('send')
      ->willReturn(['response' => false, 'error' => new MailerError('send', MailerError::LEVEL_HARD, 'Error from fallback mailer')]);

    $wpMailer = new WordPressMailer($mailer, $fallbackMailer, new MetaInfo, $this->subscribersRepository);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'body';

    $errorMessage = null;
    try {
      $wpMailer->send();
    } catch (\phpmailerException $e) {
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
    $fallbackMailer = $this->createMock(FallbackMailer::class);
    $fallbackMailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallbackMailer, new MetaInfo, $this->subscribersRepository);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Body = 'body';
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->ContentType = 'application/json';
    $this->expectException(\phpmailerException::class);
    $wpMailer->send();
  }

  public function testItTranslateExeceptionsToPhpmailerException() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->willThrowException(new \Exception('Exception from primary mailer'));
    $fallbackMailer = $this->createMock(FallbackMailer::class);
    $fallbackMailer
      ->expects($this->once())
      ->method('send')
      ->willThrowException(new \Exception('Exception from fallback mailer'));

    $wpMailer = new WordPressMailer($mailer, $fallbackMailer, new MetaInfo, $this->subscribersRepository);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'body';

    $errorMessage = null;
    try {
      $wpMailer->send();
    } catch (\phpmailerException $e) {
      $errorMessage = $e->getMessage();
    }

    // ensure exception from primary mailer is thrown
    expect($errorMessage)->same('Exception from primary mailer');
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
    $fallbackMailer = $this->createMock(FallbackMailer::class);
    $fallbackMailer->expects($this->never())->method('send');

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('email@example.com');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setSource('form');
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();

    $wpMailer = new WordPressMailer($mailer, $fallbackMailer, new MetaInfo, $this->subscribersRepository);
    $wpMailer->addAddress('email@example.com', 'Full Name');
    $wpMailer->Subject = 'Subject';
    $wpMailer->From = 'email-from@example.com';
    $wpMailer->Body = 'Body';
    $wpMailer->send();
  }

  public function _after() {
    $this->subscribersRepository->truncate();
  }
}
// phpcs:enable
