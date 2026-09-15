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
  }

  public function testItDisplaysNoticeWhenCaptchaDisabledAndMssActive() {
    $this->settings->set(CaptchaConstants::TYPE_SETTING_NAME, CaptchaConstants::TYPE_DISABLED);
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);

    $output = $this->renderNotice();

    verify($output)->stringContainsString('CAPTCHA helps protect your forms from spam and abuse');
    verify($output)->stringContainsString('admin.php?page=mailpoet-settings#/advanced');
  }

  public function testItDoesNotDisplayNoticeWhenCaptchaIsEnabled() {
    $this->settings->set(CaptchaConstants::TYPE_SETTING_NAME, CaptchaConstants::TYPE_BUILTIN);
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);

    $output = $this->renderNotice();

    verify($output)->equals('');
  }

  public function testItDoesNotDisplayNoticeWhenMssIsNotActive() {
    $this->settings->set(CaptchaConstants::TYPE_SETTING_NAME, CaptchaConstants::TYPE_DISABLED);
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_PHPMAIL]);

    $output = $this->renderNotice();

    verify($output)->equals('');
  }

  private function renderNotice(): string {
    ob_start();
    $this->captchaDisabledNotice->render();
    return (string)ob_get_clean();
  }
}
