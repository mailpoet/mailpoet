<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

/**
 * E-mail editor works with own theme.json which defines settings for the editor and styles for the e-mail.
 * This class is responsible for accessing data defined by the theme.json.
 */
class ThemeController {
  public function getTheme(): \WP_Theme_JSON {
    $coreThemeData = \WP_Theme_JSON_Resolver::get_core_data();
    $themeJson = (string)file_get_contents(dirname(__FILE__) . '/theme.json');
    $themeJson = json_decode($themeJson, true);
    /** @var array $themeJson */
    $coreThemeData->merge(new \WP_Theme_JSON($themeJson, 'default'));
    return apply_filters('mailpoet_email_editor_theme_json', $coreThemeData);
  }

  public function getStylesheetForRendering(): string {
    $emailThemeSettings = $this->getTheme()->get_settings();

    $cssPresets = '';
    // Font family classes
    foreach ($emailThemeSettings['typography']['fontFamilies']['default'] as $fontFamily) {
      $cssPresets .= ".has-{$fontFamily['slug']}-font-family { font-family: {$fontFamily['fontFamily']}; } \n";
    }
    // Font size classes
    foreach ($emailThemeSettings['typography']['fontSizes']['default'] as $fontSize) {
      $cssPresets .= ".has-{$fontSize['slug']}-font-size { font-size: {$fontSize['size']}; } \n";
    }
    // Color palette classes
    foreach ($emailThemeSettings['color']['palette']['default'] as $color) {
      $cssPresets .= ".has-{$color['slug']}-color { color: {$color['color']}; } \n";
      $cssPresets .= ".has-{$color['slug']}-background-color { background-color: {$color['color']}; } \n";
    }

    // Block specific styles
    $cssBlocks = '';
    $blocks = $this->getTheme()->get_styles_block_nodes();
    foreach ($blocks as $blockMetadata) {
      $cssBlocks .= $this->getTheme()->get_styles_for_block($blockMetadata);
    }

    return $cssPresets . $cssBlocks;
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

  public function translateSlugToColor(string $colorSlug): string {
    $settings = $this->getTheme()->get_settings();
    foreach ($settings['color']['palette']['default'] as $colorDefinition) {
      if ($colorDefinition['slug'] === $colorSlug) {
        return $colorDefinition['color'];
      }
    }
    return $colorSlug;
  }
}
