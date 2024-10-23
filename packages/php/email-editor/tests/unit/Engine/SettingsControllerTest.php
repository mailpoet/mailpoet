<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

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
    $expectedWidth = 660 - 10 * 2;
    $this->assertEquals($expectedWidth . 'px', $layoutWidth);
  }
}
