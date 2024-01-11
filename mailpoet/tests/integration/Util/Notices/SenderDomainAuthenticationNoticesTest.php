<?php declare(strict_types = 1);

namespace integration\Util\Notices;

use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Notices\SenderDomainAuthenticationNotices;
use MailPoetVendor\Carbon\Carbon;

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

    Carbon::setTestNow(Carbon::parse('2024-01-31 00:00:00 UTC'));
    $beforeRestrictionsInEffectMessage = $this->notice->getNoticeContentForFreeMailUsers(999);
    $this->assertStringContainsString('Update your sender email address to a branded domain by February 1st, 2024 to continue sending your campaigns.', $beforeRestrictionsInEffectMessage);
    $this->assertStringContainsString(sprintf('Your emails will temporarily be sent from <strong>%s</strong>', $rewrittenEmail), $beforeRestrictionsInEffectMessage);
    $this->assertStringContainsString('Update sender email', $beforeRestrictionsInEffectMessage);

    Carbon::setTestNow(Carbon::parse('2024-02-01 00:00:00 UTC'));
    $afterRestrictionsInEffectMessage = $this->notice->getNoticeContentForFreeMailUsers(1001);
    $this->assertStringContainsString('Your newsletters and post notifications have been paused. Update your sender email address to a branded domain to continue sending your campaigns', $afterRestrictionsInEffectMessage);
    $this->assertStringContainsString(sprintf('Your marketing automations and transactional emails will temporarily be sent from <strong>%s</strong>', $rewrittenEmail), $afterRestrictionsInEffectMessage);
    $this->assertStringContainsString('Update sender email', $afterRestrictionsInEffectMessage);
  }

  public function testItRetrievesAppropriateMessageForFreeMailUsersWithLessThan1000Contacts(): void {
    $email = 'sender@hotmail.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $rewrittenEmail = $this->authorizedSenderDomainController->getRewrittenEmailAddress($email);

    Carbon::setTestNow(Carbon::parse('2024-01-31 00:00:00 UTC'));
    $beforeRestrictionsInEffectMessage = $this->notice->getNoticeContentForFreeMailUsers(999);
    $this->assertStringContainsString('Update your sender email address to a branded domain by February 1st, 2024 to continue sending your campaigns.', $beforeRestrictionsInEffectMessage);
    $this->assertStringContainsString(sprintf('Your emails will temporarily be sent from <strong>%s</strong>', $rewrittenEmail), $beforeRestrictionsInEffectMessage);
    $this->assertStringContainsString('Update sender email', $beforeRestrictionsInEffectMessage);

    Carbon::setTestNow(Carbon::parse('2024-02-01 00:00:00 UTC'));
    $afterRestrictionsInEffectMessage = $this->notice->getNoticeContentForFreeMailUsers(999);
    $this->assertStringContainsString('Update your sender email address to a branded domain to continue sending your campaigns', $afterRestrictionsInEffectMessage);
    $this->assertStringContainsString(sprintf('Your existing scheduled and active emails will temporarily be sent from <strong>%s</strong>', $rewrittenEmail), $afterRestrictionsInEffectMessage);
    $this->assertStringContainsString('Update sender email', $afterRestrictionsInEffectMessage);
  }

  public function testItRetrievesAppropriateMessageForBrandedDomainsWithMoreThan1000Contacts(): void {
    $email = 'sender@brandeddomain.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $rewrittenEmail = $this->authorizedSenderDomainController->getRewrittenEmailAddress($email);

    Carbon::setTestNow(Carbon::parse('2024-01-31 00:00:00 UTC'));
    $beforeRestrictionsInEffectMessage = $this->notice->getNoticeContentForBrandedDomainUsers(false, 1001);
    $this->assertStringContainsString('Authenticate your sender domain to improve email delivery rates.', $beforeRestrictionsInEffectMessage);
    $this->assertStringContainsString('Please authenticate your sender domain to ensure your marketing campaigns are compliant and will reach your contacts', $beforeRestrictionsInEffectMessage);

    Carbon::setTestNow(Carbon::parse('2024-02-01 00:00:00 UTC'));
    $afterRestrictionsInEffectMessage = $this->notice->getNoticeContentForBrandedDomainUsers(false, 1001);
    $this->assertStringContainsString('Your newsletters and post notifications have been paused. Authenticate your sender domain to continue sending.', $afterRestrictionsInEffectMessage);
    $this->assertStringContainsString('Your marketing automations and transactional emails will temporarily be sent from', $afterRestrictionsInEffectMessage);
    $this->assertStringContainsString($rewrittenEmail, $afterRestrictionsInEffectMessage);
    $this->assertStringContainsString('Authenticate domain', $afterRestrictionsInEffectMessage);
  }

  public function testItRetrievesAppropriateMessageForBrandedDomainsWithLessThan1000Contacts(): void {
    $email = 'sender@brandeddomain.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $rewrittenEmail = $this->authorizedSenderDomainController->getRewrittenEmailAddress($email);

    Carbon::setTestNow(Carbon::parse('2024-01-31 00:00:00 UTC'));
    $beforeRestrictionsInEffectMessage = $this->notice->getNoticeContentForBrandedDomainUsers(false, 1001);
    $this->assertStringContainsString('Authenticate your sender domain to improve email delivery rates.', $beforeRestrictionsInEffectMessage);
    $this->assertStringContainsString('Please authenticate your sender domain to ensure your marketing campaigns are compliant and will reach your contacts', $beforeRestrictionsInEffectMessage);

    Carbon::setTestNow(Carbon::parse('2024-02-01 00:00:00 UTC'));
    $afterRestrictionsInEffectMessage = $this->notice->getNoticeContentForBrandedDomainUsers(false, 999);
    $this->assertStringContainsString('Authenticate your sender domain to send new emails.', $afterRestrictionsInEffectMessage);
    $this->assertStringContainsString('Your existing scheduled and active emails will temporarily be sent from', $afterRestrictionsInEffectMessage);
    $this->assertStringContainsString($rewrittenEmail, $afterRestrictionsInEffectMessage);
    $this->assertStringContainsString('Authenticate domain', $afterRestrictionsInEffectMessage);
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
