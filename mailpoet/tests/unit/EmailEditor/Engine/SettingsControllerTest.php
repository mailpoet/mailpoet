<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\SettingsController;

class SettingsControllerTest extends \MailPoetUnitTest {
  public function testItGetsMainLayoutStyles(): void {
    $settingsController = new SettingsController();
    $layoutStyles = $settingsController->getEmailLayoutStyles();
    verify($layoutStyles)->arrayHasKey('width');
    verify($layoutStyles)->arrayHasKey('background');
    verify($layoutStyles)->arrayHasKey('padding');
  }

  public function testItGetsCorrectLayoutWidthWithoutPadding(): void {
    $settingsController = new SettingsController();
    $layoutWidth = $settingsController->getLayoutWidthWithoutPadding();
    // default width is 660px and if we subtract padding from left and right we must get the correct value
    $expectedWidth = (int)SettingsController::EMAIL_WIDTH - (int)SettingsController::FLEX_GAP * 2;
    verify($layoutWidth)->equals($expectedWidth . 'px');
  }

  public function testItConvertsStylesToString(): void {
    $settingsController = new SettingsController();
    $styles = [
      'width' => '600px',
      'background' => '#ffffff',
      'padding-left' => '15px',
    ];
    $string = $settingsController->convertStylesToString($styles);
    verify($string)->equals('width:600px;background:#ffffff;padding-left:15px;');
  }
}
