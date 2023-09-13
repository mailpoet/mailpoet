<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\Renderer\BlocksRenderer;

class Columns implements BlockRenderer {
  public function render($parsedBlock, BlocksRenderer $blocksRenderer): string {
    if (!isset($parsedBlock['innerBlocks']) || empty($parsedBlock['innerBlocks'])) {
      return '';
    }
    return str_replace('{columns_content}', $this->renderInnerColumns($parsedBlock['innerBlocks'], $blocksRenderer), $this->getColumnsContainerTemplate());
  }

  private function renderInnerColumns($columnBlocks, BlocksRenderer $blocksRenderer): string {
    // Dummy width just by number of columns and 16px padding
    $width = floor((660 - 16) / count($columnBlocks));
    $result = '';
    foreach ($columnBlocks as $columnBlock) {
      $result .= str_replace('{column_content}', $blocksRenderer->render([$columnBlock]), $this->getColumnTemplate($width, 'left'));
    }
    return $result;
  }

  /**
   * Based on MJML <mj-section>
   */
  private function getColumnsContainerTemplate(): string {
    return '<tr>
            <td style="direction:ltr;font-size:0px;padding:0px 0;text-align:center;">
            <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><![endif]-->
              {columns_content}
            <!--[if mso | IE]></tr></table><![endif]-->
            </td>
          </tr>';
  }

  /**
   * Based on MJML <mj-column>
   */
  private function getColumnTemplate($width, $alignment): string {
    return '
     <!--[if mso | IE]><td class="" style="vertical-align:top;width:' . $width . 'px;" ><![endif]-->
      <div class="email_column" style="font-size:0px;text-align:' . $alignment . ';direction:ltr;display:inline-block;vertical-align:top;width:' . $width . 'px;max-width:' . $width . 'px">
        <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="' . $width . '">
          <tbody>
            {column_content}
          </tbody>
        </table>
      </div>
       <!--[if mso | IE]></td><![endif]-->';
  }
}
