<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Blocks_Registry;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Layout\Flex_Layout_Renderer;

class Initializer {
	public function initialize(): void {
		add_action( 'mailpoet_blocks_renderer_initialized', array( $this, 'registerCoreBlocksRenderers' ), 10, 1 );
		add_filter( 'mailpoet_email_editor_theme_json', array( $this, 'adjustThemeJson' ), 10, 1 );
		add_filter( 'safe_style_css', array( $this, 'allowStyles' ) );
	}

	/**
	 * Register core blocks email renderers when the blocks renderer is initialized.
	 */
	public function registerCoreBlocksRenderers( Blocks_Registry $blocksRegistry ): void {
		$blocksRegistry->add_block_renderer( 'core/paragraph', new Renderer\Blocks\Text() );
		$blocksRegistry->add_block_renderer( 'core/heading', new Renderer\Blocks\Text() );
		$blocksRegistry->add_block_renderer( 'core/column', new Renderer\Blocks\Column() );
		$blocksRegistry->add_block_renderer( 'core/columns', new Renderer\Blocks\Columns() );
		$blocksRegistry->add_block_renderer( 'core/list', new Renderer\Blocks\List_Block() );
		$blocksRegistry->add_block_renderer( 'core/list-item', new Renderer\Blocks\List_Item() );
		$blocksRegistry->add_block_renderer( 'core/image', new Renderer\Blocks\Image() );
		$blocksRegistry->add_block_renderer( 'core/buttons', new Renderer\Blocks\Buttons( new Flex_Layout_Renderer() ) );
		$blocksRegistry->add_block_renderer( 'core/button', new Renderer\Blocks\Button() );
		$blocksRegistry->add_block_renderer( 'core/group', new Renderer\Blocks\Group() );
		// Render used for all other blocks
		$blocksRegistry->add_fallback_renderer( new Renderer\Blocks\Fallback() );
	}

	/**
	 * Adjusts the editor's theme to add blocks specific settings for core blocks.
	 */
	public function adjustThemeJson( \WP_Theme_JSON $editorThemeJson ): \WP_Theme_JSON {
		$themeJson = (string) file_get_contents( __DIR__ . '/theme.json' );
		$themeJson = json_decode( $themeJson, true );
		/** @var array $themeJson */
		$editorThemeJson->merge( new \WP_Theme_JSON( $themeJson, 'default' ) );
		return $editorThemeJson;
	}

	/**
	 * Allow styles for the email editor.
	 */
	public function allowStyles( array $allowedStyles ): array {
		$allowedStyles[] = 'display';
		$allowedStyles[] = 'mso-padding-alt';
		$allowedStyles[] = 'mso-font-width';
		$allowedStyles[] = 'mso-text-raise';
		return $allowedStyles;
	}
}
