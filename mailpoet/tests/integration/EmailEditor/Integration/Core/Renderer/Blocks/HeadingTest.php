<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\EmailEditor;
use MailPoet\EmailEditor\Engine\SettingsController;

class HeadingTest extends \MailPoetTest {
  /** @var Heading */
  private $headingRenderer;

  /** @var array */
  private $parsedHeading = [
    'blockName' => 'core/heading',
    'attrs' => [
      'level' => 1,
      'backgroundColor' => 'vivid-red',
      'textColor' => 'pale-cyan-blue',
      'textAlign' => 'center',
      'style' => [
        'typography' => [
          'textTransform' => 'lowercase',
          'fontSize' => '24px',
        ],
      ],
    ],
    'email_attrs' => [
      'width' => '640px',
      'font-size' => '24px',
    ],
    'innerBlocks' => [],
    'innerHTML' => '<h1 class="has-pale-cyan-blue-color has-vivid-red-background-color has-text-color has-background">This is Heading 1</h1>',
    'innerContent' => [
      0 => '<h1 class="has-pale-cyan-blue-color has-vivid-red-background-color has-text-color has-background">This is Heading 1</h1>',
    ],
  ];

  /** @var SettingsController */
  private $settingsController;

  public function _before() {
    $this->diContainer->get(EmailEditor::class)->initialize();
    $this->headingRenderer = new Heading();
    $this->settingsController = $this->diContainer->get(SettingsController::class);
  }

  public function testItRendersContent(): void {
    $rendered = $this->headingRenderer->render('<h1>This is Heading 1</h1>', $this->parsedHeading, $this->settingsController);
    verify($rendered)->stringContainsString('This is Heading 1');
    verify($rendered)->stringContainsString('width: 100%;');
    verify($rendered)->stringContainsString('font-size:24px;');
    verify($rendered)->stringNotContainsString('width:640px;');
  }

  public function testItRendersBlockAttributes(): void {
    $rendered = $this->headingRenderer->render('<h1>This is Heading 1</h1>', $this->parsedHeading, $this->settingsController);
    verify($rendered)->stringContainsString('background-color:#cf2e2e'); // background color from theme.json matching vivid-red
    verify($rendered)->stringContainsString('color:#8ed1fc;'); // color from theme.json matching pale-cyan-blue
    verify($rendered)->stringContainsString('text-transform:lowercase;');
    verify($rendered)->stringContainsString('text-align:center;');
  }

  public function testItReplacesFluidFontSizeInContent(): void {
    $rendered = $this->headingRenderer->render('<h1 style="font-size:clamp(10px, 20px, 24px)">This is Heading 1</h1>', $this->parsedHeading, $this->settingsController);
    verify($rendered)->stringContainsString('font-size:24px');
  }
}
