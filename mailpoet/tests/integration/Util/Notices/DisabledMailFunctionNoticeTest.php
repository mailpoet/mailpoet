<?php

namespace MailPoet\Util\Notices;

use Codeception\Util\Stub;
use Codeception\Stub\Expected;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
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
    $this->settings->set(DisabledMailFunctionNotice::QUEUE_DISABLED_MAIL_FUNCTION_CHECK, true);
    $this->settings->set(DisabledMailFunctionNotice::OPTION_NAME, false);
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

  private function generateNoticeWithMethodOverride($methodOverride = []) {
    $defaultOverride = ['isFunctionDisabled' => false];
    $allOverride = array_merge($defaultOverride, $methodOverride);
    return $this->generateRawNotice($allOverride);
  }

  private function generateRawNotice($override = []) {
    $mailerFactoryMock = $this->createMock(MailerFactory::class);
    $mailerFactoryMock->method('buildMailer')
      ->willReturn($this->createMock(Mailer::class));

    return Stub::construct(
      DisabledMailFunctionNotice::class,
      [$this->wp, $this->settings, $this->diContainer->get(SubscribersFeature::class), $mailerFactoryMock],
      $override
    );
  }

  public function testItDoesNotDisplayNoticeForOtherSendingMethods() {
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);

    $disabledMailFunctionNotice = $this->generateNoticeWithMethodOverride(['isFunctionDisabled' => true]);
    $notice = $disabledMailFunctionNotice->init(true);

    expect($notice)->equals(null);
  }

  public function testItDisplaysNoticeForPhpMailSendingMethod() {
    $disabledMailFunctionNotice = $this->generateNoticeWithMethodOverride(['isFunctionDisabled' => true]);
    $notice = $disabledMailFunctionNotice->init(true);

    expect($notice)->stringContainsString('Get ready to send your first campaign');
    expect($notice)->stringContainsString('Connect your website with MailPoet');
    expect($notice)->stringContainsString('account.mailpoet.com/?s=50&amp;utm_source=mailpoet&amp;utm_medium=plugin&amp;utm_campaign=disabled_mail_function');
  }

  public function testItDoesNotDisplaysNoticeWhenMailFunctionIsEnabled() {
    $disabledMailFunctionNotice = $this->generateNoticeWithMethodOverride(['sendTestMail' => true]);
    $notice = $disabledMailFunctionNotice->init(true);

    expect($notice)->equals(null);
  }

  public function testItReturnsTrueWhenFunctionDoesNotExist() {
    $disabledMailFunctionNotice = $this->generateRawNotice();
    $result = $disabledMailFunctionNotice->isFunctionDisabled('mp_undefined_function');

    expect($result)->equals(true);
  }

  public function testItReturnsFalseWhenFunctionExist() {
    $disabledMailFunctionNotice = $this->generateRawNotice();
    $result = $disabledMailFunctionNotice->isFunctionDisabled('in_array');

    expect($result)->equals(false);
  }

  public function testItResetQueueCheck() {
    $this->settings->set(DisabledMailFunctionNotice::QUEUE_DISABLED_MAIL_FUNCTION_CHECK, true);
    $disabledMailFunctionNotice = $this->generateNoticeWithMethodOverride();
    $notice = $disabledMailFunctionNotice->init(true);

    expect($notice)->equals(null);
    $status = $this->settings->get(DisabledMailFunctionNotice::QUEUE_DISABLED_MAIL_FUNCTION_CHECK, false);
    expect($status)->equals(false);
  }

  public function testItDisplayNoticeWhenMailIsMisConfigured() {
    $disabledMailFunctionNotice = $this->generateNoticeWithMethodOverride(['sendTestMail' => false]);
    $notice = $disabledMailFunctionNotice->init(true);

    expect($notice)->stringContainsString('Get ready to send your first campaign');

    $status = $this->settings->get(DisabledMailFunctionNotice::OPTION_NAME, false);
    expect($status)->equals(true);
  }

  public function testItContinueDisplayingNoticeWhenMailFunctionIsDisabled() {
    $disabledMailFunctionNotice = $this->generateNoticeWithMethodOverride([
      'isFunctionDisabled' => true,
      'sendTestMail' => Expected::never(),
    ]);
    $notice = $disabledMailFunctionNotice->init(true);

    expect($notice)->stringContainsString('Get ready to send your first campaign');

    $status = $this->settings->get(DisabledMailFunctionNotice::QUEUE_DISABLED_MAIL_FUNCTION_CHECK, false);
    expect($status)->equals(false);

    $secondNotice = $disabledMailFunctionNotice->init(true);
    expect($secondNotice)->stringContainsString('Get ready to send your first campaign');

    $thirdNotice = $disabledMailFunctionNotice->init(true);
    expect($thirdNotice)->stringContainsString('Get ready to send your first campaign');
  }

  public function testItContinueDisplayingNoticeWhenMailFunctionIsMisConfigured() {
    $disabledMailFunctionNotice = $this->generateNoticeWithMethodOverride([
      'isFunctionDisabled' => false,
      'sendTestMail' => Expected::once(false),
    ]);

    $notice = $disabledMailFunctionNotice->init(true);

    expect($notice)->stringContainsString('Get ready to send your first campaign');

    $status = $this->settings->get(DisabledMailFunctionNotice::QUEUE_DISABLED_MAIL_FUNCTION_CHECK, false);
    expect($status)->equals(false);

    $secondNotice = $disabledMailFunctionNotice->init(true);
    expect($secondNotice)->stringContainsString('Get ready to send your first campaign');

    $thirdNotice = $disabledMailFunctionNotice->init(true);
    expect($thirdNotice)->stringContainsString('Get ready to send your first campaign');
  }

  public function testItClearsNoticeWhenSendingMethodIsChanged() {
    $disabledMailFunctionNotice = $this->generateNoticeWithMethodOverride([
      'isFunctionDisabled' => true,
      'sendTestMail' => Expected::never(),
    ]);
    $notice = $disabledMailFunctionNotice->init(true);

    expect($notice)->stringContainsString('Get ready to send your first campaign');

    $status = $this->settings->get(DisabledMailFunctionNotice::QUEUE_DISABLED_MAIL_FUNCTION_CHECK, false);
    expect($status)->equals(false);

    $secondNotice = $disabledMailFunctionNotice->init(true);
    expect($secondNotice)->stringContainsString('Get ready to send your first campaign');

    $thirdNotice = $disabledMailFunctionNotice->init(true);
    expect($thirdNotice)->stringContainsString('Get ready to send your first campaign');

    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);

    $fourthNotice = $disabledMailFunctionNotice->init(true);

    expect($fourthNotice)->equals(null);
  }
}
