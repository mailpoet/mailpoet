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
    // Getting individual border properties
    $borderColor = $parsedBlock['attrs']['style']['border']['color'] ?? '#000000';
    $borderWidth = $parsedBlock['attrs']['style']['border']['width'] ?? '0px';
    $borderRadius = $parsedBlock['attrs']['style']['border']['radius'] ?? '0px';
    // Because borders can by configured individually, we need to get each one of them and use the main border properties as fallback
    $borderBottomColor = $parsedBlock['attrs']['style']['border']['bottom']['color'] ?? $borderColor;
    $borderLeftColor = $parsedBlock['attrs']['style']['border']['left']['color'] ?? $borderColor;
    $borderRightColor = $parsedBlock['attrs']['style']['border']['right']['color'] ?? $borderColor;
    $borderTopColor = $parsedBlock['attrs']['style']['border']['top']['color'] ?? $borderColor;
    $borderBottomWidth = $parsedBlock['attrs']['style']['border']['bottom']['width'] ?? $borderWidth;
    $borderLeftWidth = $parsedBlock['attrs']['style']['border']['left']['width'] ?? $borderWidth;
    $borderRightWidth = $parsedBlock['attrs']['style']['border']['right']['width'] ?? $borderWidth;
    $borderTopWidth = $parsedBlock['attrs']['style']['border']['top']['width'] ?? $borderWidth;
    $borderBottomLeftRadius = $parsedBlock['attrs']['style']['border']['radius']['bottomLeft'] ?? $borderRadius;
    $borderBottomRightRadius = $parsedBlock['attrs']['style']['border']['radius']['bottomRight'] ?? $borderRadius;
    $borderTopLeftRadius = $parsedBlock['attrs']['style']['border']['radius']['topLeft'] ?? $borderRadius;
    $borderTopRightRadius = $parsedBlock['attrs']['style']['border']['radius']['topRight'] ?? $borderRadius;

    $width = $parsedBlock['email_attrs']['width'] ?? $settingsController->getLayoutWidthWithoutPadding();
    // Because width is primarily used for the max-width property, we need to add the left and right border width to it
    $width = $settingsController->parseNumberFromStringWithPixels($width);
    $width += $settingsController->parseNumberFromStringWithPixels($borderLeftWidth ?? '0px');
    $width += $settingsController->parseNumberFromStringWithPixels($borderRightWidth ?? '0px');
    $width = "{$width}px";
    $backgroundColor = $parsedBlock['attrs']['style']['color']['background'] ?? 'none';
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
      $layoutPaddingLeft = $settingsController->getEmailLayoutStyles()['padding']['left'];
      $layoutPaddingRight = $settingsController->getEmailLayoutStyles()['padding']['right'];
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
              <td style="
                font-size:0px;
                background:' . $backgroundColor . ';
                background-color:' . $backgroundColor . ';
                border-bottom:' . $borderBottomWidth . ' solid ' . $borderBottomColor . ';
                border-left:' . $borderLeftWidth . ' solid ' . $borderLeftColor . ';
                border-top:' . $borderTopWidth . ' solid ' . $borderTopColor . ';
                border-right:' . $borderRightWidth . ' solid ' . $borderRightColor . ';
                border-radius:' . $borderTopLeftRadius . ' ' . $borderTopRightRadius . ' ' . $borderBottomRightRadius . ' ' . $borderBottomLeftRadius . ';
                padding-left:' . $paddingLeft . ';
                padding-right:' . $paddingRight . ';
                padding-bottom:' . $paddingBottom . ';
                padding-top:' . $paddingTop . ';
                text-align:left;
              ">
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
