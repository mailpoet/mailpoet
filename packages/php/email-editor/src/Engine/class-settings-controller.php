<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine;

/**
 * Class managing the settings for the email editor.
 */
class Settings_Controller {

	const ALLOWED_BLOCK_TYPES = array(
		'core/button',
		'core/buttons',
		'core/paragraph',
		'core/heading',
		'core/column',
		'core/columns',
		'core/image',
		'core/list',
		'core/list-item',
		'core/group',
	);

	const DEFAULT_SETTINGS = array(
		'enableCustomUnits' => array( 'px', '%' ),
	);

	/**
	 * Width of the email in pixels.
	*
	 * @var string
	 */
	const EMAIL_WIDTH = '660px';

	/**
	 * Theme controller.
	 *
	 * @var Theme_Controller
	 */
	private Theme_Controller $theme_controller;

	/**
	 * Assets for iframe editor (component styles, scripts, etc.)
	 *
	 * @var array
	 */
	private array $iframe_assets = array();

	/**
	 * Class constructor.
	 *
	 * @param Theme_Controller $theme_controller Theme controller.
	 */
	public function __construct(
		Theme_Controller $theme_controller
	) {
		$this->theme_controller = $theme_controller;
	}

	/**
	 * Method to initialize the settings controller.
	 *
	 * @return void
	 */
	public function init(): void {
		/*
		 * We need to initialize these assets early because they are read from global variables $wp_styles and $wp_scripts
		 * and in later WordPress page load pages they contain stuff we don't want (e.g. html for admin login popup)
		 * in the post editor this is called directly in post.php.
		 */
		$this->iframe_assets = _wp_get_iframed_editor_assets();
	}

	/**
	 * Get the settings for the email editor.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		global $wp_filesystem;

		$core_default_settings = \get_default_block_editor_settings();
		$theme_settings        = $this->theme_controller->get_settings();

		$settings                      = array_merge( $core_default_settings, self::DEFAULT_SETTINGS );
		$settings['allowedBlockTypes'] = self::ALLOWED_BLOCK_TYPES;
		// Assets for iframe editor (component styles, scripts, etc.).
		$settings['__unstableResolvedAssets'] = $this->iframe_assets;

		/*
		 * Custom editor content styles body selector is later transformed to .editor-styles-wrapper
		 * setting padding for bottom and top is needed because \WP_Theme_JSON::get_stylesheet() set them only for .wp-site-blocks selector.
		 */
		$content_variables        = 'body {';
		$content_variables       .= 'padding-bottom: var(--wp--style--root--padding-bottom);';
		$content_variables       .= 'padding-top: var(--wp--style--root--padding-top);';
		$content_variables       .= '}';
		$flex_email_layout_styles = $wp_filesystem->get_contents( __DIR__ . '/flex-email-layout.css' );
		$settings['styles']       = array(
			array( 'css' => $content_variables ),
			array( 'css' => $flex_email_layout_styles ),
		);

		$settings['__experimentalFeatures'] = $theme_settings;

		// Enabling alignWide allows full width for specific blocks such as columns, heading, image, etc.
		$settings['alignWide'] = true;
		return $settings;
	}

	/**
	 * Returns the layout settings for the email editor.
	 *
	 * @return array{contentSize: string, wideSize: string, layout: string}
	 */
	public function get_layout(): array {
		$theme_settings = $this->theme_controller->get_settings();
		return array(
			'contentSize' => $theme_settings['layout']['contentSize'],
			'wideSize'    => $theme_settings['layout']['wideSize'],
			'layout'      => 'constrained',
		);
	}

	/**
	 * Get the email styles.
	 *
	 * @return array{
	 *   spacing: array{
	 *     blockGap: string,
	 *     padding: array{bottom: string, left: string, right: string, top: string}
	 *   },
	 *   color: array{
	 *     background: string
	 *   },
	 *   typography: array{
	 *     fontFamily: string
	 *   }
	 * }
	 */
	public function get_email_styles(): array {
		$theme = $this->get_theme();
		return $theme->get_data()['styles'];
	}

	/**
	 * Returns the width of the layout without padding.
	 *
	 * @return string
	 */
	public function get_layout_width_without_padding(): string {
		$styles = $this->get_email_styles();
		$layout = $this->get_layout();
		$width  = $this->parse_number_from_string_with_pixels( $layout['contentSize'] );
		$width -= $this->parse_number_from_string_with_pixels( $styles['spacing']['padding']['left'] );
		$width -= $this->parse_number_from_string_with_pixels( $styles['spacing']['padding']['right'] );
		return "{$width}px";
	}

	/**
	 * Parse styles string to array.
	 *
	 * @param string $styles Styles string.
	 * @return array
	 */
	public function parse_styles_to_array( string $styles ): array {
		$styles        = explode( ';', $styles );
		$parsed_styles = array();
		foreach ( $styles as $style ) {
			$style = explode( ':', $style );
			if ( count( $style ) === 2 ) {
				$parsed_styles[ trim( $style[0] ) ] = trim( $style[1] );
			}
		}
		return $parsed_styles;
	}

	/**
	 * Returns float number parsed from string with pixels.
	 *
	 * @param string $value Value with pixels.
	 * @return float
	 */
	public function parse_number_from_string_with_pixels( string $value ): float {
		return (float) str_replace( 'px', '', $value );
	}

	/**
	 * Returns the theme.
	 *
	 * @return \WP_Theme_JSON
	 */
	public function get_theme(): \WP_Theme_JSON {
		return $this->theme_controller->get_theme();
	}

	/**
	 * Translate slug to font size.
	 *
	 * @param string $font_size Font size slug.
	 * @return string
	 */
	public function translate_slug_to_font_size( string $font_size ): string {
		return $this->theme_controller->translate_slug_to_font_size( $font_size );
	}

	/**
	 * Translate slug to color.
	 *
	 * @param string $color_slug Color slug.
	 * @return string
	 */
	public function translate_slug_to_color( string $color_slug ): string {
		return $this->theme_controller->translate_slug_to_color( $color_slug );
	}
}
