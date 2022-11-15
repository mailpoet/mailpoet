<?php

namespace MailPoet\Util\Notices;

use Codeception\Util\Stub;
use MailPoet\Mailer\Mailer;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

class DisabledMailFunctionNoticeTest extends \MailPoetTest
{
  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->wp = new WPFunctions;
    $this->settings->set('mta.method', Mailer::METHOD_PHPMAIL);
    $this->wp->setTransient(SubscribersFeature::SUBSCRIBERS_COUNT_CACHE_KEY, 50, SubscribersFeature::SUBSCRIBERS_COUNT_CACHE_EXPIRATION_MINUTES * 60);
  }

  public function _after() {
    parent::_after();
    $this->cleanup();
  }

  private function cleanup() {
    $this->settings->delete('mta.method');
    $this->wp->deleteTransient(SubscribersFeature::SUBSCRIBERS_COUNT_CACHE_KEY);
  }

  private function generateNotice($methodOverride = []) {
    return Stub::construct(
      DisabledMailFunctionNotice::class,
      [$this->wp, $this->settings, $this->diContainer->get(SubscribersFeature::class)],
      $methodOverride
    );
  }

  public function testItDoesNotDisplayNoticeForOtherSendingMethods() {
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);

    $disabledMailFunctionNotice = $this->generateNotice(['isFunctionDisabled' => true]);
    $notice = $disabledMailFunctionNotice->init(true);

    expect($notice)->equals(null);
  }

  public function testItDisplaysNoticeForPhpMailSendingMethod() {
    $disabledMailFunctionNotice = $this->generateNotice(['isFunctionDisabled' => true]);
    $notice = $disabledMailFunctionNotice->init(true);

    expect($notice)->stringContainsString('Get ready to send your first campaign');
    expect($notice)->stringContainsString('Connect your website with MailPoet');
    expect($notice)->stringContainsString('account.mailpoet.com/?s=50&amp;utm_source=mailpoet&amp;utm_medium=plugin&amp;utm_campaign=disabled_mail_function');
  }

  public function testItDoesNotDisplaysNoticeWhenMailFunctionIsEnabled() {
    $disabledMailFunctionNotice = $this->generateNotice();
    $notice = $disabledMailFunctionNotice->init(true);

    expect($notice)->equals(null);
  }

  public function testItReturnsTrueWhenFunctionDoesNotExist() {
    $disabledMailFunctionNotice = $this->generateNotice();
    $result = $disabledMailFunctionNotice->isFunctionDisabled('mp_undefined_function');

    expect($result)->equals(true);
  }

  public function testItReturnsFalseWhenFunctionExist() {
    $disabledMailFunctionNotice = $this->generateNotice();
    $result = $disabledMailFunctionNotice->isFunctionDisabled('in_array');

    expect($result)->equals(false);
  }

}
