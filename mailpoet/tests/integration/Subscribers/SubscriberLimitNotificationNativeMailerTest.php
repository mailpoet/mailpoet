<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Mailer\WordPress\WordPressMailer;
use MailPoet\WP\Functions as WPFunctions;
use PHPMailer\PHPMailer\PHPMailer;

class SubscriberLimitNotificationNativeMailerTest extends \MailPoetTest {
  public function testItTemporarilyUsesCorePhpMailerAndRestoresPreviousMailer(): void {
    global $phpmailer;

    $originalMailer = $phpmailer ?? null;
    $previousMailer = $this->getMockBuilder(WordPressMailer::class)
      ->disableOriginalConstructor()
      ->getMock();
    $phpmailer = $previousMailer;
    $addedCallback = null;
    $removedCallback = null;

    try {
      $wp = $this->createMock(WPFunctions::class);
      $wp->expects($this->once())
        ->method('addAction')
        ->with('phpmailer_init', $this->callback('is_callable'), PHP_INT_MAX, 1)
        ->willReturnCallback(function(string $hook, callable $callback) use (&$addedCallback): bool {
          $addedCallback = $callback;
          return true;
        });
      $wp->expects($this->once())
        ->method('removeAction')
        ->with('phpmailer_init', $this->callback('is_callable'), PHP_INT_MAX)
        ->willReturnCallback(function(string $hook, callable $callback) use (&$addedCallback, &$removedCallback): bool {
          $removedCallback = $callback;
          $this->assertSame($addedCallback, $callback);
          return true;
        });
      $wp->expects($this->once())
        ->method('wpMail')
        ->willReturnCallback(function() use (&$addedCallback, $previousMailer): bool {
          $currentMailer = $GLOBALS['phpmailer'] ?? null;
          $this->assertInstanceOf(PHPMailer::class, $currentMailer);
          $this->assertNotInstanceOf(WordPressMailer::class, $currentMailer);
          $this->assertNotSame($previousMailer, $currentMailer);
          $this->assertIsCallable($addedCallback);
          $addedCallback($currentMailer);
          $this->assertSame('Text body', $currentMailer->AltBody); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return true;
        });

      $mailer = new SubscriberLimitNotificationNativeMailer($wp);
      verify($mailer->send('admin@example.com', 'Subject', '<p>HTML body</p>', 'Text body'))->true();
      $this->assertSame($previousMailer, $phpmailer);
      $this->assertSame($addedCallback, $removedCallback);
    } finally {
      $phpmailer = $originalMailer;
    }
  }

  public function testItRestoresPreviousMailerWhenWpMailFails(): void {
    global $phpmailer;

    $originalMailer = $phpmailer ?? null;
    $previousMailer = $this->getMockBuilder(WordPressMailer::class)
      ->disableOriginalConstructor()
      ->getMock();
    $phpmailer = $previousMailer;
    $addedCallback = null;
    $removedCallback = null;

    try {
      $wp = $this->createMock(WPFunctions::class);
      $wp->expects($this->once())
        ->method('addAction')
        ->willReturnCallback(function(string $hook, callable $callback) use (&$addedCallback): bool {
          $addedCallback = $callback;
          return true;
        });
      $wp->expects($this->once())
        ->method('removeAction')
        ->willReturnCallback(function(string $hook, callable $callback) use (&$removedCallback): bool {
          $removedCallback = $callback;
          return true;
        });
      $wp->expects($this->once())
        ->method('wpMail')
        ->willThrowException(new \RuntimeException('wp_mail failed'));

      $mailer = new SubscriberLimitNotificationNativeMailer($wp);
      try {
        $mailer->send('admin@example.com', 'Subject', '<p>HTML body</p>', 'Text body');
        $this->fail('Expected wpMail failure to be rethrown.');
      } catch (\RuntimeException $e) {
        $this->assertSame('wp_mail failed', $e->getMessage());
      }

      $this->assertSame($previousMailer, $phpmailer);
      $this->assertSame($addedCallback, $removedCallback);
    } finally {
      $phpmailer = $originalMailer;
    }
  }
}
