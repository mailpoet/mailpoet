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

  /** @var StylesController */
  private $stylesController;

  public function __construct(
    StylesController $stylesController
  ) {
    $this->stylesController = $stylesController;
  }

  public function getSettings(): array {
    $coreDefaultSettings = get_default_block_editor_settings();
    $coreThemeData = \WP_Theme_JSON_Resolver::get_core_data();
    $coreSettings = $coreThemeData->get_settings();

    // Enable custom spacing
    $coreSettings['spacing']['units'] = ['px'];
    $coreSettings['spacing']['padding'] = true;

    $settings = array_merge($coreDefaultSettings, self::DEFAULT_SETTINGS);
    $settings['allowedBlockTypes'] = self::ALLOWED_BLOCK_TYPES;
    $settings['defaultEditorStyles'] = [[ 'css' => $this->stylesController->getEmailContentStyles() ]];
    $settings['__experimentalFeatures'] = $coreSettings;

    return $settings;
  }
}
