<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\Renderer\BlocksRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class Columns implements BlockRenderer {
  public function render($parsedBlock, BlocksRenderer $blocksRenderer, SettingsController $settingsController): string {
    $content = '';
    if (isset($parsedBlock['innerBlocks']) && !empty($parsedBlock['innerBlocks'])) {
      $content = $blocksRenderer->render($parsedBlock['innerBlocks']);
    }
    return str_replace(
      '{columns_content}',
      $content,
      $this->prepareColumnsTemplate($parsedBlock)
    );
  }

  /**
   * Based on MJML <mj-section>
   */
  private function prepareColumnsTemplate(array $parsedBlock): string {
    $width = $parsedBlock['email_attrs']['width'] ?? '640px';
    $backgroundColor = $parsedBlock['attrs']['style']['color']['background'] ?? 'none';
    $paddingBottom = $parsedBlock['attrs']['style']['spacing']['padding']['bottom'] ?? '0px';
    $paddingLeft = $parsedBlock['attrs']['style']['spacing']['padding']['left'] ?? '0px';
    $paddingRight = $parsedBlock['attrs']['style']['spacing']['padding']['right'] ?? '0px';
    $paddingTop = $parsedBlock['attrs']['style']['spacing']['padding']['top'] ?? '0px';

    return '
      <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="width:' . $width . ';" width="' . $width . '" bgcolor="' . $backgroundColor . '" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
      <div style="background:' . $backgroundColor . ';background-color:' . $backgroundColor . ';margin:0px auto;max-width:' . $width . ';width:100%;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:' . $backgroundColor . ';background-color:' . $backgroundColor . ';max-width:' . $width . ';width:100%;">
          <tbody>
            <tr>
              <td style="font-size:0px;padding-left:' . $paddingLeft . ';padding-right:' . $paddingRight . ';padding-bottom:' . $paddingBottom . ';padding-top:' . $paddingTop . ';text-align:left;">
                <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><![endif]-->
                {columns_content}
                <!--[if mso | IE]></tr></table><![endif]-->
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!--[if mso | IE]></td></tr></table><![endif]-->
    ';
  }
}
