<?php declare(strict_types = 1);

namespace MailPoet\Test\Captcha;

use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Captcha\CaptchaDisabledNotice;
use MailPoet\Mailer\Mailer;
use MailPoet\Settings\SettingsController;

class CaptchaDisabledNoticeTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  /** @var CaptchaDisabledNotice */
  private $captchaDisabledNotice;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->captchaDisabledNotice = $this->diContainer->get(CaptchaDisabledNotice::class);
    delete_transient(CaptchaDisabledNotice::OPTION_NAME);
  }

  public function _after() {
    parent::_after();
    delete_transient(CaptchaDisabledNotice::OPTION_NAME);
  }

  public function testItBuildsNoticeMessageWithSettingsLink() {
    $notice = $this->captchaDisabledNotice->display();

    verify($notice->getMessage())->stringContainsString('CAPTCHA helps protect your forms from spam and abuse');
    verify($notice->getMessage())->stringContainsString('admin.php?page=mailpoet-settings#/advanced');
  }

  public function testItDisplaysNoticeWhenCaptchaDisabledAndMssActive() {
    $this->settings->set(CaptchaConstants::TYPE_SETTING_NAME, CaptchaConstants::TYPE_DISABLED);
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);

    verify($this->captchaDisabledNotice->init(true))->notNull();
  }

  public function testItDoesNotDisplayNoticeWhenCaptchaIsEnabled() {
    $this->settings->set(CaptchaConstants::TYPE_SETTING_NAME, CaptchaConstants::TYPE_BUILTIN);
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);

    $notice = $this->captchaDisabledNotice->init(true);

    verify($notice)->null();
  }

  public function testItDoesNotDisplayNoticeWhenMssIsNotActive() {
    $this->settings->set(CaptchaConstants::TYPE_SETTING_NAME, CaptchaConstants::TYPE_DISABLED);
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_PHPMAIL]);

    $notice = $this->captchaDisabledNotice->init(true);

    verify($notice)->null();
  }

  public function testItDoesNotDisplayNoticeWhenShouldDisplayIsFalse() {
    $this->settings->set(CaptchaConstants::TYPE_SETTING_NAME, CaptchaConstants::TYPE_DISABLED);
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);

    $notice = $this->captchaDisabledNotice->init(false);

    verify($notice)->null();
  }

  public function testItDoesNotDisplayNoticeWhenDismissed() {
    $this->settings->set(CaptchaConstants::TYPE_SETTING_NAME, CaptchaConstants::TYPE_DISABLED);
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);

    $this->captchaDisabledNotice->disable();
    $notice = $this->captchaDisabledNotice->init(true);

    verify($notice)->null();
  }
}
