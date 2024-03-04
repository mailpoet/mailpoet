<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Integrations\Utils\DomDocumentHelper;

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
    return (object)wp_parse_args($styles['declarations'], [
      'css' => '',
      'declarations' => [],
      'classnames' => '',
    ]);
  }

  /**
   * Based on MJML <mj-section>
   */
  private function getBlockWrapper(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $block_attributes = isset( $parsedBlock['attrs'] ) && is_array( $parsedBlock['attrs'] ) ? $parsedBlock['attrs'] : [];
    $block_styles = isset( $block_attributes['style'] ) && is_array( $block_attributes['style'] ) ? $block_attributes['style'] : [];
    $classes = (new DomDocumentHelper($blockContent))->getAttributeValueByTagName('div', 'class') ?? '';

    $width = $parsedBlock['email_attrs']['width'] ?? $settingsController->getLayoutWidthWithoutPadding();
    $marginTop = $parsedBlock['email_attrs']['margin-top'] ?? '0px';
    $paddingStyles = $this->getStylesFromBlock( ['spacing' => ['padding' => $parsedBlock['attrs']['style']['spacing']['padding'] ?? null ]] )->declarations;
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

    $align = $block_attributes['align'] ?? null;
    if ($align !== 'full') {
      $layoutPaddingLeft = $settingsController->getEmailStyles()['layout']['padding']['left'];
      $layoutPaddingRight = $settingsController->getEmailStyles()['layout']['padding']['right'];
    } else {
      $layoutPaddingLeft = '0px';
      $layoutPaddingRight = '0px';
    }

    return '
      <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="width:' . esc_attr( $width ) . ';" width="' . esc_attr( $width ) . '"><tr><td style="font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
      <div style="' . esc_attr($settingsController->convertStylesToString([
        'margin-top' => $marginTop,
        'max-width' => $width,
        'padding-left' => $layoutPaddingLeft,
        'padding-right' => $layoutPaddingRight,
      ])) . '">
        <table
          class="' . esc_attr( $classes ) . '"
          align="center"
          border="0"
          cellpadding="0"
          cellspacing="0"
          role="presentation"
          style="' . esc_attr($settingsController->convertStylesToString(array_merge($colorStyles, $backgroundStyles, $borderStyles))) . ';max-width:' . esc_attr( $width ) . ';width:100%;border-collapse:separate;"
        >
          <tbody>
            <tr>
              <td style="
              ' . esc_attr($settingsController->convertStylesToString(array_merge($paddingStyles, [
                'text-align' => 'left',
                'font-size' => '0px',
              ]))) . '">
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
