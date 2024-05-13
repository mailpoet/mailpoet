<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Engine\ThemeController;

class SettingsControllerTest extends \MailPoetUnitTest {
  public function testItGetsCorrectLayoutWidthWithoutPadding(): void {
    $themeJsonMock = $this->createMock(\WP_Theme_JSON::class);
    $themeJsonMock->method('get_data')->willReturn([
      'styles' => [
        'spacing' => [
          'padding' => [
            'left' => '10px',
            'right' => '10px',
          ],
        ],
      ],
    ]);
    $themeController = $this->createMock(ThemeController::class);
    $themeController->method('getTheme')->willReturn($themeJsonMock);
    $themeController->method('getSettings')->willReturn([
      "layout" => [
        "contentSize" => "660px",
        "wideSize" => "660px",
      ],
    ]);
    $settingsController = new SettingsController($themeController);
    $layoutWidth = $settingsController->getLayoutWidthWithoutPadding();
    // default width is 660px and if we subtract padding from left and right we must get the correct value
    $expectedWidth = (int)SettingsController::EMAIL_WIDTH - 10 * 2;
    verify($layoutWidth)->equals($expectedWidth . 'px');
  }
}
