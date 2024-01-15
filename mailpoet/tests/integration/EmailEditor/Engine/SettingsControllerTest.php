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
    verify($css)->stringContainsString('.has-arial-font-family');
    verify($css)->stringContainsString('.has-comic-sans-ms-font-family');
    verify($css)->stringContainsString('.has-courier-new-font-family');
    verify($css)->stringContainsString('.has-georgia-font-family');
    verify($css)->stringContainsString('.has-lucida-font-family');
    verify($css)->stringContainsString('.has-tahoma-font-family');
    verify($css)->stringContainsString('.has-times-new-roman-font-family');
    verify($css)->stringContainsString('.has-trebuchet-ms-font-family');
    verify($css)->stringContainsString('.has-verdana-font-family');
    verify($css)->stringContainsString('.has-arvo-font-family');
    verify($css)->stringContainsString('.has-lato-font-family');
    verify($css)->stringContainsString('.has-merriweather-font-family');
    verify($css)->stringContainsString('.has-merriweather-sans-font-family');
    verify($css)->stringContainsString('.has-noticia-text-font-family');
    verify($css)->stringContainsString('.has-open-sans-font-family');
    verify($css)->stringContainsString('.has-playfair-display-font-family');
    verify($css)->stringContainsString('.has-roboto-font-family');
    verify($css)->stringContainsString('.has-source-sans-pro-font-family');
    verify($css)->stringContainsString('.has-oswald-font-family');
    verify($css)->stringContainsString('.has-raleway-font-family');
    verify($css)->stringContainsString('.has-permanent-marker-font-family');
    verify($css)->stringContainsString('.has-pacifico-font-family');
  }
}
