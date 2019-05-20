<?php
namespace MailPoet\Newsletter\Renderer\Columns;

use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;

class Renderer {

  function render($content_block, $columns_data) {

    $columns_count = count($content_block['blocks']);

    if ($columns_count === 1) {
      return $this->renderOneColumn($content_block, $columns_data[0]);
    }
    return $this->renderMultipleColumns($content_block, $columns_data);
  }

  private function renderOneColumn($content_block, $content) {
    $template = $this->getOneColumnTemplate(
      $content_block['styles']['block'],
      isset($content_block['image']) ? $content_block['image'] : null
    );
    return $template['content_start'] . $content . $template['content_end'];
  }

  function getOneColumnTemplate($styles, $image) {
    $background_css = $this->getBackgroundCss($styles, $image);
    $template['content_start'] = '
      <tr>
        <td class="mailpoet_content" align="center" style="border-collapse:collapse;' . $background_css . '" ' . $this->getBgColorAttribute($styles, $image) . '>
          <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
            <tbody>
              <tr>
                <td style="padding-left:0;padding-right:0">
                  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="mailpoet_' . ColumnsHelper::columnClass(1) . '" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;table-layout:fixed;margin-left:auto;margin-right:auto;padding-left:0;padding-right:0;">
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

  private function renderMultipleColumns($content_block, $columns_data) {
    $columns_count = count($content_block['blocks']);
    $columns_layout = isset($content_block['columnLayout']) ? $content_block['columnLayout'] : null;

    $widths = ColumnsHelper::columnWidth($columns_count, $columns_layout);
    $class = ColumnsHelper::columnClass($columns_count);
    $alignment = ColumnsHelper::columnAlignment($columns_count);
    $index = 0;
    $result = $this->getMultipleColumnsContainerStart($class, $content_block['styles']['block'], isset($content_block['image']) ? $content_block['image'] : null);
    foreach ($columns_data as $content) {
      $result .= $this->getMultipleColumnsContentStart($widths[$index++], $alignment, $class);
      $result .= $content;
      $result .= $this->getMultipleColumnsContentEnd();
    }
    $result .= $this->getMultipleColumnsContainerEnd();
    return $result;
  }

  private function getMultipleColumnsContainerStart($class, $styles, $image) {
    return '
      <tr>
        <td class="mailpoet_content-' . $class . '" align="left" style="border-collapse:collapse;' . $this->getBackgroundCss($styles, $image) . '" ' . $this->getBgColorAttribute($styles, $image) . '>
          <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
            <tbody>
              <tr>
                <td align="center" style="font-size:0;"><!--[if mso]>
                  <table border="0" width="100%" cellpadding="0" cellspacing="0">
                    <tbody>
                      <tr>';
  }

  private function getMultipleColumnsContainerEnd() {
    return '
                  </tr>
                </tbody>
              </table>
            <![endif]--></td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>';
  }

  private function getMultipleColumnsContentEnd() {
    return '
            </tbody>
          </table>
        </div><!--[if mso]>
      </td>';
  }

  function getMultipleColumnsContentStart($width, $alignment, $class) {
    return '
      <td width="' . $width . '" valign="top">
        <![endif]--><div style="display:inline-block; max-width:' . $width . 'px; vertical-align:top; width:100%;">
          <table width="' . $width . '" class="mailpoet_' . $class . '" border="0" cellpadding="0" cellspacing="0" align="' . $alignment . '" style="width:100%;max-width:' . $width . 'px;border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;table-layout:fixed;margin-left:auto;margin-right:auto;padding-left:0;padding-right:0;">
            <tbody>';
  }

  private function getBackgroundCss($styles, $image) {
    if ($image !== null && $image['src'] !== null) {
      $background_color = isset($styles['backgroundColor']) && $styles['backgroundColor'] !== 'transparent' ? $styles['backgroundColor'] : '#ffffff';
      $repeat = $image['display'] === 'tile' ? 'repeat' : 'no-repeat';
      $size = $image['display'] === 'scale' ? 'cover' : 'contain';
      $style = sprintf(
        'background: %s url(%s) %s center/%s;background-color: %s;background-image: url(%s);background-repeat: %s;background-position: center;background-size: %s;',
        $background_color, $image['src'], $repeat, $size, $background_color, $image['src'], $repeat, $size
      );
      return EHelper::escapeHtmlStyleAttr($style);
    } else {
      if (!isset($styles['backgroundColor'])) return false;
      $background_color = $styles['backgroundColor'];
      return ($background_color !== 'transparent') ?
        EHelper::escapeHtmlStyleAttr(sprintf('background-color:%s!important;', $background_color)) :
        false;
    }
  }

  private function getBgColorAttribute($styles, $image) {
    if (($image === null || $image['src'] === null)
      && isset($styles['backgroundColor'])
      && $styles['backgroundColor'] !== 'transparent'
    ) {
      return 'bgcolor="' . EHelper::escapeHtmlAttr($styles['backgroundColor']) . '"';
    }
    return null;
  }
}
