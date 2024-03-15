<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Integrations\Utils\DomDocumentHelper;
use MailPoet\Util\Helpers;

class Heading implements BlockRenderer {
  public function render(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $level = $parsedBlock['attrs']['level'] ?? 2; // default level is 2
    $blockContent = $this->adjustStyleAttribute($blockContent, $parsedBlock, $settingsController, ['tag_name' => "h$level"]);

    return str_replace('{heading_content}', $blockContent, $this->getBlockWrapper($blockContent, $parsedBlock, $settingsController));
  }

  /**
   * Based on MJML <mj-text>
   */
  private function getBlockWrapper($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $marginTop = $parsedBlock['email_attrs']['margin-top'] ?? '0px';
    $level = $parsedBlock['attrs']['level'] ?? 2; // default level is 2
    $classes = (new DomDocumentHelper($blockContent))->getAttributeValueByTagName("h$level", 'class') ?? '';

    // Styles for padding need to be set on the wrapping table cell due to support in Outlook
    $styles = [
      'min-width' => '100%', // prevent Gmail App from shrinking the table on mobile devices
    ];

    $paddingStyles = wp_style_engine_get_styles(['spacing' => ['padding' => $parsedBlock['attrs']['style']['spacing']['padding'] ?? null ]]);
    $styles = array_merge($styles, $paddingStyles['declarations'] ?? []);

    if (isset($parsedBlock['attrs']['textAlign'])) {
      $styles['text-align'] = $parsedBlock['attrs']['textAlign'];
    }

    if (isset($parsedBlock['attrs']['style']['color']['background'])) {
      $styles['background-color'] = $parsedBlock['attrs']['style']['color']['background'];
    }

    if (isset($parsedBlock['attrs']['style']['color']['text'])) {
      $styles['color'] = $parsedBlock['attrs']['style']['color']['text'];
    }

    // fetch Block Style Typography e.g., fontStyle, fontWeight, etc
    $attrs = $parsedBlock['attrs'] ?? [];
    if (isset($attrs['style']['typography'])) {
      $blockStyleTypographyKeys = array_keys($attrs['style']['typography']);
      foreach ($blockStyleTypographyKeys as $blockStyleTypographyKey) {
        $styles[Helpers::camelCaseToKebabCase($blockStyleTypographyKey)] = $attrs['style']['typography'][$blockStyleTypographyKey];
      }
    }

    return '
      <!--[if mso | IE]><table align="left" role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td><![endif]-->
        <div style="margin-top: ' . $marginTop . ';">
          <table
            role="presentation"
            border="0"
            cellpadding="0"
            cellspacing="0"
            style="min-width: 100%;"
            width="100%"
          >
            <tr>
              <td class="' . esc_attr($classes) . '" style="' . $settingsController->convertStylesToString($styles) . '">
                {heading_content}
              </td>
            </tr>
          </table>
        </div>
      <!--[if mso | IE]></td></tr></table><![endif]-->
    ';
  }

  /**
   * 1) We need to remove padding because we render padding on wrapping table cell
   * 2) We also need to replace font-size to avoid clamp() because clamp() is not supported in many email clients.
   * The font size values is automatically converted to clamp() when WP site theme is configured to use fluid layouts.
   * Currently (WP 6.4), there is no way to disable this behavior.
   * @param array{tag_name: string, class_name?: string} $tag
   */
  private function adjustStyleAttribute($blockContent, array $parsedBlock, SettingsController $settingsController, array $tag): string {
    $html = new \WP_HTML_Tag_Processor($blockContent);
    $themeData = $settingsController->getTheme()->get_data();
    $fontSize = 'font-size:' . ($parsedBlock['email_attrs']['font-size'] ?? $themeData['styles']['typography']['fontSize']) . ';';

    if ($html->next_tag($tag)) {
      $elementStyle = $html->get_attribute('style') ?? '';
      // Padding may contain value like 10px or variable like var(--spacing-10)
      $elementStyle = preg_replace('/padding.*:.?[0-9a-z-()]+;?/', '', $elementStyle);
      $elementStyle = preg_replace('/font-size:[^;]+;?/', $fontSize, $elementStyle);
      $html->set_attribute('style', $elementStyle);
      $blockContent = $html->get_updated_html();
    }

    return $blockContent;
  }
}
