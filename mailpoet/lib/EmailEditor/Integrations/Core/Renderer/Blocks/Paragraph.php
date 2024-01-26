<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\Util\Helpers;

class Paragraph implements BlockRenderer {
  public function render(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $blockContent = $this->removePaddingFromElement($blockContent, ['tag_name' => 'p']);
    return str_replace('{paragraph_content}', $blockContent, $this->getBlockWrapper($blockContent, $parsedBlock, $settingsController));
  }

  /**
   * Based on MJML <mj-text>
   */
  private function getBlockWrapper(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $themeData = $settingsController->getTheme()->get_data();
    $classes = $this->getClassesFromElement($blockContent, ['tag_name' => 'p']);

    $align = $parsedBlock['attrs']['align'] ?? 'left';
    $marginTop = $parsedBlock['email_attrs']['margin-top'] ?? '0px';

    $styles = [
      'text-align' => $align,
      'padding-bottom' => $parsedBlock['attrs']['style']['spacing']['padding']['bottom'] ?? '0px',
      'padding-left' => $parsedBlock['attrs']['style']['spacing']['padding']['left'] ?? '0px',
      'padding-right' => $parsedBlock['attrs']['style']['spacing']['padding']['right'] ?? '0px',
      'padding-top' => $parsedBlock['attrs']['style']['spacing']['padding']['top'] ?? '0px',
    ];

    if (isset($parsedBlock['attrs']['style']['color']['text'])) {
      $styles['color'] = $parsedBlock['attrs']['style']['color']['text'];
    }

    if (isset($parsedBlock['attrs']['style']['color']['background'])) {
      $styles['background-color'] = $parsedBlock['attrs']['style']['color']['background'];
    }

    // fetch Block Style Typography e.g., fontStyle, fontWeight, etc
    $attrs = $parsedBlock['attrs'] ?? [];
    if (isset($attrs['style']['typography'])) {
      $blockStyleTypographyKeys = array_keys($attrs['style']['typography']);
      foreach ($blockStyleTypographyKeys as $blockStyleTypographyKey) {
        $styles[Helpers::camelCaseToKebabCase($blockStyleTypographyKey)] = $attrs['style']['typography'][$blockStyleTypographyKey];
      }
    }

    if (!isset($styles['font-size'])) {
      $styles['font-size'] = $themeData['styles']['typography']['fontSize'];
    }

    return '
      <!--[if mso | IE]><table align="left" role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td><![endif]-->
        <div style="margin-top: ' . $marginTop . ';">
          <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width:100%;"width="100%">
            <tr>
              <td class="' . esc_attr($classes) . '" style="' . $settingsController->convertStylesToString($styles) . '" align="' . $align . '">
                {paragraph_content}
              </td>
            </tr>
          </table>
        </div>
      <!--[if mso | IE]></td></tr></table><![endif]-->
    ';
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

  /**
   * @param array{tag_name: string, class_name?: string} $tag
   */
  private function getClassesFromElement($blockContent, array $tag): string {
    $html = new \WP_HTML_Tag_Processor($blockContent);
    $elementClass = '';
    if ($html->next_tag($tag)) {
      $elementClass = $html->get_attribute('class') ?? '';
    }
    return $elementClass;
  }
}
