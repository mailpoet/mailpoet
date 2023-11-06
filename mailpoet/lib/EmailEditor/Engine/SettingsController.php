<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

class SettingsController {

  const ALLOWED_BLOCK_TYPES = [
    'core/paragraph',
    'core/heading',
    'core/column',
    'core/columns',
    'core/image',
  ];

  const DEFAULT_SETTINGS = [
    'enableCustomUnits' => ['px', '%'],
    '__experimentalFeatures' => [
      'color' => [
        'custom' => true,
        'text' => true,
        'background' => true,
        'customGradient' => false,
        'defaultPalette' => true,
        'palette' => [
          'default' => [],
        ],
      ],
    ],
  ];

  /**
   * Width of the email in pixels.
   * @var string
   */
  const EMAIL_WIDTH = '660px';

  /**
   * Width of the email in pixels.
   * @var string
   */
  const EMAIL_BACKGROUND = '#cccccc';

  /**
   * Padding of the email in pixels.
   * @var string
   */
  const EMAIL_PADDING = '10px';

  /**
   * Default styles applied to the email. These are going to be replaced by style settings.
   * This is currently more af a proof of concept that we can apply styles to the email.
   * We will gradually replace these hardcoded values with styles saved as global styles or styles saved with the email.
   * @var array
   */
  const DEFAULT_EMAIL_CONTENT_STYLES = [
    'typography' => [
      'fontFamily' => "Arial, 'Helvetica Neue', Helvetica, sans-serif",
      'fontSize' => '16px',
    ],
    'h1' => [
      'typography' => [
        'fontSize' => '32px',
      ],
    ],
    'h2' => [
      'typography' => [
        'fontSize' => '24px',
      ],
    ],
    'h3' => [
      'typography' => [
        'fontSize' => '18px',
      ],
    ],
    'h4' => [
      'typography' => [
        'fontSize' => '16px',
      ],
    ],
    'h5' => [
      'typography' => [
        'fontSize' => '14px',
      ],
    ],
    'h6' => [
      'typography' => [
        'fontSize' => '12px',
      ],
    ],
  ];

  public function getSettings(): array {
    $coreDefaultSettings = get_default_block_editor_settings();
    $coreThemeData = \WP_Theme_JSON_Resolver::get_core_data();
    $coreSettings = $coreThemeData->get_settings();

    // Enable custom spacing
    $coreSettings['spacing']['units'] = ['px'];
    $coreSettings['spacing']['padding'] = true;

    $settings = array_merge($coreDefaultSettings, self::DEFAULT_SETTINGS);
    $settings['allowedBlockTypes'] = self::ALLOWED_BLOCK_TYPES;
    $settings['defaultEditorStyles'] = [[ 'css' => $this->getEmailContentStyles() ]];
    $settings['__experimentalFeatures'] = $coreSettings;

    return $settings;
  }

  public function getEmailContentStyles(): array {
    return self::DEFAULT_EMAIL_CONTENT_STYLES;
  }

  /**
   * @return array{width: string, background: string, padding: array{bottom: string, left: string, right: string, top: string}}
   */
  public function getEmailLayoutStyles(): array {
    return [
      'width' => self::EMAIL_WIDTH,
      'background' => self::EMAIL_BACKGROUND,
      'padding' => [
        'bottom' => self::EMAIL_PADDING,
        'left' => self::EMAIL_PADDING,
        'right' => self::EMAIL_PADDING,
        'top' => self::EMAIL_PADDING,
      ],
    ];
  }

  public function getLayoutWidthWithoutPadding(): string {
    $layoutStyles = $this->getEmailLayoutStyles();
    $width = $this->parseNumberFromStringWithPixels($layoutStyles['width']);
    $width -= $this->parseNumberFromStringWithPixels($layoutStyles['padding']['left']);
    $width -= $this->parseNumberFromStringWithPixels($layoutStyles['padding']['right']);
    return "{$width}px";
  }

  private function parseNumberFromStringWithPixels(string $string): float {
    return (float)str_replace('px', '', $string);
  }
}
