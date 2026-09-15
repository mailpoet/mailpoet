<?php declare(strict_types = 1);

namespace integration\AdminPages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\AdminPages\Pages\Forms;
use MailPoet\API\JSON\ResponseBuilders\SegmentsResponseBuilder;
use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\WP\Functions as WPFunctions;

class FormsTest extends \MailPoetTest {

  /** @var Forms */
  private $formsPage;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->formsPage = new Forms(
      $this->diContainer->get(AssetsController::class),
      $this->diContainer->get(PageRenderer::class),
      $this->diContainer->get(UserFlagsController::class),
      $this->diContainer->get(SegmentsRepository::class),
      $this->diContainer->get(SegmentsResponseBuilder::class),
      $this->settings,
      $this->diContainer->get(WPFunctions::class)
    );
  }

  public function testItExposesCaptchaDisabledWhenCaptchaIsDisabled() {
    $this->settings->set(CaptchaConstants::TYPE_SETTING_NAME, '');
    $output = $this->renderPage();
    verify($output)->stringContainsString('var mailpoet_captcha_disabled = true;');
  }

  public function testItExposesCaptchaEnabledWhenCaptchaIsConfigured() {
    $this->settings->set(CaptchaConstants::TYPE_SETTING_NAME, CaptchaConstants::TYPE_BUILTIN);
    $output = $this->renderPage();
    verify($output)->stringContainsString('var mailpoet_captcha_disabled = false;');
  }

  private function renderPage(): string {
    ob_start();
    $this->formsPage->render();
    return (string)ob_get_clean();
  }
}
