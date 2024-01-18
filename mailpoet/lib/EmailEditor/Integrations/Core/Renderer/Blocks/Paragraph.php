<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\Util\Helpers;

class Paragraph implements BlockRenderer {
  public function render(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $blockContent = $this->removePaddingFromElement($blockContent, ['tag_name' => 'p']);
    return str_replace('{paragraph_content}', $blockContent, $this->getBlockWrapper($parsedBlock, $settingsController));
  }

  /**
   * Based on MJML <mj-text>
   */
  private function getBlockWrapper(array $parsedBlock, SettingsController $settingsController): string {
    $themeData = $settingsController->getTheme()->get_data();
    $availableStylesheets = $settingsController->getAvailableStylesheets();

    $align = $parsedBlock['attrs']['align'] ?? 'left';
    $marginTop = $parsedBlock['email_attrs']['margin-top'] ?? '0px';

    $styles = [
      'background-color' => $parsedBlock['attrs']['style']['color']['background'] ?? 'transparent',
      'text-align' => $align,
      'padding-bottom' => $parsedBlock['attrs']['style']['spacing']['padding']['bottom'] ?? '0px',
      'padding-left' => $parsedBlock['attrs']['style']['spacing']['padding']['left'] ?? '0px',
      'padding-right' => $parsedBlock['attrs']['style']['spacing']['padding']['right'] ?? '0px',
      'padding-top' => $parsedBlock['attrs']['style']['spacing']['padding']['top'] ?? '0px',
    ];

    foreach ($parsedBlock['email_attrs'] ?? [] as $property => $value) {
      if ($property === 'width') continue; // width is handled by the wrapping blocks (columns, column)
      if ($property === 'margin-top') continue; // margin-top is set on the wrapping div co we need to avoid duplication
      $styles[$property] = $value;
    }

    if (!isset($styles['font-size'])) {
      $styles['font-size'] = $themeData['styles']['typography']['fontSize'];
    }

    $styles = array_merge($styles, $this->fetchStylesFromBlockAttrs($availableStylesheets, $parsedBlock['attrs'] ?? []));

    return '
      <!--[if mso | IE]><table align="left" role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td><![endif]-->
        <div style="margin-top: ' . $marginTop . ';">
          <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width:100%;"width="100%">
            <tr>
              <td style="' . $settingsController->convertStylesToString($styles) . '" align="' . $align . '">
                {paragraph_content}
              </td>
            </tr>
          </table>
        </div>
      <!--[if mso | IE]></td></tr></table><![endif]-->
    ';
  }

  /**
   * @param string $availableStylesheets contains CSS styles generated from the core theme.json file
   */
  private function fetchStylesFromBlockAttrs(string $availableStylesheets, array $attrs = []): array {
    $styles = [];

    // Fetching matched color variables by slug stored in the block attrs to the color value.
    $supportedColorValues = ['backgroundColor', 'textColor'];
    foreach ($supportedColorValues as $supportedColorValue) {
      if (array_key_exists($supportedColorValue, $attrs)) {
        $colorSlug = $attrs[$supportedColorValue];

        // CSS variables are stored by current pattern `--wp--preset--{type}--{slug}` More info: https://developer.wordpress.org/themes/global-settings-and-styles/settings/color/#registering-custom-color-presets
        $colorRegex = "/--wp--preset--color--$colorSlug: (#[0-9a-fA-F]{6});/";

        // fetch color hex from available stylesheets
        preg_match($colorRegex, $availableStylesheets, $colorMatch);

        $colorValue = '';
        if ($colorMatch) {
          $colorValue = $colorMatch[1];
        }

        if ($supportedColorValue === 'textColor') {
          $styles['color'] = $colorValue; // use color instead of textColor. textColor not valid CSS property
        } else {
          $styles[Helpers::camelCaseToKebabCase($supportedColorValue)] = $colorValue;
        }

      }
    }

    // fetch Block Style Typography e.g., fontStyle, fontWeight, etc
    if (isset($attrs['style']['typography'])) {
      $blockStyleTypographyKeys = array_keys($attrs['style']['typography']);
      foreach ($blockStyleTypographyKeys as $blockStyleTypographyKey) {
        $styles[Helpers::camelCaseToKebabCase($blockStyleTypographyKey)] = $attrs['style']['typography'][$blockStyleTypographyKey];
      }
    }

    return $styles;
  }

  /**
   * @param array{tag_name: string, class_name?: string} $tag
   */
  private function removePaddingFromElement($blockContent, array $tag): string {
    $html = new \WP_HTML_Tag_Processor($blockContent);
    if ($html->next_tag($tag)) {
      $elementStyle = $html->get_attribute('style') ?? '';
      $elementStyle = preg_replace('/padding.*:.?[0-9]+px;?/', '', $elementStyle);
      $html->set_attribute('style', $elementStyle);
      $blockContent = $html->get_updated_html();
    }

    return $blockContent;
  }
}
