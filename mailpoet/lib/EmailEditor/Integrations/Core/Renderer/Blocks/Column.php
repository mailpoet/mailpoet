<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Integrations\Utils\DomDocumentHelper;

class Column implements BlockRenderer {
  public function render(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $content = '';
    foreach ($parsedBlock['innerBlocks'] ?? [] as $block) {
      $content .= render_block($block);
    }

    return str_replace(
      '{column_content}',
      $content,
      $this->getBlockWrapper($blockContent, $parsedBlock, $settingsController)
    );
  }

  private function getStylesFromBlock(array $block_styles) {
    $styles = wp_style_engine_get_styles( $block_styles );
    return (object)wp_parse_args($styles['declarations'], [
      'css' => '',
      'declarations' => [],
      'classnames' => '',
    ]);
  }

  /**
   * Based on MJML <mj-column>
   */
  private function getBlockWrapper(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $block_attributes = isset( $parsedBlock['attrs'] ) && is_array( $parsedBlock['attrs'] ) ? $parsedBlock['attrs'] : [];
    $block_styles = isset( $block_attributes['style'] ) && is_array( $block_attributes['style'] ) ? $block_attributes['style'] : [];
    $classes = (new DomDocumentHelper($blockContent))->getAttributeValueByTagName('div', 'class') ?? '';

    $width = $parsedBlock['email_attrs']['width'] ?? $settingsController->getLayoutWidthWithoutPadding();
    $paddingStyles = $this->getStylesFromBlock( [ 'spacing' => ['padding' => $block_styles['spacing']['padding'] ?? [] ] ] )->css;
    $colorStyles = $this->getStylesFromBlock( [ 'color' => $block_styles['color'] ?? [] ] )->declarations;
    $backgroundStyles = $this->getStylesFromBlock( [ 'background' => $block_styles['background'] ?? [] ] )->declarations;
    $borderStyles = $this->getStylesFromBlock( [ 'border' => $block_styles['border'] ?? [] ] )->declarations;

    if (!empty($backgroundStyles['background-image']) && empty($backgroundStyles['background-size'])) {
      $backgroundStyles['background-size'] = 'cover';
    }

    if (!empty($borderStyles['border-width'])) {
      $borderStyles['border-style'] = 'solid';
      $borderStyles['box-sizing'] = 'border-box';
    }

    $mainCellStyles = [
      'vertical-align' => !empty( $block_attributes['verticalAlignment'] ) && $block_attributes['verticalAlignment'] !== 'stretch' ? $block_attributes['verticalAlignment'] : 'top',
      'width' => $width,
    ];
    $columnCellStyles = array_merge(
      $colorStyles,
      [
        'min-width' => '100%',
        'width' => '100%',
        'max-width' => $width,
        'vertical-align' => 'top',
      ]
    );
    $mainCellClasses = 'block wp-block-column';
    $columnCellClasses = 'email_column';

    // The default column alignment is `stretch to fill` which means that we need to set the background color to the main cell
    // to create a feeling of a stretched column. This also needs to apply to CSS classnames which can also apply styles.
    if (!isset($block_attributes['verticalAlignment']) || $block_attributes['verticalAlignment'] === 'stretch') {
      $mainCellStyles = array_merge($mainCellStyles, $colorStyles, $backgroundStyles, $borderStyles);
      $mainCellClasses = $mainCellClasses . ' ' . $classes;
    } else {
      $columnCellStyles = array_merge($columnCellStyles, $backgroundStyles, $borderStyles);
      $columnCellClasses = $columnCellClasses . ' ' . $classes;
    }

    return '
      <td class="' . esc_attr($mainCellClasses) . '" style="' . esc_attr($settingsController->convertStylesToString($mainCellStyles)) . '">
        <div class="email_column" style="width:100%;max-width:' . esc_attr($width) . ';font-size:0px;text-align:left;display:inline-block;">
          <table class="' . esc_attr($columnCellClasses) . '" border="0" cellpadding="0" cellspacing="0" role="presentation" style="' . esc_attr($settingsController->convertStylesToString($columnCellStyles)) . '" width="' . esc_attr($width) . '">
            <tbody>
              <tr>
                <td align="left" style="font-size:0px;' . esc_attr($paddingStyles) . '">
                  <div style="text-align:left;">{column_content}</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </td>
    ';
  }
}
