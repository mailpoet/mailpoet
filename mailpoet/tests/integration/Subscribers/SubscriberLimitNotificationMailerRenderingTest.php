<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Config\Renderer;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberLimitNotificationMailerRenderingTest extends \MailPoetTest {
  public function testItRendersPercentAndHtmlUpgradeLink(): void {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getOption')->with('admin_email')->willReturn('admin@example.com');
    $wp->method('sanitizeEmail')->with('admin@example.com')->willReturn('admin@example.com');
    $wp->method('isEmail')->with('admin@example.com')->willReturn('admin@example.com');
    $wp->method('adminUrl')->with('admin.php?page=mailpoet-upgrade')->willReturn('https://example.com/wp-admin/admin.php?page=mailpoet-upgrade');

    $nativeMailer = $this->createMock(SubscriberLimitNotificationNativeMailer::class);
    $nativeMailer->expects($this->once())
      ->method('send')
      ->willReturnCallback(function(string $recipient, string $subject, string $htmlBody, string $textBody): bool {
        verify($recipient)->equals('admin@example.com');
        verify($subject)->equals('Your MailPoet subscriber list is at 95% of its limit');
        verify($htmlBody)->stringContainsString('You have reached 95% of your free plan limit.');
        verify($htmlBody)->stringContainsString('<a href="https://example.com/wp-admin/admin.php?page=mailpoet-upgrade">manage your MailPoet plan</a>');
        verify($htmlBody)->stringNotContainsString('95%%');
        verify($htmlBody)->stringNotContainsString('&lt;a href=');
        verify($textBody)->stringContainsString('You have reached 95% of your free plan limit.');
        verify($textBody)->stringContainsString('https://example.com/wp-admin/admin.php?page=mailpoet-upgrade');
        verify($textBody)->stringNotContainsString('95%%');
        return true;
      });

    $mailer = new SubscriberLimitNotificationMailer(
      $this->diContainer->get(Renderer::class),
      $wp,
      $nativeMailer
    );

    verify($mailer->send(95, 950, 1000))->true();
  }
}
