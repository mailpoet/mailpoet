<?php declare(strict_types = 1);

namespace integration\Util\Notices;

use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Notices\SenderDomainAuthenticationNotices;

class SenderDomainAuthenticationNoticesTest extends \MailPoetTest {
  /** @var SenderDomainAuthenticationNotices */
  private $notice;

  /** @var SettingsController */
  private $settings;

  private AuthorizedSenderDomainController $authorizedSenderDomainController;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->notice = $this->diContainer->get(SenderDomainAuthenticationNotices::class);
    $this->authorizedSenderDomainController = $this->diContainer->get(AuthorizedSenderDomainController::class);
  }

  public function testItCanGetDefaultFromAddress(): void {
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => 'sender@test.com',
    ]);
    $defaultFrom = $this->notice->getDefaultFromAddress();
    $this->assertSame('sender@test.com', $defaultFrom);
  }

  public function testItCanGetDefaultFromDomain(): void {
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => 'sender@test.com',
    ]);
    $defaultDomain = $this->notice->getDefaultFromDomain();
    $this->assertSame('test.com', $defaultDomain);
  }

  public function testItCanDetermineIfUsingFreeMailServiceForFromAddress(): void {
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => 'sender@hotmail.com',
    ]);
    $this->assertTrue($this->notice->isFreeMailUser());
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => 'sender@customdomainthatprobablydoesnotevenexist.com',
    ]);
    $this->assertFalse($this->notice->isFreeMailUser());
  }

  public function testItRetrievesAppropriateMessageForFreeUsersWithMoreThan1000Contacts(): void {
    $email = 'sender@hotmail.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $rewrittenEmail = $this->authorizedSenderDomainController->getRewrittenEmailAddress($email);
    $message = $this->notice->getNoticeContentForFreeMailUsers(1001);
    $this->assertStringContainsString('Your newsletters and post notifications have been paused. Update your sender email address to a branded domain to continue sending your campaigns', $message);
    $this->assertStringContainsString(sprintf('Your marketing automations and transactional emails will temporarily be sent from <strong>%s</strong>', $rewrittenEmail), $message);
    $this->assertStringContainsString('Update sender email', $message);
  }

  public function testItRetrievesAppropriateMessageForFreeMailUsersWithLessThan1000Contacts(): void {
    $email = 'sender@hotmail.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $rewrittenEmail = $this->authorizedSenderDomainController->getRewrittenEmailAddress($email);
    $message = $this->notice->getNoticeContentForFreeMailUsers(999);
    $this->assertStringContainsString('Update your sender email address to a branded domain to continue sending your campaigns', $message);
    $this->assertStringContainsString(sprintf('Your existing scheduled and active emails will temporarily be sent from <strong>%s</strong>', $rewrittenEmail), $message);
    $this->assertStringContainsString('Update sender email', $message);
  }

  public function testItRetrievesAppropriateMessageForBrandedDomainsWithMoreThan1000Contacts(): void {
    $email = 'sender@brandeddomain.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $rewrittenEmail = $this->authorizedSenderDomainController->getRewrittenEmailAddress($email);
    $message = $this->notice->getNoticeContentForBrandedDomainUsers(false, 1001);
    $this->assertStringContainsString('Your newsletters and post notifications have been paused. Authenticate your sender domain to continue sending.', $message);
    $this->assertStringContainsString('Your marketing automations and transactional emails will temporarily be sent from', $message);
    $this->assertStringContainsString($rewrittenEmail, $message);
    $this->assertStringContainsString('Authenticate domain', $message);
  }

  public function testItRetrievesAppropriateMessageForBrandedDomainsWithLessThan1000Contacts(): void {
    $email = 'sender@brandeddomain.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $rewrittenEmail = $this->authorizedSenderDomainController->getRewrittenEmailAddress($email);
    $message = $this->notice->getNoticeContentForBrandedDomainUsers(false, 999);
    $this->assertStringContainsString('Authenticate your sender domain to send new emails.', $message);
    $this->assertStringContainsString('Your existing scheduled and active emails will temporarily be sent from', $message);
    $this->assertStringContainsString($rewrittenEmail, $message);
    $this->assertStringContainsString('Authenticate domain', $message);
  }

  public function testItRetrievesAppropriateMessageForBrandedDomainsWithLessThan500Contacts(): void {
    $email = 'sender@brandeddomain.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $message = $this->notice->getNoticeContentForBrandedDomainUsers(false, 499);
    $this->assertStringContainsString('Authenticate your sender domain to improve email delivery rates.', $message);
    $this->assertStringContainsString('Authenticate domain', $message);
  }

  public function testItRetrievesAppropriateMessageForBrandedDomainsThatArePartiallyVerified(): void {
    $email = 'sender@brandeddomain.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $message = $this->notice->getNoticeContentForBrandedDomainUsers(true, 1200);
    $this->assertStringContainsString('Authenticate your sender domain to improve email delivery rates.', $message);
    $this->assertStringContainsString('Authenticate domain', $message);
  }
}
