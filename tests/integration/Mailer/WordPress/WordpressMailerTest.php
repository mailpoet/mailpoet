<?php

namespace MailPoet\Mailer\WordPress;

if (!class_exists('PHPMailer')) {
  // this has to be here otherwise AspectMock is trying to load the PHPMailer and doesn't find it
  require_once ABSPATH . WPINC . '/class-phpmailer.php';
}

use MailPoet\Mailer\Mailer;

class WordpressMailerTest extends \MailPoetTest {

  function testItdoesNotSendWhenPreSendCheckFails() {
    $mailer = $this->createMock(Mailer::class);
    $mailer->expects($this->never())->method('send');
    $wpMailer = new WordPressMailer($mailer);
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
        ]
      ]))
      ->willReturn(['response' => true]);
    $wpMailer = new WordPressMailer($mailer);
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
    $wpMailer = new WordPressMailer($mailer);
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
        ]
      ]))
      ->willReturn(['response' => true]);
    $wpMailer = new WordPressMailer($mailer);
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
    $wpMailer = new WordPressMailer($mailer);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Body = 'body';
    expect($wpMailer->send())->true();
  }

  function testItThrowsOnError() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->once())
      ->method('send')
      ->willReturn(['response' => false, 'error' => 'Big Error']);
    $wpMailer = new WordPressMailer($mailer);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Body = 'body';
    $this->expectException(\phpmailerException::class);
    $wpMailer->send();
  }

  function testItThrowsOnUnknownContentType() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->never())
      ->method('send');
    $wpMailer = new WordPressMailer($mailer);
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
      ->willThrowException(new \Exception('Big Error'));
    $wpMailer = new WordPressMailer($mailer);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Body = 'body';
    $this->expectException(\phpmailerException::class);
    $wpMailer->send();
  }

  function testItThrowsOnUnknownContentType() {
    $mailer = $this->createMock(Mailer::class);
    $mailer
      ->expects($this->never())
      ->method('send');
    $wpMailer = new WordpressMailer($mailer);
    $wpMailer->addAddress('email@example.com');
    $wpMailer->Body = 'body';
    $wpMailer->ContentType = 'application/json';
    $this->expectException(\phpmailerException::class);
    expect($wpMailer->send());
  }

}
