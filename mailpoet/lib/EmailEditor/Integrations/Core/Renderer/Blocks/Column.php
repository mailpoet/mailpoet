<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class Column implements BlockRenderer {
  public function render($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $content = '';
    foreach ($parsedBlock['innerBlocks'] ?? [] as $block) {
      $content .= render_block($block);
    }

    return str_replace(
      '{column_content}',
      $content,
      $this->getBlockWrapper($parsedBlock, $settingsController)
    );
  }

  /**
   * Based on MJML <mj-column>
   */
  private function getBlockWrapper(array $parsedBlock, SettingsController $settingsController): string {
    $width = $parsedBlock['email_attrs']['width'] ?? $settingsController->getLayoutWidthWithoutPadding();
    $backgroundColor = $parsedBlock['attrs']['style']['color']['background'] ?? 'none';
    $paddingBottom = $parsedBlock['attrs']['style']['spacing']['padding']['bottom'] ?? '0px';
    $paddingLeft = $parsedBlock['attrs']['style']['spacing']['padding']['left'] ?? '0px';
    $paddingRight = $parsedBlock['attrs']['style']['spacing']['padding']['right'] ?? '0px';
    $paddingTop = $parsedBlock['attrs']['style']['spacing']['padding']['top'] ?? '0px';

    $verticalAlign = 'top';
    // Because `stretch` is not a valid value for the `vertical-align` property, we don't override the default value
    if (isset($parsedBlock['attrs']['verticalAlignment']) && $parsedBlock['attrs']['verticalAlignment'] !== 'stretch') {
      $verticalAlign = $parsedBlock['attrs']['verticalAlignment'];
    }

    $mainCellStyles = [
      'width' => $width,
      'vertical-align' => $verticalAlign,
    ];
    // The default column alignment is `stretch to fill` which means that we need to set the background color to the main cell
    // to create a feeling of a stretched column
    if (!isset($parsedBlock['attrs']['verticalAlignment']) || $parsedBlock['attrs']['verticalAlignment'] === 'stretch') {
      $mainCellStyles['background-color'] = $backgroundColor;
    }

    return '
      <td class="block" style="' . $settingsController->convertStylesToString($mainCellStyles) . '">
        <div class="email_column" style="background:' . $backgroundColor . ';background-color:' . $backgroundColor . ';width:100%;max-width:' . $width . ';font-size:0px;text-align:left;display:inline-block;">
          <table class="email_column" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:' . $backgroundColor . ';background-color:' . $backgroundColor . ';min-width:100%;width:100%;max-width:' . $width . ';vertical-align:top;" width="' . $width . '">
            <tbody>
              <tr>
                <td align="left" style="font-size:0px;padding-left:' . $paddingLeft . ';padding-right:' . $paddingRight . ';padding-bottom:' . $paddingBottom . ';padding-top:' . $paddingTop . ';">
                  <div style="line-height:1;text-align:left;">{column_content}</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </td>
    ';
  }
}
