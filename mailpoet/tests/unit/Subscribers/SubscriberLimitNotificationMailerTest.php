<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Config\Renderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/../../../lib/Subscribers/SubscriberLimitNotificationMailer.php';
require_once __DIR__ . '/../../../lib/Subscribers/SubscriberLimitNotificationNativeMailer.php';

class SubscriberLimitNotificationMailerTest extends \MailPoetUnitTest {
  public function testItSendsRenderedNotificationToAdminEmail(): void {
    $renderer = $this->createMock(Renderer::class);
    $renderer->method('render')
      ->willReturnCallback(function(string $template, array $context): string {
        verify($context['count'])->equals(950);
        verify($context['limit'])->equals(1000);
        verify($context['threshold'])->equals(95);
        verify($context['hasValidApiKey'])->false();
        verify($context['link_upgrade'])->equals('https://account.mailpoet.com/?s=1001');
        return $template;
      });

    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getOption')->with('admin_email')->willReturn(' admin@example.com ');
    $wp->method('sanitizeEmail')->with(' admin@example.com ')->willReturn('admin@example.com');
    $wp->method('isEmail')->with('admin@example.com')->willReturn('admin@example.com');

    $servicesChecker = $this->createMock(ServicesChecker::class);
    $servicesChecker->expects($this->never())->method('generatePartialApiKey');

    $nativeMailer = $this->createMock(SubscriberLimitNotificationNativeMailer::class);
    $nativeMailer->expects($this->once())
      ->method('send')
      ->with(
        'admin@example.com',
        'Your MailPoet subscriber list is at 95% of its limit',
        'emails/subscriberLimitThresholdNotification.html',
        'emails/subscriberLimitThresholdNotification.txt'
      )
      ->willReturn(true);

    $mailer = new SubscriberLimitNotificationMailer($renderer, $wp, $nativeMailer, $servicesChecker);
    verify($mailer->send(95, 950, 1000, false))->true();
  }

  public function testItDoesNotSendWithInvalidAdminEmail(): void {
    $renderer = $this->createMock(Renderer::class);
    $renderer->expects($this->never())->method('render');

    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getOption')->with('admin_email')->willReturn('invalid');
    $wp->method('sanitizeEmail')->with('invalid')->willReturn('');

    $nativeMailer = $this->createMock(SubscriberLimitNotificationNativeMailer::class);
    $nativeMailer->expects($this->never())->method('send');

    $mailer = new SubscriberLimitNotificationMailer($renderer, $wp, $nativeMailer, $this->createMock(ServicesChecker::class));
    verify($mailer->send(95, 950, 1000, false))->false();
  }
}
