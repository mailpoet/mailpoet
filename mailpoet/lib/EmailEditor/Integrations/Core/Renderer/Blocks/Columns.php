<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Integrations\Utils\DomDocumentHelper;

class Columns implements BlockRenderer {
  public function render(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $content = '';
    foreach ($parsedBlock['innerBlocks'] ?? [] as $block) {
      $content .= render_block($block);
    }

    return str_replace(
      '{columns_content}',
      $content,
      $this->getBlockWrapper($blockContent, $parsedBlock, $settingsController)
    );
  }

  /**
   * Based on MJML <mj-section>
   */
  private function getBlockWrapper(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $width = $parsedBlock['email_attrs']['width'] ?? $settingsController->getLayoutWidthWithoutPadding();
    $paddingBottom = $parsedBlock['attrs']['style']['spacing']['padding']['bottom'] ?? '0px';
    $paddingLeft = $parsedBlock['attrs']['style']['spacing']['padding']['left'] ?? '0px';
    $paddingRight = $parsedBlock['attrs']['style']['spacing']['padding']['right'] ?? '0px';
    $paddingTop = $parsedBlock['attrs']['style']['spacing']['padding']['top'] ?? '0px';
    $marginTop = $parsedBlock['email_attrs']['margin-top'] ?? '0px';

    $classes = (new DomDocumentHelper($blockContent))->getAttributeValueByTagName('div', 'class') ?? '';
    $colorStyles = [];
    if (isset($parsedBlock['attrs']['style']['color']['background'])) {
      $colorStyles['background-color'] = $parsedBlock['attrs']['style']['color']['background'];
      $colorStyles['background'] = $parsedBlock['attrs']['style']['color']['background'];
    }
    if (isset($parsedBlock['attrs']['style']['color']['text'])) {
      $colorStyles['color'] = $parsedBlock['attrs']['style']['color']['text'];
    }

    $align = $parsedBlock['attrs']['align'] ?? null;
    if ($align !== 'full') {
      $layoutPaddingLeft = $settingsController->getEmailStyles()['layout']['padding']['left'];
      $layoutPaddingRight = $settingsController->getEmailStyles()['layout']['padding']['right'];
    } else {
      $layoutPaddingLeft = '0px';
      $layoutPaddingRight = '0px';
    }

    return '
      <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="width:' . $width . ';" width="' . $width . '"><tr><td style="font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
      <div style="margin-top:' . $marginTop . ';max-width:' . $width . ';padding-left:' . $layoutPaddingLeft . ';padding-right:' . $layoutPaddingRight . ';">
        <table
          class="' . $classes . '"
          align="center"
          border="0"
          cellpadding="0"
          cellspacing="0"
          role="presentation"
          style="' . esc_attr($settingsController->convertStylesToString($colorStyles)) . ';max-width:' . $width . ';width:100%;"
        >
          <tbody>
            <tr>
              <td style="font-size:0px;padding-left:' . $paddingLeft . ';padding-right:' . $paddingRight . ';padding-bottom:' . $paddingBottom . ';padding-top:' . $paddingTop . ';text-align:left;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width:100%;">
                  <tr>
                    {columns_content}
                  </tr>
                </table>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!--[if mso | IE]></td></tr></table><![endif]-->
    ';
  }
}
