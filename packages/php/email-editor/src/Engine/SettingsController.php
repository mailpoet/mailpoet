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
    'core/group',
  ];

  const DEFAULT_SETTINGS = [
    'enableCustomUnits' => ['px', '%'],
  ];

  /**
   * Width of the email in pixels.
   * @var string
   */
  const EMAIL_WIDTH = '660px';

  private ThemeController $themeController;

  private array $iframeAssets = [];

  /**
   * @param ThemeController $themeController
   */
  public function __construct(
    ThemeController $themeController
  ) {
    $this->themeController = $themeController;
  }

  public function init() {
    // We need to initialize these assets early because they are read from global variables $wp_styles and $wp_scripts
    // and in later WordPress page load pages they contain stuff we don't want (e.g. html for admin login popup)
    // in the post editor this is called directly in post.php
    $this->iframeAssets = _wp_get_iframed_editor_assets();
  }

  public function getSettings(): array {
    $coreDefaultSettings = \get_default_block_editor_settings();
    $themeSettings = $this->themeController->getSettings();

    $settings = array_merge($coreDefaultSettings, self::DEFAULT_SETTINGS);
    $settings['allowedBlockTypes'] = self::ALLOWED_BLOCK_TYPES;
    // Assets for iframe editor (component styles, scripts, etc.)
    $settings['__unstableResolvedAssets'] = $this->iframeAssets;
    $editorContentStyles = file_get_contents(__DIR__ . '/content-editor.css');
    $settings['styles'] = [
      ['css' => $editorContentStyles],
    ];

    $settings['__experimentalFeatures'] = $themeSettings;
    // Controls which alignment options are available for blocks
    $settings['supportsLayout'] = true; // Allow using default layouts
    $settings['__unstableIsBlockBasedTheme'] = true; // For default setting this to true disables wide and full alignments
    return $settings;
  }

  /**
   * @return array{contentSize: string, wideSize: string}
   */
  public function getLayout(): array {
    $layoutSettings = $this->themeController->getLayoutSettings();
    return [
      'contentSize' => $layoutSettings['contentSize'],
      'wideSize' => $layoutSettings['wideSize'],
    ];
  }

  /**
   * @return array{
   *   spacing: array{
   *     blockGap: string,
   *     padding: array{bottom: string, left: string, right: string, top: string}
   *   },
   *   color: array{
   *     background: string
   *   },
   *   typography: array{
   *     fontFamily: string
   *   }
   * }
   */
  public function getEmailStyles(): array {
    $theme = $this->getTheme();
    return $theme->get_data()['styles'];
  }

  public function getLayoutWidthWithoutPadding(): string {
    $styles = $this->getEmailStyles();
    $layout = $this->getLayout();
    $width = $this->parseNumberFromStringWithPixels($layout['contentSize']);
    $width -= $this->parseNumberFromStringWithPixels($styles['spacing']['padding']['left']);
    $width -= $this->parseNumberFromStringWithPixels($styles['spacing']['padding']['right']);
    return "{$width}px";
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
    return $this->themeController->getTheme();
  }

  public function translateSlugToFontSize(string $fontSize): string {
    return $this->themeController->translateSlugToFontSize($fontSize);
  }

  public function translateSlugToColor(string $colorSlug): string {
    return $this->themeController->translateSlugToColor($colorSlug);
  }
}
