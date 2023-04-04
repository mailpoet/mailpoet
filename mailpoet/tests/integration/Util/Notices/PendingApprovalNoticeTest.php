<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Mailer\Mailer;
use MailPoet\Settings\SettingsController;

class PendingApprovalNoticeTest extends \MailPoetTest {
  /** @var PendingApprovalNotice */
  private $notice;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->notice = new PendingApprovalNotice($this->settings);
  }

  public function testItDisplays(): void {
    $this->settings->set('mta.mailpoet_api_key_state.data.is_approved', false);
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);

    $result = $this->notice->init(true);
    // check that the notice is displayed. We cannot check the whole string because it contains HTML tags
    expect($result)->stringContainsString('Your subscription is currently');
    expect($result)->stringContainsString('if you haven’t heard from our team about your subscription status in the past 48 hours.');
  }

  public function testItDoesNotDisplayWhenDisabled(): void {
    $this->settings->set('mta.mailpoet_api_key_state.data.is_approved', false);
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);

    $result = $this->notice->init(false);
    expect($result)->null();
  }

  public function testItDoesNotDisplayWhenNotUsingMailPoet(): void {
    $this->settings->set('mta.mailpoet_api_key_state.data.is_approved', false);
    $this->settings->set('mta.method', Mailer::METHOD_PHPMAIL);

    $result = $this->notice->init(true);
    expect($result)->null();
  }

  public function testItDoesNotDisplayWhenApproved(): void {
    $this->settings->set('mta.mailpoet_api_key_state.data.is_approved', true);
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);

    $result = $this->notice->init(true);
    expect($result)->null();
  }
}
