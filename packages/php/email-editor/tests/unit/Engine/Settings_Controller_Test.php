<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

class Settings_Controller_Test extends \MailPoetUnitTest {
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
    $themeController = $this->createMock(Theme_Controller::class);
    $themeController->method('get_theme')->willReturn($themeJsonMock);
    $themeController->method('get_settings')->willReturn([
      "layout" => [
        "contentSize" => "660px",
        "wideSize" => "660px",
      ],
    ]);
    $settingsController = new Settings_Controller($themeController);
    $layoutWidth = $settingsController->get_layout_width_without_padding();
    // default width is 660px and if we subtract padding from left and right we must get the correct value
    $expectedWidth = (int)Settings_Controller::EMAIL_WIDTH - 10 * 2;
    $this->assertEquals($expectedWidth . 'px', $layoutWidth);
  }
}
