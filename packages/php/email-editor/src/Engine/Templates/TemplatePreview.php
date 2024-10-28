<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Templates;

use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Engine\ThemeController;
use MailPoet\EmailEditor\Validator\Builder;
use WP_Theme_JSON;

class TemplatePreview {
  private ThemeController $themeController;
  private SettingsController $settingsController;
  private Templates $templates;

  public function __construct(
    ThemeController $themeController,
    SettingsController $settingsController,
    Templates $templates
  ) {
    $this->themeController = $themeController;
    $this->settingsController = $settingsController;
    $this->templates = $templates;
  }

  public function initialize(): void {
    register_rest_field(
      'wp_template',
      'email_theme_css',
      [
        'get_callback' => [$this, 'getEmailThemePreviewCss'],
        'update_callback' => null,
        'schema' => Builder::string()->toArray(),
      ]
    );
  }

  /**
   * Generates CSS for preview of email theme
   * They are applied in the preview BLockPreview in template selection
   */
  public function getEmailThemePreviewCss($template): string {
    $editorTheme = clone $this->themeController->getTheme();
    $templateTheme = $this->templates->getBlockTemplateTheme($template['id'], $template['wp_id']);
    if (is_array($templateTheme)) {
      $editorTheme->merge(new WP_Theme_JSON($templateTheme, 'custom'));
    }
    $editorSettings = $this->settingsController->getSettings();
    $additionalCSS = '';
    foreach ($editorSettings['styles'] as $style) {
      $additionalCSS .= $style['css'];
    }
    // Set proper content width for previews
    $layoutSettings = $this->themeController->getLayoutSettings();
    $additionalCSS .= ".is-root-container { width: {$layoutSettings['contentSize']}; margin: 0 auto; }";
    return $editorTheme->get_stylesheet() . $additionalCSS;
  }
}
