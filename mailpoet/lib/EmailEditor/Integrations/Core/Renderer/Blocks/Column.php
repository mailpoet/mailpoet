<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;

class Column implements BlockRenderer {
  public function render($blockContent, array $parsedBlock): string {
    $content = '';
    foreach ($parsedBlock['innerBlocks'] ?? [] as $block) {
      $content .= render_block($block);
    }

    return str_replace(
      '{column_content}',
      $content,
      $this->prepareColumnTemplate($parsedBlock)
    );
  }

  /**
   * Based on MJML <mj-column>
   */
  private function prepareColumnTemplate(array $parsedBlock): string {
    $width = $parsedBlock['email_attrs']['width'] ?? '640px';
    $backgroundColor = $parsedBlock['attrs']['style']['color']['background'] ?? 'none';
    $paddingBottom = $parsedBlock['attrs']['style']['spacing']['padding']['bottom'] ?? '0px';
    $paddingLeft = $parsedBlock['attrs']['style']['spacing']['padding']['left'] ?? '0px';
    $paddingRight = $parsedBlock['attrs']['style']['spacing']['padding']['right'] ?? '0px';
    $paddingTop = $parsedBlock['attrs']['style']['spacing']['padding']['top'] ?? '0px';

    return '
      <!--[if mso | IE]><td class="" style="vertical-align:top;width:' . $width . ';"><![endif]-->
      <div class="email_column" style="background:' . $backgroundColor . ';background-color:' . $backgroundColor . ';width:100%;max-width:' . $width . ';font-size:0px;text-align:left;display:inline-block;vertical-align:top;">
        <table class="email_column" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:' . $backgroundColor . ';background-color:' . $backgroundColor . ';width:100%;max-width:' . $width . ';vertical-align:top;" width="' . $width . '">
          <tbody>
            <tr>
              <td align="left" style="font-size:0px;padding-left:' . $paddingLeft . ';padding-right:' . $paddingRight . ';padding-bottom:' . $paddingBottom . ';padding-top:' . $paddingTop . ';">
                <div style="line-height:1;text-align:left;">{column_content}</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!--[if mso | IE]></td><![endif]-->
    ';
  }
}
