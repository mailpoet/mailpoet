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
    // default width is 660px and padding for left and right is 10px
    verify($layoutWidth)->equals('640px');
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
