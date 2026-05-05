<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Config\Renderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberLimitNotificationMailerRenderingTest extends \MailPoetTest {
  public function testItRendersFreeLimitCopyAndHtmlUpgradeLink(): void {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getOption')->with('admin_email')->willReturn('admin@example.com');
    $wp->method('sanitizeEmail')->with('admin@example.com')->willReturn('admin@example.com');
    $wp->method('isEmail')->with('admin@example.com')->willReturn('admin@example.com');

    $servicesChecker = $this->createMock(ServicesChecker::class);
    $servicesChecker->expects($this->never())->method('generatePartialApiKey');

    $nativeMailer = $this->createMock(SubscriberLimitNotificationNativeMailer::class);
    $nativeMailer->expects($this->once())
      ->method('send')
      ->willReturnCallback(function(string $recipient, string $subject, string $htmlBody, string $textBody): bool {
        verify($recipient)->equals('admin@example.com');
        verify($subject)->equals('Your MailPoet subscriber list is at 95% of its limit');
        verify($htmlBody)->stringContainsString('You have reached 95% of the free version’s subscriber limit.');
        verify($htmlBody)->stringContainsString('<a href="https://account.mailpoet.com/?s=1001">view MailPoet plans</a>');
        verify($htmlBody)->stringNotContainsString('95%%');
        verify($htmlBody)->stringNotContainsString('&lt;a href=');
        verify($textBody)->stringContainsString('You have reached 95% of the free version’s subscriber limit.');
        verify($textBody)->stringContainsString('Upgrade to a MailPoet plan to keep growing your audience: view MailPoet plans.');
        verify($textBody)->stringContainsString('https://account.mailpoet.com/?s=1001');
        verify($textBody)->stringNotContainsString('95%%');
        return true;
      });

    $mailer = new SubscriberLimitNotificationMailer(
      $this->diContainer->get(Renderer::class),
      $wp,
      $nativeMailer,
      $servicesChecker
    );

    verify($mailer->send(95, 950, 1000, false))->true();
  }

  public function testItRendersPlanLimitCopyAndHtmlUpgradeLink(): void {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getOption')->with('admin_email')->willReturn('admin@example.com');
    $wp->method('sanitizeEmail')->with('admin@example.com')->willReturn('admin@example.com');
    $wp->method('isEmail')->with('admin@example.com')->willReturn('admin@example.com');

    $servicesChecker = $this->createMock(ServicesChecker::class);
    $servicesChecker->expects($this->once())->method('generatePartialApiKey')->willReturn('abc123');

    $nativeMailer = $this->createMock(SubscriberLimitNotificationNativeMailer::class);
    $nativeMailer->expects($this->once())
      ->method('send')
      ->willReturnCallback(function(string $recipient, string $subject, string $htmlBody, string $textBody): bool {
        verify($recipient)->equals('admin@example.com');
        verify($subject)->equals('Your MailPoet subscriber list is at 95% of its limit');
        verify($htmlBody)->stringContainsString('You have reached 95% of your MailPoet plan’s subscriber limit.');
        verify($htmlBody)->stringContainsString('<a href="https://account.mailpoet.com/orders/upgrade/abc123">manage your MailPoet plan</a>');
        verify($htmlBody)->stringNotContainsString('95%%');
        verify($htmlBody)->stringNotContainsString('&lt;a href=');
        verify($textBody)->stringContainsString('You have reached 95% of your MailPoet plan’s subscriber limit.');
        verify($textBody)->stringContainsString('Upgrade your MailPoet plan to keep growing your audience: manage your MailPoet plan.');
        verify($textBody)->stringContainsString('https://account.mailpoet.com/orders/upgrade/abc123');
        verify($textBody)->stringNotContainsString('95%%');
        return true;
      });

    $mailer = new SubscriberLimitNotificationMailer(
      $this->diContainer->get(Renderer::class),
      $wp,
      $nativeMailer,
      $servicesChecker
    );

    verify($mailer->send(95, 950, 1000, true))->true();
  }
}
