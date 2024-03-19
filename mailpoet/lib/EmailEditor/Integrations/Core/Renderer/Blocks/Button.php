<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Integrations\Utils\DomDocumentHelper;
use WP_Style_Engine;

/**
 * Renders a button block.
 * @see https://www.activecampaign.com/blog/email-buttons
 * @see https://documentation.mjml.io/#mj-button
 */
class Button implements BlockRenderer {
  private function getStylesFromBlock(array $block_styles, $skip_convert_vars = false) {
    $styles = wp_style_engine_get_styles($block_styles, ['convert_vars_to_classnames' => $skip_convert_vars]);
    return (object)wp_parse_args($styles, [
      'css' => '',
      'declarations' => [],
      'classnames' => '',
    ]);
  }

  private function replaceSpacingPresets($styles, $presets) {
    $replaced = [];
    foreach ($styles as $key => $value) {
      if (strstr($value, 'var:preset|spacing|')) {
        $slug = str_replace('var:preset|spacing|', '', $value);
        $replaced[$key] = $presets[$slug] ?? $value;
      } else {
        $replaced[$key] = $value;
      }
    }
    return $replaced;
  }

  private function convertToPixels($size, $baseFontSize) {
    $unit = '';
    $value = '';

    // Extract unit and value from the size
    preg_match('/^([\d\.]+)(px|em|%)$/', $size, $matches);
    if (count($matches) == 3) {
        $value = absint($matches[1]);
        $unit = $matches[2];
    } else {
        return 0;
    }

    // Convert size to pixels
    switch ($unit) {
      case 'px':
        return $value;
      case 'em':
        return $value * $baseFontSize;
      case '%':
        return ($value / 100) * $baseFontSize;
      default:
        return 0;
    }
  }

  /**
   * We need to space using em units, so calculate the percentage of 1em for padding values.
   *
   * @param string|float|int $value Size in pixels.
   * @return string
   */
  private function msoPaddingPercentage($value, $baseFontSize = 16) {
    return round((floatval($value) / $baseFontSize), 2) * 100 . '%';
  }

  private function msoPadding($wrapContent, $padding, $baseFontSize = 16) {
    // Convert padding to pixels.
    $paddingTop = $this->convertToPixels($padding['padding-top'], $baseFontSize);
    $paddingBottom = $this->convertToPixels($padding['padding-bottom'], $baseFontSize);
    $paddingLeft = $this->convertToPixels($padding['padding-left'], $baseFontSize);
    $paddingRight = $this->convertToPixels($padding['padding-right'], $baseFontSize);

    return sprintf(
      '<!--[if mso]><i style="%s" hidden>&emsp;</i><span style="%s"><![endif]-->%s<!--[if mso]></span><i style="%s" hidden>&emsp;&#8203;</i><![endif]-->',
      // Top and left padding.
      esc_attr(WP_Style_Engine::compile_css(['mso-font-width' => $this->msoPaddingPercentage($paddingLeft, $baseFontSize), 'mso-text-raise' => $this->msoPaddingPercentage($paddingTop + $paddingBottom, $baseFontSize)], '')),
      // Bottom padding.
      esc_attr(WP_Style_Engine::compile_css(['mso-text-raise' => $this->msoPaddingPercentage($paddingBottom, $baseFontSize)], '')),
      $wrapContent,
      // Right padding.
      esc_attr(WP_Style_Engine::compile_css(['mso-font-width' => $this->msoPaddingPercentage($paddingRight, $baseFontSize)], '')),
    );
  }

  public function render($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    if (empty($parsedBlock['innerHTML'])) {
      return '';
    }

    $themeSettings = $settingsController->getTheme()->get_settings();
    $themeData = $settingsController->getTheme()->get_data();
    $domHelper = new DomDocumentHelper($parsedBlock['innerHTML']);
    $buttonLink = $domHelper->findElement('a');

    if (!$buttonLink) {
      return '';
    }

    $buttonText = $domHelper->getElementInnerHTML($buttonLink) ?: '';
    $buttonUrl = $buttonLink->getAttribute('href') ?: '#';
    $buttonClasses = $domHelper->getAttributeValueByTagName('div', 'class') ?? '';

    $blockAttributes = wp_parse_args($parsedBlock['attrs'] ?? [], [
      'width' => '100%',
      'style' => [],
      'textAlign' => 'center',
      'backgroundColor' => '',
      'textColor' => '',
    ]);

    $blockStyles = array_replace_recursive(
      array_replace_recursive(
        $themeData['styles']['blocks']['core/button'] ?? [],
        [
          'color' => array_filter([
            'background' => $blockAttributes['backgroundColor'] ? $settingsController->translateSlugToColor($blockAttributes['backgroundColor']) : null,
            'text' => $blockAttributes['textColor'] ? $settingsController->translateSlugToColor($blockAttributes['textColor']) : null,
          ]),
          'typography' => [
            'fontSize' => $parsedBlock['email_attrs']['font-size'] ?? 'inherit',
            'textDecoration' => $parsedBlock['email_attrs']['text-decoration'] ?? 'none',
          ],
        ]
      ),
      $blockAttributes['style'] ?? []
    );

    if (isset($blockStyles['border']['width']) && empty($blockStyles['border']['style'])) {
      $blockStyles['border']['style'] = 'solid';
    }

    $wrapperStyles = $this->getStylesFromBlock([
      'border' => $blockStyles['border'] ?? [],
      'typography' => $blockStyles['typography'] ?? [],
      'color' => [
        'text' => $blockStyles['color']['text'] ?? '',
        'background' => $blockStyles['color']['background'] ?? '',
      ],
    ]);

    $linkStyles = $this->getStylesFromBlock([
      'color' => [
        'text' => $blockStyles['color']['text'] ?? '',
      ],
      'typography' => $blockStyles['typography'],
      'spacing' => [
        'padding' => $blockStyles['spacing']['padding'] ?? [],
      ],
    ]);

    $paddingStyles = wp_parse_args(
      $this->replaceSpacingPresets(
        $this->getStylesFromBlock(['spacing' => ['padding' => $blockStyles['spacing']['padding'] ?? []]], true)->declarations,
        wp_list_pluck($themeSettings['spacing']['spacingSizes']['default'] ?? [], 'size', 'slug')
      ),
      [
        'padding-top' => '0px',
        'padding-right' => '0px',
        'padding-bottom' => '0px',
        'padding-left' => '0px',
      ]
    );

    return sprintf(
      '<div class="%s" style="%stext-align:%s;width:%s;"><a rel="noopener" class="%s" style="display:block;word-break:break-word;%s" href="%s" target="_blank">%s</a></div>',
      esc_attr($buttonClasses . ' ' . $wrapperStyles->classnames),
      esc_attr($wrapperStyles->css),
      esc_attr($blockAttributes['textAlign'] ?? 'center'),
      esc_attr(isset($blockAttributes['width']) ? '100%' : 'auto'),
      esc_attr($linkStyles->classnames),
      esc_attr($linkStyles->css),
      esc_url($buttonUrl),
      $this->msoPadding($buttonText, $paddingStyles, absint($linkStyles->declarations['font-size']) ?? 16)
    );
  }
}
