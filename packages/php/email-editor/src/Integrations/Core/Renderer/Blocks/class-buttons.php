<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare( strict_types = 1 );
namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Layout\Flex_Layout_Renderer;
use MailPoet\EmailEditor\Engine\Settings_Controller;

/**
 * Renders a buttons block.
 */
class Buttons extends Abstract_Block_Renderer {
	/**
	 * Provides the Flex_Layout_Renderer instance.
	 *
	 * @var Flex_Layout_Renderer
	 */
	private $flex_layout_renderer;

	/**
	 * Buttons constructor.
	 *
	 * @param Flex_Layout_Renderer $flex_layout_renderer Flex layout renderer.
	 */
	public function __construct(
		Flex_Layout_Renderer $flex_layout_renderer
	) {
		$this->flex_layout_renderer = $flex_layout_renderer;
	}

	/**
	 * Renders the block content.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @return string
	 */
	protected function render_content( $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		// Ignore font size set on the buttons block.
		// We rely on TypographyPreprocessor to set the font size on the buttons.
		// Rendering font size on the wrapper causes unwanted whitespace below the buttons.
		if ( isset( $parsed_block['attrs']['style']['typography']['fontSize'] ) ) {
			unset( $parsed_block['attrs']['style']['typography']['fontSize'] );
		}
		return $this->flex_layout_renderer->render_inner_blocks_in_layout( $parsed_block, $settings_controller );
	}
}
