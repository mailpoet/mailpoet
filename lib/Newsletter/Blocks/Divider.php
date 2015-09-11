<?php namespace MailPoet\Newsletter\Blocks;

class Divider {

  static function render($element) {
    $template = '
<tr>
  <td class="mailpoet_col mailpoet_divider mailpoet_padded" style="background-color: ' . $element['styles']['block']['backgroundColor'] . '; padding: ' . $element['styles']['block']['padding'] . ' 0;" valign="top">
    <table width="100%">
	  <tr>
	    <td style="border-top-width: ' . $element['styles']['block']['borderWidth'] . ';
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