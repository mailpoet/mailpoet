<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;

class Divider {
  static function render($element) {
    $template = '
      <tr>
        <td class="mailpoet_divider" valign="top" bgColor="' . $element['styles']['block']['backgroundColor'] . '"
        style="background-color:' . $element['styles']['block']['backgroundColor'] . ';' .
        sprintf('padding: %s %spx %s %spx;',
                $element['styles']['block']['padding'],
                StylesHelper::$paddingWidth,
                $element['styles']['block']['padding'],
                StylesHelper::$paddingWidth) . '">
          <table width="100%" border="0" cellpadding="0" cellspacing="0"
          style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;">
            <tr>
              <td class="mailpoet_divider-cell"
              style="border-top-width: ' . $element['styles']['block']['borderWidth'] . ';
       	             border-top-style: ' . $element['styles']['block']['borderStyle'] . ';
                     border-top-color: ' . $element['styles']['block']['borderColor'] . ';">
    	      </td>
            </tr>
          </table>
        </td>
      </tr>';
    return $template;
  }
}