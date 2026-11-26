<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class PendingApprovalNoticeTest extends \MailPoetTest {
  /** @var PendingApprovalNotice */
  private $notice;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->notice = new PendingApprovalNotice($this->settings, WPFunctions::get());
    delete_transient(Bridge::REVIEW_STATUS_TRANSIENT_NAME);
    $this->settings->set('mta.mailpoet_api_key_state.state', Bridge::KEY_VALID);
  }

  public function testItDisplays(): void {
    $this->settings->set('mta.mailpoet_api_key_state.data.is_approved', false);
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);

    $result = $this->notice->init(true);
    // check that the notice is displayed. We cannot check the whole string because it contains HTML tags
    verify($result)->stringContainsString('reviewing your subscription');
    verify($result)->stringContainsString('If you don\'t hear from us within 48 hours, please check the inbox and spam folders of your MailPoet account email');
  }

  public function testItDisplaysTheReviewStatusWhenTheBridgeReportedOne(): void {
    $this->settings->set('mta.mailpoet_api_key_state.data.is_approved', false);
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);
    set_transient(Bridge::REVIEW_STATUS_TRANSIENT_NAME, ['status' => 'question', 'reason' => null, 'since' => '2026-11-20T10:00:00.000+00:00']);

    $result = $this->notice->init(true);
    verify($result)->stringContainsString('Action required');
    verify($result)->stringContainsString('We emailed you a question about your plan on');
    verify($result)->stringNotContainsString('reviewing your subscription');
  }

  public function testItDoesNotDisplayWhenMSSKeyIsNotValid(): void {
    $this->settings->set('mta.mailpoet_api_key_state.data.is_approved', false);
    $this->settings->set('mta.mailpoet_api_key_state.state', Bridge::KEY_VALID_UNDERPRIVILEGED);
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);

    $result = $this->notice->init(true);
    verify($result)->null();
  }

  public function testItDoesNotDisplayWhenDisabled(): void {
    $this->settings->set('mta.mailpoet_api_key_state.data.is_approved', false);
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);

    $result = $this->notice->init(false);
    verify($result)->null();
  }

  public function testItDoesNotDisplayWhenNotUsingMailPoet(): void {
    $this->settings->set('mta.mailpoet_api_key_state.data.is_approved', false);
    $this->settings->set('mta.method', Mailer::METHOD_PHPMAIL);

    $result = $this->notice->init(true);
    verify($result)->null();
  }

  public function testItDoesNotDisplayWhenApproved(): void {
    $this->settings->set('mta.mailpoet_api_key_state.data.is_approved', true);
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);

    $result = $this->notice->init(true);
    verify($result)->null();
  }
}
