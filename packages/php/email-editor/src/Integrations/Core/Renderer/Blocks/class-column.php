<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Settings_Controller;
use MailPoet\EmailEditor\Integrations\Utils\Dom_Document_Helper;
use WP_Style_Engine;

class Column extends Abstract_Block_Renderer {
	/**
	 * Override this method to disable spacing (block gap) for columns.
	 * Spacing is applied on wrapping columns block. Columns are rendered side by side so no spacer is needed.
	 */
	protected function addSpacer( $content, $emailAttrs ): string {
		return $content;
	}

	protected function renderContent( string $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string {
		$content = '';
		foreach ( $parsedBlock['innerBlocks'] ?? array() as $block ) {
			$content .= render_block( $block );
		}

		return str_replace(
			'{column_content}',
			$content,
			$this->getBlockWrapper( $blockContent, $parsedBlock, $settingsController )
		);
	}

	/**
	 * Based on MJML <mj-column>
	 */
	private function getBlockWrapper( string $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string {
		$originalWrapperClassname = ( new Dom_Document_Helper( $blockContent ) )->getAttributeValueByTagName( 'div', 'class' ) ?? '';
		$block_attributes         = wp_parse_args(
			$parsedBlock['attrs'] ?? array(),
			array(
				'verticalAlignment' => 'stretch',
				'width'             => $settingsController->get_layout_width_without_padding(),
				'style'             => array(),
			)
		);

		// The default column alignment is `stretch to fill` which means that we need to set the background color to the main cell
		// to create a feeling of a stretched column. This also needs to apply to CSS classnames which can also apply styles.
		$isStretched = empty( $block_attributes['verticalAlignment'] ) || $block_attributes['verticalAlignment'] === 'stretch';

		$paddingCSS = $this->getStylesFromBlock( array( 'spacing' => array( 'padding' => $block_attributes['style']['spacing']['padding'] ?? array() ) ) )['css'];
		$cellStyles = $this->getStylesFromBlock(
			array(
				'color'      => $block_attributes['style']['color'] ?? array(),
				'background' => $block_attributes['style']['background'] ?? array(),
			)
		)['declarations'];

		$borderStyles = $this->getStylesFromBlock( array( 'border' => $block_attributes['style']['border'] ?? array() ) )['declarations'];

		if ( ! empty( $borderStyles ) ) {
			$cellStyles = array_merge( $cellStyles, array( 'border-style' => 'solid' ), $borderStyles );
		}

		if ( ! empty( $cellStyles['background-image'] ) && empty( $cellStyles['background-size'] ) ) {
			$cellStyles['background-size'] = 'cover';
		}

		$wrapperClassname = 'block wp-block-column email-block-column';
		$contentClassname = 'email-block-column-content';
		$wrapperCSS       = WP_Style_Engine::compile_css(
			array(
				'vertical-align' => $isStretched ? 'top' : $block_attributes['verticalAlignment'],
			),
			''
		);
		$contentCSS       = 'vertical-align: top;';

		if ( $isStretched ) {
			$wrapperClassname .= ' ' . $originalWrapperClassname;
			$wrapperCSS       .= ' ' . WP_Style_Engine::compile_css( $cellStyles, '' );
		} else {
			$contentClassname .= ' ' . $originalWrapperClassname;
			$contentCSS       .= ' ' . WP_Style_Engine::compile_css( $cellStyles, '' );
		}

		return '
      <td class="' . esc_attr( $wrapperClassname ) . '" style="' . esc_attr( $wrapperCSS ) . '" width="' . esc_attr( $block_attributes['width'] ) . '">
        <table class="' . esc_attr( $contentClassname ) . '" style="' . esc_attr( $contentCSS ) . '" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
          <tbody>
            <tr>
              <td align="left" style="text-align:left;' . esc_attr( $paddingCSS ) . '">
                {column_content}
              </td>
            </tr>
          </tbody>
        </table>
      </td>
    ';
	}
}
