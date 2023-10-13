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
    'enableCustomSpacing' => true,
    'enableCustomLineHeight' => true,
    'disableCustomFontSizes' => false,
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
    $settings = self::DEFAULT_SETTINGS;
    $settings['allowedBlockTypes'] = self::ALLOWED_BLOCK_TYPES;
    $settings['defaultEditorStyles'] = [[ 'css' => $this->stylesController->getEmailContentStyles() ]];
    return $settings;
  }
}
