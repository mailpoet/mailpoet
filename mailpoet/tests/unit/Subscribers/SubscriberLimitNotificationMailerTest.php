<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Config\Renderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/../../../lib/Subscribers/SubscriberLimitNotificationMailer.php';

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

    $defaultMailer = $this->createMock(Mailer::class);
    $defaultMailer->expects($this->once())
      ->method('send')
      ->willReturnCallback(function(array $newsletter, string $recipient, array $extraParams): array {
        verify($recipient)->equals('admin@example.com');
        verify($newsletter['subject'])->equals('Your MailPoet subscriber list is at 95% of its limit');
        verify($newsletter['body']['html'])->equals('emails/subscriberLimitThresholdNotification.html');
        verify($newsletter['body']['text'])->equals('emails/subscriberLimitThresholdNotification.txt');
        verify($extraParams['meta'])->equals([
          'email_type' => 'subscriber_limit_notification',
          'subscriber_status' => 'unknown',
          'subscriber_source' => 'administrator',
        ]);
        return ['response' => true];
      });

    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($defaultMailer);

    $mailer = new SubscriberLimitNotificationMailer($renderer, $wp, $mailerFactory, new MetaInfo(), $servicesChecker);
    verify($mailer->send(95, 950, 1000, false))->true();
  }

  public function testItDoesNotSendWithInvalidAdminEmail(): void {
    $renderer = $this->createMock(Renderer::class);
    $renderer->expects($this->never())->method('render');

    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getOption')->with('admin_email')->willReturn('invalid');
    $wp->method('sanitizeEmail')->with('invalid')->willReturn('');

    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->expects($this->never())->method('getDefaultMailer');

    $mailer = new SubscriberLimitNotificationMailer($renderer, $wp, $mailerFactory, new MetaInfo(), $this->createMock(ServicesChecker::class));
    verify($mailer->send(95, 950, 1000, false))->false();
  }
}
