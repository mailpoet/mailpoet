<?php
namespace MailPoet\Newsletter\Renderer\Columns;

class Renderer {
  function render($column_styles, $columns_count, $columns_data) {
    $styles = $column_styles['block'];
    $width = ColumnsHelper::columnWidth($columns_count);
    $class = ColumnsHelper::columnClass($columns_count);
    $alignment = ColumnsHelper::columnAlignment($columns_count);
    $template = ($columns_count === 1) ?
      $this->getOneColumnTemplate($styles, $class) :
      $this->getMultipleColumnsTemplate($styles, $width, $alignment, $class);
    $result = array_map(function($content) use ($template) {
      $content = Renderer::removePaddingFromLastElement($content);
      return $template['content_start'] . $content . $template['content_end'];
    }, $columns_data);
    $result = implode('', $result);
    if($columns_count !== 1) {
      $result = $template['container_start'] . $result . $template['container_end'];
    }
    return $result;
  }

  function getOneColumnTemplate($styles, $class) {
    $background_color = $this->getBackgroundColor($styles);
    $template['content_start'] = '
      <tr>
        <td class="mailpoet_content" align="center" style="border-collapse:collapse">
          <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
            <tbody>
              <tr>
                <td style="padding-left:0;padding-right:0">
                  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="mailpoet_' . $class . '" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;table-layout:fixed;margin-left:auto;margin-right:auto;padding-left:0;padding-right:0;' . $background_color . '">
                    <tbody>';
    $template['content_end'] = '
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
    $background_color = $this->getBackgroundColor($styles);
    $template['container_start'] = '
      <tr>
        <td class="mailpoet_content-' . $class . '" align="left" style="border-collapse:collapse;' . $background_color . '">
          <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
            <tbody>
              <tr>
                <td align="center" style="font-size:0;"><!--[if mso]>
                  <table border="0" width="100%" cellpadding="0" cellspacing="0">
                    <tbody>
                      <tr>';
    $template['content_start'] = '
      <td width="' . $width . '" valign="top">
        <![endif]--><div style="display:inline-block; max-width:' . $width . 'px; vertical-align:top; width:100%;">
          <table width="' . $width . '" class="mailpoet_' . $class . '" border="0" cellpadding="0" cellspacing="0" align="' . $alignment . '" style="width:100%;max-width:' . $width . 'px;border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;table-layout:fixed;margin-left:auto;margin-right:auto;padding-left:0;padding-right:0;">
            <tbody>';
    $template['content_end'] = '
            </tbody>
          </table>
        </div><!--[if mso]>
      </td>';
    $template['container_end'] = '
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

  function removePaddingFromLastElement($element) {
    return preg_replace('/mailpoet_padded_bottom(?!.*mailpoet_padded_bottom)/ism', '', $element);
  }

  function getBackgroundColor($styles) {
    if(!isset($styles['backgroundColor'])) return false;
    $background_color = $styles['backgroundColor'];
    return ($background_color !== 'transparent') ?
      sprintf('background-color:%s!important;" bgcolor="%s', $background_color, $background_color) :
      false;
  }
}