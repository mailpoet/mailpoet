<?php namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;

class Button {
  static function render($element) {
    $stylesHelper = new StylesHelper();

    $template = '
    <tr>
      <td class="mailpoet_col mailpoet_button mailpoet_padded" valign = "top" >
        <div>
          <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
              <td align="' . $element['styles']['block']['textAlign'] . '">
                <!--[if mso]>
                <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml"
                	xmlns:w="urn:schemas-microsoft-com:office:word"
                	href="' . $element['url'] . '"
                	style="height:' . $element['styles']['block']['lineHeight'] . ';
                	       width:' . $element['styles']['block']['width'] . ';
                	       v-text-anchor:middle;"
                	       arcsize="' . round($element['styles']['block']['borderRadius'] / $element['styles']['block']['lineHeight'] * 100) . '%"
                	       strokecolor="' . $element['styles']['block']['borderColor'] . '"
                	       fillcolor="' . $element['styles']['block']['backgroundColor'] . '">
                  <w:anchorlock/>
                  <center style="color:' . $element['styles']['block']['fontColor'] . ';
                                 font-family:' . $element['styles']['block']['fontFamily'] . ';
                                 font-size:' . $element['styles']['block']['fontSize'] . ';
                                 font-weight:bold;">' . $element['text'] . '
                  </center>
                  </v:roundrect>
                <![endif]-->
                <a class="mailpoet_button"
                   href="' . $element['url'] . '"
                   style="display:inline-block;text-align:center;text-decoration:none;-webkit-text-size-adjust:none;mso-hide:all;' . $stylesHelper->getBlockStyles($element, array('textAlign')) . '"> ' . $element['text'] . '
                </a>
              </td>
            </tr>
          </table>
        </div>
      </td>
    </tr>';

    return $template;
  }
}