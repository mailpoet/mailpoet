<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Settings_Controller;
use MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks\Abstract_Block_Renderer;
use MailPoet\EmailEditor\Integrations\Utils\Dom_Document_Helper;
use WP_Style_Engine;

class Group extends Abstract_Block_Renderer {
	protected function renderContent( string $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string {
		$content     = '';
		$innerBlocks = $parsedBlock['innerBlocks'] ?? array();

		foreach ( $innerBlocks as $block ) {
			$content .= render_block( $block );
		}

		return str_replace(
			'{group_content}',
			$content,
			$this->getBlockWrapper( $blockContent, $parsedBlock, $settingsController )
		);
	}

	private function getBlockWrapper( string $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string {
		$originalClassname = ( new Dom_Document_Helper( $blockContent ) )->getAttributeValueByTagName( 'div', 'class' ) ?? '';
		$blockAttributes   = wp_parse_args(
			$parsedBlock['attrs'] ?? array(),
			array(
				'style'           => array(),
				'backgroundColor' => '',
				'textColor'       => '',
				'borderColor'     => '',
				'layout'          => array(),
			)
		);

		// Layout, background, borders need to be on the outer table element.
		$tableStyles                    = $this->getStylesFromBlock(
			array(
				'color'      => array_filter(
					array(
						'background' => $blockAttributes['backgroundColor'] ? $settingsController->translate_slug_to_color( $blockAttributes['backgroundColor'] ) : null,
						'text'       => $blockAttributes['textColor'] ? $settingsController->translate_slug_to_color( $blockAttributes['textColor'] ) : null,
						'border'     => $blockAttributes['borderColor'] ? $settingsController->translate_slug_to_color( $blockAttributes['borderColor'] ) : null,
					)
				),
				'background' => $blockAttributes['style']['background'] ?? array(),
				'border'     => $blockAttributes['style']['border'] ?? array(),
				'spacing'    => array( 'padding' => $blockAttributes['style']['spacing']['margin'] ?? array() ),
			)
		)['declarations'];
		$tableStyles['border-collapse'] = 'separate'; // Needed for the border radius to work.

		// Padding properties need to be added to the table cell.
		$cellStyles = $this->getStylesFromBlock(
			array(
				'spacing' => array( 'padding' => $blockAttributes['style']['spacing']['padding'] ?? array() ),
			)
		)['declarations'];

		$tableStyles['background-size'] = empty( $tableStyles['background-size'] ) ? 'cover' : $tableStyles['background-size'];
		$justifyContent                 = $blockAttributes['layout']['justifyContent'] ?? 'center';
		$width                          = $parsedBlock['email_attrs']['width'] ?? '100%';

		return sprintf(
			'<table class="email-block-group %3$s" style="%1$s" width="100%%" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
        <tbody>
          <tr>
            <td class="email-block-group-content" style="%2$s" align="%4$s" width="%5$s">
              {group_content}
            </td>
          </tr>
        </tbody>
      </table>',
			esc_attr( WP_Style_Engine::compile_css( $tableStyles, '' ) ),
			esc_attr( WP_Style_Engine::compile_css( $cellStyles, '' ) ),
			esc_attr( $originalClassname ),
			esc_attr( $justifyContent ),
			esc_attr( $width ),
		);
	}
}
