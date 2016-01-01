<?php
namespace MailPoet\Newsletter\Renderer\Columns;

class Renderer {
  function render($columnStyles, $columnsCount, $columnsData) {
    $styles = $columnStyles['block'];

    $width = ColumnsHelper::$columnsWidth[$columnsCount];
    $class = ColumnsHelper::$columnsClass[$columnsCount];
    $alignment = ColumnsHelper::$columnsAlignment[$columnsCount];
    $template = ($columnsCount === 1) ?
      $this->getOneColumnTemplate($styles, $class) :
      $this->getMultipleColumnsTemplate($styles, $width, $alignment, $class);
    $result = array_map(function ($content) use ($template) {
      return $template['contentStart'] . $content . $template['contentEnd'];
    }, $columnsData);
    $result = implode('', $result);
    if ($columnsCount !== 1) {
      $result = $template['containerStart'] . $result . $template['containerEnd'];
    }
    return $result;
  }
  
  function getOneColumnTemplate($styles, $class) {
    $template['contentStart'] = '
      <tr>
        <td class="mailpoet_content" align="center" style="border-collapse:collapse">
          <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
            <tbody>
              <tr>
                <td style="padding-left:0;padding-right:0">
                  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="mailpoet_' . $class . '" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;table-layout:fixed;margin-left:auto;margin-right:auto;padding-left:0;padding-right:0;background-color:' . $styles['backgroundColor'] . '!important;" bgcolor="' . $styles['backgroundColor'] . '">
                    <tbody>';
    $template['contentEnd'] = '
                    </tbody>
                  </table>
                </td>
              </tr>
            </tbody>
          </table>
        </td>
      </tr>';
    return $template;
  }

  function getMultipleColumnsTemplate($styles, $width, $alignment, $class) {
    $template['containerStart'] = '
      <tr>
        <td class="mailpoet_content-' . $class . '" align="left" style="border-collapse:collapse;background-color:' . $styles['backgroundColor'] . '!important;" bgcolor="' . $styles['backgroundColor'] . '">
          <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
            <tbody>
              <tr>
                <td align="center" style="font-size:0;"><!--[if mso]>
                  <table border="0" width="100%" cellpadding="0" cellspacing="0">
                    <tbody>
                      <tr>';
    $template['contentStart'] = '
      <td width="' . $width . '" valign="top">
        <![endif]--><div style="display:inline-block; max-width:' . $width . 'px; vertical-align:top; width:100%;">
          <table width="' . $width . '" class="mailpoet_' . $class . '" border="0" cellpadding="0" cellspacing="0" align="' . $alignment . '" style="width:100%;max-width:' . $width . 'px;border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;table-layout:fixed;margin-left:auto;margin-right:auto;padding-left:0;padding-right:0;">
            <tbody>';
    $template['contentEnd'] = '
            </tbody>
          </table>
        </div><!--[if mso]>
      </td>';
    $template['containerEnd'] = '
                  </tr>
                </tbody>
              </table>
            <![endif]--></td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>';
    return $template;
  }
}