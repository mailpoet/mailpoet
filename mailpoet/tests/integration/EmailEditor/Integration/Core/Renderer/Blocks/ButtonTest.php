<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\EmailEditor;
use MailPoet\EmailEditor\Engine\SettingsController;

class ButtonTest extends \MailPoetTest {
  /** @var Button */
  private $buttonRenderer;

  /** @var array */
  private $parsedButton = [
    'blockName' => 'core/button',
    'attrs' => [
      'width' => 50,
      'style' => [
        'spacing' => [
          'padding' => [
            'left' => '10px',
            'right' => '10px',
            'top' => '10px',
            'bottom' => '10px',
          ],
        ],
        'color' => [
          'background' => '#dddddd',
          'text' => '#111111',
        ],
      ],
    ],
    'innerBlocks' => [],
    'innerHTML' => '<div class="wp-block-button has-custom-width wp-block-button__width-50"><a href="http://example.com" class="wp-block-button__link has-text-color has-background has-link-color wp-element-button" style="color:#111111;background-color:#dddddd;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px">Button Text</a></div>',
    'innerContent' => ['<div class="wp-block-button has-custom-width wp-block-button__width-50"><a href="http://example.com" class="wp-block-button__link has-text-color has-background has-link-color wp-element-button" style="color:#111111;background-color:#dddddd;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px">Button Text</a></div>'],
    'email_attrs' => [
      'color' => '#111111',
      'width' => '320px',
    ],
  ];

  /** @var SettingsController */
  private $settingsController;

  public function _before(): void {
    $this->diContainer->get(EmailEditor::class)->initialize();
    $this->buttonRenderer = new Button();
    $this->settingsController = $this->diContainer->get(SettingsController::class);
  }

  public function testItRendersLink(): void {
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    verify($output)->stringContainsString('href="http://example.com"');
    verify($output)->stringContainsString('Button Text');
  }

  public function testItRendersPaddingBasedOnAttributesValue(): void {
    $this->parsedButton['attrs']['style']['spacing']['padding'] = [
      'left' => '10px',
      'right' => '20px',
      'top' => '30px',
      'bottom' => '40px',
    ];
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    verify($output)->stringContainsString('padding-left:10px;');
    verify($output)->stringContainsString('padding-right:20px;');
    verify($output)->stringContainsString('padding-top:30px;');
    verify($output)->stringContainsString('padding-bottom:40px;');
  }

  public function testItRendersColors(): void {
    $this->parsedButton['attrs']['style']['color'] = [
      'background' => '#000000',
      'text' => '#111111',
    ];
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    verify($output)->stringContainsString('bgcolor="#000000"');
    verify($output)->stringContainsString('background:#000000;');
    verify($output)->stringContainsString('color:#111111;');
  }

  public function testItRendersBorder(): void {
    $this->parsedButton['attrs']['style']['border'] = [
      'width' => '10px',
      'color' => '#111111',
    ];
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    verify($output)->stringContainsString('border-color:#111111;');
    verify($output)->stringContainsString('border-width:10px;');
    verify($output)->stringContainsString('border-style:solid;');
  }

  public function testItRendersBorderNone(): void {
    $this->parsedButton['attrs']['style']['border'] = [];
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    verify($output)->stringContainsString('border:none;');
  }

  public function testItRendersBorderWithTextColorFallback(): void {
    $this->parsedButton['attrs']['style']['border'] = [
      'width' => '10px',
    ];
    $this->parsedButton['attrs']['style']['color'] = [
      'text' => '#111111',
    ];
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    verify($output)->stringContainsString('border-color:#111111;');
  }

  public function testItRendersEachSideSpecificBorder(): void {
    $this->parsedButton['attrs']['style']['border'] = [
      'top' => ['width' => '1px', 'color' => '#111111'],
      'right' => ['width' => '2px', 'color' => '#222222'],
      'bottom' => ['width' => '3px', 'color' => '#333333'],
      'left' => ['width' => '4px', 'color' => '#444444'],
    ];
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    verify($output)->stringContainsString('border-top-width:1px;');
    verify($output)->stringContainsString('border-top-color:#111111;');

    verify($output)->stringContainsString('border-right-width:2px;');
    verify($output)->stringContainsString('border-right-color:#222222;');

    verify($output)->stringContainsString('border-bottom-width:3px;');
    verify($output)->stringContainsString('border-bottom-color:#333333;');

    verify($output)->stringContainsString('border-left-width:4px;');
    verify($output)->stringContainsString('border-left-color:#444444;');

    verify($output)->stringContainsString('border-style:solid;');
  }

  public function testItRendersBorderRadius(): void {
    $this->parsedButton['attrs']['style']['border'] = [
      'radius' => '10px',
    ];
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    verify($output)->stringContainsString('border-radius:10px;');
  }

  public function testItRendersFontSizeFromEmailAttrs(): void {
    $this->parsedButton['email_attrs']['font-size'] = '10px';
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    verify($output)->stringContainsString('font-size:10px;');
  }

  public function testItRendersCornerSpecificBorderRadius(): void {
    $this->parsedButton['attrs']['style']['border']['radius'] = [
      'topLeft' => '1px',
      'topRight' => '2px',
      'bottomLeft' => '3px',
      'bottomRight' => '4px',
    ];
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    verify($output)->stringContainsString('border-top-left-radius:1px;');
    verify($output)->stringContainsString('border-top-right-radius:2px;');
    verify($output)->stringContainsString('border-bottom-left-radius:3px;');
    verify($output)->stringContainsString('border-bottom-right-radius:4px;');
  }

  public function testItRendersDefaultBackgroundColor(): void {
    unset($this->parsedButton['attrs']['style']['color']);
    unset($this->parsedButton['attrs']['style']['spacing']['padding']);
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    // Verify default background colors theme.json for email editor
    // These can't be set via CSS inliner because of special email HTML markup
    verify($output)->stringContainsString('bgcolor="#32373c"');
    verify($output)->stringContainsString('background:#32373c;');
  }

  public function testItRendersBackgroundColorSetBySlug(): void {
    unset($this->parsedButton['attrs']['style']['color']);
    unset($this->parsedButton['attrs']['style']['spacing']['padding']);
    $this->parsedButton['attrs']['backgroundColor'] = 'black';
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    // For other blocks this is handled by CSS-inliner, but for button we need to handle it manually
    // because of special email HTML markup
    verify($output)->stringContainsString('bgcolor="#000000"');
    verify($output)->stringContainsString('background:#000000;');
    verify($output)->stringContainsString('background-color:#000000;');
  }

  public function testItRendersFontColorSetBySlug(): void {
    unset($this->parsedButton['attrs']['style']['color']);
    unset($this->parsedButton['attrs']['style']['spacing']['padding']);
    $this->parsedButton['attrs']['textColor'] = 'white';
    $output = $this->buttonRenderer->render($this->parsedButton['innerHTML'], $this->parsedButton, $this->settingsController);
    // For other blocks this is handled by CSS-inliner, but for button we need to handle it manually
    // because of special email HTML markup
    verify($output)->stringContainsString('color:#ffffff');
  }
}
