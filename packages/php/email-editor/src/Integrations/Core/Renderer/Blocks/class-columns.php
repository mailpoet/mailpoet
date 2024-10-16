<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Settings_Controller;
use MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks\Abstract_Block_Renderer;
use MailPoet\EmailEditor\Integrations\Utils\Dom_Document_Helper;
use WP_Style_Engine;

class Columns extends Abstract_Block_Renderer {
	protected function renderContent( string $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string {
		$content = '';
		foreach ( $parsedBlock['innerBlocks'] ?? array() as $block ) {
			$content .= render_block( $block );
		}

		return str_replace(
			'{columns_content}',
			$content,
			$this->getBlockWrapper( $blockContent, $parsedBlock, $settingsController )
		);
	}

	/**
	 * Based on MJML <mj-section>
	 */
	private function getBlockWrapper( string $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string {
		$originalWrapperClassname = ( new Dom_Document_Helper( $blockContent ) )->getAttributeValueByTagName( 'div', 'class' ) ?? '';
		$block_attributes         = wp_parse_args(
			$parsedBlock['attrs'] ?? array(),
			array(
				'align' => null,
				'width' => $settingsController->get_layout_width_without_padding(),
				'style' => array(),
			)
		);

		$columnsStyles = $this->getStylesFromBlock(
			array(
				'spacing'    => array( 'padding' => $block_attributes['style']['spacing']['padding'] ?? array() ),
				'color'      => $block_attributes['style']['color'] ?? array(),
				'background' => $block_attributes['style']['background'] ?? array(),
			)
		)['declarations'];

		$borderStyles = $this->getStylesFromBlock( array( 'border' => $block_attributes['style']['border'] ?? array() ) )['declarations'];

		if ( ! empty( $borderStyles ) ) {
			$columnsStyles = array_merge( $columnsStyles, array( 'border-style' => 'solid' ), $borderStyles );
		}

		if ( empty( $columnsStyles['background-size'] ) ) {
			$columnsStyles['background-size'] = 'cover';
		}

		$renderedColumns = '<table class="' . esc_attr( 'email-block-columns ' . $originalWrapperClassname ) . '" style="width:100%;border-collapse:separate;text-align:left;' . esc_attr( WP_Style_Engine::compile_css( $columnsStyles, '' ) ) . '" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tbody>
        <tr>{columns_content}</tr>
      </tbody>
    </table>';

		// Margins are not supported well in outlook for tables, so wrap in another table.
		$margins = $block_attributes['style']['spacing']['margin'] ?? array();

		if ( ! empty( $margins ) ) {
			$marginToPaddingStyles = $this->getStylesFromBlock(
				array(
					'spacing' => array( 'margin' => $margins ),
				)
			)['css'];
			$renderedColumns       = '<table class="email-block-columns-wrapper" style="width:100%;border-collapse:separate;text-align:left;' . esc_attr( $marginToPaddingStyles ) . '" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
          <tr>
            <td>' . $renderedColumns . '</td>
          </tr>
        </tbody>
      </table>';
		}

		return $renderedColumns;
	}
}
