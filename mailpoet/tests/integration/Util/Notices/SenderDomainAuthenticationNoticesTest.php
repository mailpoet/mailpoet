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

  private int $lowerLimit;

  private int $upperLimit;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->notice = $this->diContainer->get(SenderDomainAuthenticationNotices::class);
    $this->authorizedSenderDomainController = $this->diContainer->get(AuthorizedSenderDomainController::class);
    $this->lowerLimit = AuthorizedSenderDomainController::LOWER_LIMIT;
    $this->upperLimit = AuthorizedSenderDomainController::UPPER_LIMIT;
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

  public function testItRetrievesAppropriateMessageForFreeUsersThatAreBigSenders(): void {
    $email = 'sender@hotmail.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $rewrittenEmail = $this->authorizedSenderDomainController->getRewrittenEmailAddress($email);

    $noticeMessage = $this->notice->getNoticeContentForFreeMailUsers($this->upperLimit + 1);
    $this->assertStringContainsString('Your newsletters and post notifications have been paused. Update your sender email address to a branded domain to continue sending your campaigns', $noticeMessage);
    $this->assertStringContainsString(sprintf('Your marketing automations and transactional emails will temporarily be sent from <strong>%s</strong>', $rewrittenEmail), $noticeMessage);
    $this->assertStringContainsString('Update sender email', $noticeMessage);
  }

  public function testItRetrievesAppropriateMessageForFreeMailUsersThatAreNotBigSenders(): void {
    $email = 'sender@hotmail.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $rewrittenEmail = $this->authorizedSenderDomainController->getRewrittenEmailAddress($email);

    $noticeMessage = $this->notice->getNoticeContentForFreeMailUsers($this->upperLimit);
    $this->assertStringContainsString('Update your sender email address to a branded domain to continue sending your campaigns', $noticeMessage);
    $this->assertStringContainsString(sprintf('Your existing scheduled and active emails will temporarily be sent from <strong>%s</strong>', $rewrittenEmail), $noticeMessage);
    $this->assertStringContainsString('Update sender email', $noticeMessage);
  }

  public function testItRetrievesAppropriateMessageForBrandedDomainsForBigSenders(): void {
    $email = 'sender@brandeddomain.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $rewrittenEmail = $this->authorizedSenderDomainController->getRewrittenEmailAddress($email);

    $noticeMessage = $this->notice->getNoticeContentForBrandedDomainUsers(false, $this->upperLimit + 1);
    $this->assertStringContainsString('Your newsletters and post notifications have been paused. Authenticate your sender domain to continue sending.', $noticeMessage);
    $this->assertStringContainsString('Your marketing automations and transactional emails will temporarily be sent from', $noticeMessage);
    $this->assertStringContainsString($rewrittenEmail, $noticeMessage);
    $this->assertStringContainsString('Authenticate domain', $noticeMessage);
  }

  public function testItRetrievesAppropriateMessageForBrandedDomainsThatAreNotBigSenders(): void {
    $email = 'sender@brandeddomain.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $rewrittenEmail = $this->authorizedSenderDomainController->getRewrittenEmailAddress($email);

    $noticeMessage = $this->notice->getNoticeContentForBrandedDomainUsers(false, $this->upperLimit);
    $this->assertStringContainsString('Authenticate your sender domain to send new emails.', $noticeMessage);
    $this->assertStringContainsString('Your existing scheduled and active emails will temporarily be sent from', $noticeMessage);
    $this->assertStringContainsString($rewrittenEmail, $noticeMessage);
    $this->assertStringContainsString('Authenticate domain', $noticeMessage);
  }

  public function testItRetrievesAppropriateMessageForBrandedDomainsForSmallSenders(): void {
    $email = 'sender@brandeddomain.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $message = $this->notice->getNoticeContentForBrandedDomainUsers(false, $this->lowerLimit);
    $this->assertStringContainsString('Authenticate your sender domain to improve email delivery rates.', $message);
    $this->assertStringContainsString('Authenticate domain', $message);
  }

  public function testItRetrievesAppropriateMessageForBrandedDomainsThatArePartiallyVerified(): void {
    $email = 'sender@brandeddomain.com';
    $this->settings->set('sender', [
      'name' => 'Sender',
      'address' => $email,
    ]);
    $message = $this->notice->getNoticeContentForBrandedDomainUsers(true, $this->upperLimit);
    $this->assertStringContainsString('Authenticate your sender domain to improve email delivery rates.', $message);
    $this->assertStringContainsString('Authenticate domain', $message);
  }
}
