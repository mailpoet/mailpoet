<?php

namespace MailPoet\Mailer\WordPress;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Subscribers\SubscribersRepository;

class WordpressMailerTest extends \MailPoetTest {
  /** @var SubscribersRepository */
  private $subscribers_repository;

  function _before() {
    $this->subscribers_repository = $this->di_container->get(SubscribersRepository::class);
    $this->subscribers_repository->truncate();
  }

  function testItdoesNotSendWhenPreSendCheckFails() {
    $mailer = $this->createMock(Mailer::class);
    $mailer->expects($this->never())->method('send');
    $fallback_mailer = $this->createMock(FallbackMailer::class);
    $fallback_mailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallback_mailer, new MetaInfo, $this->subscribers_repository);
    $this->expectException(\phpmailerException::class);
    $wpMailer->send();
  }

  function testItFormatsTextNewsletterForMailer() {
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
    $fallback_mailer = $this->createMock(FallbackMailer::class);
    $fallback_mailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallback_mailer, new MetaInfo, $this->subscribers_repository);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Subject = 'Subject';
    $wpMailer->Body = 'Email Text Body';
    $wpMailer->isHTML(false);
    $wpMailer->send();
  }

  function testItFormatsSubscriberForMailer() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->with($this->anything(), [
        'full_name' => 'Full Name',
        'address' => 'email@example.com',
      ])
      ->willReturn(['response' => true]);
    $fallback_mailer = $this->createMock(FallbackMailer::class);
    $fallback_mailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallback_mailer, new MetaInfo, $this->subscribers_repository);
    $wpMailer->addAddress('email@example.com', 'Full Name');
    $wpMailer->Subject = 'Subject';
    $wpMailer->Body = 'Body';
    $wpMailer->isHTML(false);
    $wpMailer->send();
  }

  function testItFormatsHtmlNewsletterForMailer() {
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
    $fallback_mailer = $this->createMock(FallbackMailer::class);
    $fallback_mailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallback_mailer, new MetaInfo, $this->subscribers_repository);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Subject = 'Subject';
    $wpMailer->Body = 'Email Html Body';
    $wpMailer->isHTML(true);
    $wpMailer->send();
  }

  function testItReturnsOnSuccess() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->willReturn(['response' => true]);
    $fallback_mailer = $this->createMock(FallbackMailer::class);
    $fallback_mailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallback_mailer, new MetaInfo, $this->subscribers_repository);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Body = 'body';
    expect($wpMailer->send())->true();
  }

  function testItUsesBackupMailerWhenPrimaryFails() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->willThrowException(new \Exception());

    $fallback_mailer = $this->createMock(FallbackMailer::class);
    $fallback_mailer
      ->expects($this->once())
      ->method('send')
      ->willReturn(['response' => true]);

    $wpMailer = new WordPressMailer($mailer, $fallback_mailer, new MetaInfo, $this->subscribers_repository);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Body = 'body';
    expect($wpMailer->send())->true();
  }

  function testItThrowsOnError() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->willReturn(['response' => false, 'error' => new MailerError('send', 1, 'Error from primary mailer')]);
    $fallback_mailer = $this->createMock(FallbackMailer::class);
    $fallback_mailer
      ->expects($this->once())
      ->method('send')
      ->willReturn(['response' => false, 'error' => new MailerError('send', 1, 'Error from fallback mailer')]);

    $wpMailer = new WordPressMailer($mailer, $fallback_mailer, new MetaInfo, $this->subscribers_repository);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Body = 'body';

    $error_message = null;
    try {
      $wpMailer->send();
    } catch (\phpmailerException $e) {
      $error_message = $e->getMessage();
    }

    // ensure error from primary mailer is thrown
    expect($error_message)->same('Error from primary mailer');
  }

  function testItThrowsOnUnknownContentType() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->never())
      ->method('send');
    $fallback_mailer = $this->createMock(FallbackMailer::class);
    $fallback_mailer->expects($this->never())->method('send');

    $wpMailer = new WordPressMailer($mailer, $fallback_mailer, new MetaInfo, $this->subscribers_repository);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Body = 'body';
    $wpMailer->ContentType = 'application/json';
    $this->expectException(\phpmailerException::class);
    $wpMailer->send();
  }

  function testItTranslateExeceptionsToPhpmailerException() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->willThrowException(new \Exception('Exception from primary mailer'));
    $fallback_mailer = $this->createMock(FallbackMailer::class);
    $fallback_mailer
      ->expects($this->once())
      ->method('send')
      ->willThrowException(new \Exception('Exception from fallback mailer'));

    $wpMailer = new WordPressMailer($mailer, $fallback_mailer, new MetaInfo, $this->subscribers_repository);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Body = 'body';

    $error_message = null;
    try {
      $wpMailer->send();
    } catch (\phpmailerException $e) {
      $error_message = $e->getMessage();
    }

    // ensure exception from primary mailer is thrown
    expect($error_message)->same('Exception from primary mailer');
  }

  function testItAddSubscriberMetaInfo() {
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
    $fallback_mailer = $this->createMock(FallbackMailer::class);
    $fallback_mailer->expects($this->never())->method('send');

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('email@example.com');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setSource('form');
    $this->subscribers_repository->persist($subscriber);
    $this->subscribers_repository->flush();

    $wpMailer = new WordPressMailer($mailer, $fallback_mailer, new MetaInfo, $this->subscribers_repository);
    $wpMailer->addAddress('email@example.com', 'Full Name');
    $wpMailer->Subject = 'Subject';
    $wpMailer->Body = 'Body';
    $wpMailer->send();
  }

  function _after() {
    $this->subscribers_repository->truncate();
  }
}
