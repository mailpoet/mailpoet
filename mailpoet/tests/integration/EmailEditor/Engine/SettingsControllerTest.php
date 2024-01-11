<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

class SettingsControllerTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settingsController;

  public function _before() {
    parent::_before();
    $this->settingsController = $this->diContainer->get(SettingsController::class);
  }

  public function testItGeneratesCssStylesForThemeWithFontFamilies() {
    $css = $this->settingsController->getStylesheetForRendering();
    verify($css)->stringContainsString('.has-system-sans-serif-font-family');
    verify($css)->stringContainsString('.has-system-Serif-font-family');
  }
}
