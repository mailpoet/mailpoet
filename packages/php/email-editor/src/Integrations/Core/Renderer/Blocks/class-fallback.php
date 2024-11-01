<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare( strict_types = 1 );
namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Settings_Controller;

/**
 * Fallback block renderer.
 * This renderer is used when no specific renderer is found for a block.
 *
 * AbstractBlockRenderer applies some adjustments to the block content, like adding spacers.
 * By using fallback renderer for all blocks we apply there adjustments to all blocks that don't have any renderer.
 *
 * We need to find a better abstraction/architecture for this.
 */
class Fallback extends Abstract_Block_Renderer {
	/**
	 * Renders the block content.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @return string
	 */
	protected function render_content( $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		return $block_content;
	}
}
