<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Integrations\Utils\DomDocumentHelper;
use WP_Style_Engine;

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
    return (object)wp_parse_args($styles, [
      'css' => '',
      'declarations' => [],
      'classnames' => '',
    ]);
  }

  /**
   * Based on MJML <mj-column>
   */
  private function getBlockWrapper(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $originalWrapperClassname = (new DomDocumentHelper($blockContent))->getAttributeValueByTagName('div', 'class') ?? '';
    $block_attributes = wp_parse_args($parsedBlock['attrs'] ?? [], [
      'verticalAlignment' => 'top',
      'width' => $settingsController->getLayoutWidthWithoutPadding(),
      'style' => [],
    ]);

    // The default column alignment is `stretch to fill` which means that we need to set the background color to the main cell
    // to create a feeling of a stretched column. This also needs to apply to CSS classnames which can also apply styles.
    $isStretched = empty( $block_attributes['verticalAlignment'] ) || $block_attributes['verticalAlignment'] === 'stretch';

    $paddingCSS = $this->getStylesFromBlock( [ 'spacing' => [ 'padding' => $block_attributes['style']['spacing']['padding'] ?? [] ] ] )->css;
    $cellStyles = $this->getStylesFromBlock( [
        'color' => $block_attributes['style']['color'] ?? [],
        'background' => $block_attributes['style']['background'] ?? [],
        'border' => $block_attributes['style']['border'] ?? [],
      ] )->declarations;

    if (!empty($cellStyles['background-image']) && empty($cellStyles['background-size'])) {
      $cellStyles['background-size'] = 'cover';
    }

    // Prepend default border styles to ensure borders are solid and 0px by default.
    $cellStyles = array_merge( [
      'border-width' => '0',
      'border-style' => 'solid',
    ], $cellStyles );

    $wrapperClassname = 'block wp-block-column';
    $contentClassname = 'email_column';
    $wrapperCSS = WP_Style_Engine::compile_css( [
      'vertical-align' => $isStretched ? 'top' : $block_attributes['verticalAlignment'],
    ], '' );
    $contentCSS = 'vertical-align: top;';

    if ($isStretched) {
      $wrapperClassname .= ' ' . $originalWrapperClassname;
      $wrapperCSS .= ' ' . WP_Style_Engine::compile_css( $cellStyles, '' );
    } else {
      $contentClassname .= ' ' . $originalWrapperClassname;
      $contentCSS .= ' ' . WP_Style_Engine::compile_css( $cellStyles, '' );
    }

    return '
      <td class="' . esc_attr($wrapperClassname) . '" style="' . esc_attr($wrapperCSS) . '" width="' . esc_attr( $block_attributes['width'] ) . '">
        <table class="' . esc_attr($contentClassname) . '" style="' . esc_attr($contentCSS) . '" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
          <tbody>
            <tr>
              <td align="left" style="text-align:left;' . esc_attr($paddingCSS) . '">
                {column_content}
              </td>
            </tr>
          </tbody>
        </table>
      </td>
    ';
  }
}
