<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Settings_Controller;

// We have to avoid using keyword `List`
class List_Block extends Abstract_Block_Renderer {
	protected function renderContent( string $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string {
		$html    = new \WP_HTML_Tag_Processor( $blockContent );
		$tagName = ( $parsedBlock['attrs']['ordered'] ?? false ) ? 'ol' : 'ul';
		if ( $html->next_tag( array( 'tag_name' => $tagName ) ) ) {
			$styles = $html->get_attribute( 'style' ) ?? '';
			$styles = $settingsController->parse_styles_to_array( $styles );

			// Font size
			if ( isset( $parsedBlock['email_attrs']['font-size'] ) ) {
				$styles['font-size'] = $parsedBlock['email_attrs']['font-size'];
			} else {
				// Use font-size from email theme when those properties are not set
				$themeData           = $settingsController->get_theme()->get_data();
				$styles['font-size'] = $themeData['styles']['typography']['fontSize'];
			}

			$html->set_attribute( 'style', esc_attr( \WP_Style_Engine::compile_css( $styles, '' ) ) );
			$blockContent = $html->get_updated_html();
		}

		$wrapperStyle = \WP_Style_Engine::compile_css(
			array(
				'margin-top' => $parsedBlock['email_attrs']['margin-top'] ?? '0px',
			),
			''
		);

		// \WP_HTML_Tag_Processor escapes the content, so we have to replace it back
		$blockContent = str_replace( '&#039;', "'", $blockContent );

		return sprintf(
			'<div style="%1$s">%2$s</div>',
			esc_attr( $wrapperStyle ),
			$blockContent
		);
	}
}
