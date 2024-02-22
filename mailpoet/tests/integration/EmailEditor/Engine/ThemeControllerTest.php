<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

class ThemeControllerTest extends \MailPoetTest {
  private ThemeController $themeController;

  public function _before() {
    parent::_before();
    $this->themeController = $this->diContainer->get(ThemeController::class);
  }

  public function testItGeneratesCssStylesForRenderer() {
    $css = $this->themeController->getStylesheetForRendering();
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

    verify($css)->stringContainsString('.has-small-font-size');
    verify($css)->stringContainsString('.has-medium-font-size');
    verify($css)->stringContainsString('.has-large-font-size');
    verify($css)->stringContainsString('.has-x-large-font-size');
  }

  public function testItCanTranslateFontSizeSlug() {
    verify($this->themeController->translateSlugToFontSize('small'))->equals('13px');
    verify($this->themeController->translateSlugToFontSize('medium'))->equals('20px');
    verify($this->themeController->translateSlugToFontSize('large'))->equals('36px');
    verify($this->themeController->translateSlugToFontSize('x-large'))->equals('42px');
    verify($this->themeController->translateSlugToFontSize('unknown'))->equals('unknown');
  }

  public function testItCanTranslateColorSlug() {
    verify($this->themeController->translateSlugToColor('black'))->equals('#000000');
    verify($this->themeController->translateSlugToColor('white'))->equals('#ffffff');
    verify($this->themeController->translateSlugToColor('cyan-bluish-gray'))->equals('#abb8c3');
    verify($this->themeController->translateSlugToColor('pale-pink'))->equals('#f78da7');
  }
}
