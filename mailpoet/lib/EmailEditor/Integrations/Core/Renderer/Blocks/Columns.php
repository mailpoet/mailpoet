<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Integrations\Utils\DomDocumentHelper;
use WP_Style_Engine;

class Columns implements BlockRenderer {
  public function render(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $content = '';
    foreach ($parsedBlock['innerBlocks'] ?? [] as $block) {
      $content .= render_block($block);
    }

    return str_replace(
      '{columns_content}',
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
   * Based on MJML <mj-section>
   */
  private function getBlockWrapper(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $originalWrapperClassname = (new DomDocumentHelper($blockContent))->getAttributeValueByTagName('div', 'class') ?? '';
    $block_attributes = wp_parse_args($parsedBlock['attrs'] ?? [], [
      'align' => null,
      'width' => $settingsController->getLayoutWidthWithoutPadding(),
      'style' => [],
    ]);

    $cellStyles = $this->getStylesFromBlock( [
      'spacing' => [ 'padding' => $block_attributes['style']['spacing']['padding'] ?? [] ],
      'color' => $block_attributes['style']['color'] ?? [],
      'background' => $block_attributes['style']['background'] ?? [],
      'border' => $block_attributes['style']['border'] ?? [],
    ] )->declarations;

    if (empty($cellStyles['background-size'])) {
      $cellStyles['background-size'] = 'cover';
    }

    // Prepend default border styles to ensure borders are solid and 0px by default.
    $cellStyles = array_merge( [
      'border-width' => '0',
      'border-style' => 'solid',
    ], $cellStyles );

    $contentClassname = 'email_columns ' . $originalWrapperClassname;
    $contentCSS = WP_Style_Engine::compile_css( $cellStyles, '' );
    $layoutCSS = WP_Style_Engine::compile_css( [
      'max-width' => $block_attributes['width'],
      'margin-top' => $parsedBlock['email_attrs']['margin-top'] ?? '0px',
      'padding-left' => $block_attributes['align'] !== 'full' ? $settingsController->getEmailStyles()['layout']['padding']['left'] : '0px',
      'padding-right' => $block_attributes['align'] !== 'full' ? $settingsController->getEmailStyles()['layout']['padding']['right'] : '0px',
    ], '' );

    return '
      <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="width:' . esc_attr( $block_attributes['width'] ) . ';" width="' . esc_attr( $block_attributes['width'] ) . '"><tr><td style="font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
      <div style="' . esc_attr($layoutCSS) . '">
      <table style="width:100%;border-collapse:separate;" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
          <tr>
            <td class="' . esc_attr( $contentClassname ) . '" style="text-align:left;width:100%;' . esc_attr($contentCSS) . '">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:separate;">
                <tr>
                  {columns_content}
                </tr>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
      <!--[if mso | IE]></td></tr></table><![endif]-->
    ';
  }
}
