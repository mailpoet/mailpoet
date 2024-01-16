<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

class SettingsController {

  const ALLOWED_BLOCK_TYPES = [
    'core/button',
    'core/buttons',
    'core/paragraph',
    'core/heading',
    'core/column',
    'core/columns',
    'core/image',
    'core/list',
    'core/list-item',
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
   * Color of email layout background.
   * @var string
   */
  const EMAIL_LAYOUT_BACKGROUND = '#cccccc';

  /**
   * Padding of the email in pixels.
   * @var string
   */
  const EMAIL_PADDING = '10px';

  /**
   * Gap between blocks in flex layouts
   * @var string
   */
  const FLEX_GAP = '16px';

  private $availableStylesheets = '';

  public function getSettings(): array {
    $coreDefaultSettings = get_default_block_editor_settings();
    $editorTheme = $this->getTheme();
    $themeSettings = $editorTheme->get_settings();

    // body selector is later transformed to .editor-styles-wrapper
    // setting padding for bottom and top is needed because \WP_Theme_JSON::get_stylesheet() set them only for .wp-site-blocks selector
    $contentVariables = 'body {';
    $contentVariables .= 'padding-bottom: var(--wp--style--root--padding-bottom);';
    $contentVariables .= 'padding-top: var(--wp--style--root--padding-top);';
    $contentVariables .= '--wp--style--block-gap:' . self::FLEX_GAP . ';';
    $contentVariables .= '}';

    $settings = array_merge($coreDefaultSettings, self::DEFAULT_SETTINGS);
    $settings['allowedBlockTypes'] = self::ALLOWED_BLOCK_TYPES;
    $flexEmailLayoutStyles = file_get_contents(__DIR__ . '/flex-email-layout.css');

    $settings['styles'] = [
      $coreDefaultSettings['defaultEditorStyles'][0],
      ['css' => wp_get_global_stylesheet(['base-layout-styles'])],
      ['css' => $editorTheme->get_stylesheet()],
      ['css' => $contentVariables],
      ['css' => $flexEmailLayoutStyles],
    ];

    $settings['__experimentalFeatures'] = $themeSettings;
    // Enable border radius, color, style and width where possible
    $settings['__experimentalFeatures']['border'] = [
      "radius" => true,
      "color" => true,
      "style" => true,
      "width" => true,
    ];

    // Enabling alignWide allows full width for specific blocks such as columns, heading, image, etc.
    $settings['alignWide'] = true;

    // Disable gradients. We cannot render them in emails.
    $settings['disableCustomGradients'] = true;
    $settings['__experimentalFeatures']['color']['customGradient'] = false;
    $settings['__experimentalFeatures']['color']['defaultGradients'] = false;
    $settings['__experimentalFeatures']['color']['gradients'] = [];

    return $settings;
  }

  /**
   * @return array{contentSize: string, layout: string}
   */
  public function getLayout(): array {
    return [
      'contentSize' => self::EMAIL_WIDTH,
      'layout' => 'constrained',
    ];
  }

  public function getAvailableStylesheets(): string {
    if ($this->availableStylesheets) return $this->availableStylesheets;
    $coreThemeData = \WP_Theme_JSON_Resolver::get_core_data();
    $this->availableStylesheets = $coreThemeData->get_stylesheet();
    return $this->availableStylesheets;
  }

  /**
   * @return array{width: string, background: string, padding: array{bottom: string, left: string, right: string, top: string}}
   */
  public function getEmailLayoutStyles(): array {
    return [
      'width' => self::EMAIL_WIDTH,
      'background' => self::EMAIL_LAYOUT_BACKGROUND,
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

  /**
   * This functions converts an array of styles to a string that can be used in HTML.
   */
  public function convertStylesToString(array $styles): string {
    $cssString = '';
    foreach ($styles as $property => $value) {
      $cssString .= $property . ':' . $value . ';';
    }
    return trim($cssString); // Remove trailing space and return the formatted string
  }

  public function parseStylesToArray(string $styles): array {
    $styles = explode(';', $styles);
    $parsedStyles = [];
    foreach ($styles as $style) {
      $style = explode(':', $style);
      if (count($style) === 2) {
        $parsedStyles[trim($style[0])] = trim($style[1]);
      }
    }
    return $parsedStyles;
  }

  public function parseNumberFromStringWithPixels(string $string): float {
    return (float)str_replace('px', '', $string);
  }

  public function getTheme(): \WP_Theme_JSON {
    $coreThemeData = \WP_Theme_JSON_Resolver::get_core_data();
    $themeJson = (string)file_get_contents(dirname(__FILE__) . '/theme.json');
    $themeJson = json_decode($themeJson, true);
    /** @var array $themeJson */
    $coreThemeData->merge(new \WP_Theme_JSON($themeJson, 'default'));
    return $coreThemeData;
  }

  public function getStylesheetForRendering(): string {
    $emailThemeSettings = $this->getTheme()->get_settings();
    $css = '';
    // Font family classes
    foreach ($emailThemeSettings['typography']['fontFamilies']['default'] as $fontFamily) {
      $css .= ".has-{$fontFamily['slug']}-font-family { font-family: {$fontFamily['fontFamily']}; } \n";
    }
    // Font size classes
    foreach ($emailThemeSettings['typography']['fontSizes']['default'] as $fontSize) {
      $css .= ".has-{$fontSize['slug']}-font-size { font-size: {$fontSize['size']}; } \n";
    }
    return $css;
  }

  public function translateSlugToFontSize(string $fontSize): string {
    $settings = $this->getTheme()->get_settings();
    foreach ($settings['typography']['fontSizes']['default'] as $fontSizeDefinition) {
      if ($fontSizeDefinition['slug'] === $fontSize) {
        return $fontSizeDefinition['size'];
      }
    }
    return $fontSize;
  }
}
