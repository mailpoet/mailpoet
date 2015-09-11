<?php namespace MailPoet\Newsletter\Columns;

class Renderer {

  public $columnWidths = array(
    1 => 600,
    2 => 300,
    3 => 200
  );

  public $columnClasses = array(
    1 => 'mailpoet_col-one',
    2 => 'mailpoet_col-two',
    3 => 'mailpoet_col-three'
  );

  function render($columnsCount, $columnsData) {

    $columnWidth = $this->columnWidths[$columnsCount];
    $columnClass = $this->columnClasses[$columnsCount];

    // open column container
    $columnContainerTemplate = '
<tr>
<td class="mailpoet_content" align="left" style="border-collapse: collapse;">
  <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
    <tbody>
      <tr>
        <td class="mailpoet_cols-wrapper" style="border-collapse: collapse; padding-left: 0px; padding-right: 0px;">
        <!--[if mso]>
        <table border="0" width="100%" cellpadding="0" cellspacing="0">
        	<tbody>
              <tr>
                <td width="' . $columnWidth . '" style="width: ' . $columnWidth . 'px;" valign="top">
        <![endif]-->';

    $columnOpenTemplate = '
<table width="' . $columnWidth . '"
       border="0" cellpadding="0" cellspacing="0" align="left" class="mailpoet_force-row ' . $columnClass . ' mailpoet_col"
       style="width: ' . $columnWidth . 'px; border-spacing: 0; mso-table-lspace: 0pt; mso-table-rspace: 0pt;
              table-layout: fixed; margin-left: auto; margin-right: auto;" bgcolor="#999999">
  <tbody>';

    $columnCloseTemplate = '
  </tbody>
</table>
<!--[if mso]>
  </td>
  <td width="' . $columnWidth . '" style="width: ' . $columnWidth . 'px;" valign="top">
<![endif]-->';

    foreach ($columnsData as $index => $columnData) {
      $index++;
      $columnContainerTemplate .= $columnOpenTemplate . $columnData;
      if($columnsCount > 1 && $index != $columnsCount) {
        $columnContainerTemplate .= $columnCloseTemplate;
      }
    }

    // close column container
    $columnContainerTemplate .= '
              </tbody>
            </table>
        <!--[if mso]>
              </td>
            </tr>
          </tbody>
        </table>
        <![endif]-->
        </td>
      </tr>
    </table>
  </td>
</tr>';

    return $columnContainerTemplate;
  }
}
