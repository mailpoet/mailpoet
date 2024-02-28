<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Engine\ThemeController;

class SettingsControllerTest extends \MailPoetUnitTest {
  public function testItGetsMainLayoutStyles(): void {
    $settingsController = new SettingsController($this->makeEmpty(ThemeController::class));
    $styles = $settingsController->getEmailStyles();
    verify($styles)->arrayHasKey('layout');
    verify($styles)->arrayHasKey('colors');
    verify($styles)->arrayHasKey('typography');
    $layoutStyles = $styles['layout'];
    verify($layoutStyles)->arrayHasKey('background');
    verify($layoutStyles)->arrayHasKey('padding');
    verify($layoutStyles)->arrayHasKey('width');
  }

  public function testItGetsCorrectLayoutWidthWithoutPadding(): void {
    $settingsController = new SettingsController($this->makeEmpty(ThemeController::class));
    $layoutWidth = $settingsController->getLayoutWidthWithoutPadding();
    // default width is 660px and if we subtract padding from left and right we must get the correct value
    $expectedWidth = (int)SettingsController::EMAIL_WIDTH - (int)SettingsController::FLEX_GAP * 2;
    verify($layoutWidth)->equals($expectedWidth . 'px');
  }

  public function testItConvertsStylesToString(): void {
    $settingsController = new SettingsController($this->makeEmpty(ThemeController::class));
    $styles = [
      'width' => '600px',
      'background' => '#ffffff',
      'padding-left' => '15px',
    ];
    $string = $settingsController->convertStylesToString($styles);
    verify($string)->equals('width:600px;background:#ffffff;padding-left:15px;');
  }
}
