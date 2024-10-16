<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine;

use MailPoet\EmailEditor\Engine\Renderer\Renderer;
use WP_Block_Template;
use WP_Post;
use WP_Theme_JSON;
use WP_Theme_JSON_Resolver;

/**
 * E-mail editor works with own theme.json which defines settings for the editor and styles for the e-mail.
 * This class is responsible for accessing data defined by the theme.json.
 */
class Theme_Controller {
	/**
	 * Core theme loaded from the WordPress core.
	 *
	 * @var WP_Theme_JSON
	 */
	private WP_Theme_JSON $core_theme;

	/**
	 * Base theme loaded from a file in the package directory.
	 *
	 * @var WP_Theme_JSON
	 */
	private WP_Theme_JSON $base_theme;

	/**
	 * Theme_Controller constructor.
	 */
	public function __construct() {
		$this->core_theme = WP_Theme_JSON_Resolver::get_core_data();
		$this->base_theme = new WP_Theme_JSON( (array) json_decode( (string) wp_remote_get( __DIR__ . '/theme.json' ), true ), 'default' );
	}

	/**
	 * Gets combined theme data from the core and base theme, merged with the currently rendered template.
	 *
	 * @return WP_Theme_JSON
	 */
	public function get_theme(): WP_Theme_JSON {
		$theme = new WP_Theme_JSON();
		$theme->merge( $this->core_theme );
		$theme->merge( $this->base_theme );

		if ( Renderer::getTheme() !== null ) {
			$theme->merge( Renderer::getTheme() );
		}

		return apply_filters( 'mailpoet_email_editor_theme_json', $theme );
	}

	/**
	 * Convert compressed format presets to valid CSS values.
	 *
	 * @param string $value Value to convert.
	 * @param array  $presets List of variable presets from theme.json.
	 * @return mixed Converted or original value.
	 */
	private function maybe_convert_preset( $value, $presets ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		if ( strstr( $value, 'var:preset|color|' ) ) {
			$value = str_replace( 'var:preset|color|', '', $value );
			$value = sprintf( 'var(--wp--preset--color--%s)', $value );
		}

		return preg_replace( array_keys( $presets ), array_values( $presets ), $value );
	}

	/**
	 * Method to recursively replace presets in the values.
	 *
	 * @param array $values Values to replace.
	 * @param array $presets List of variable presets from theme.json.
	 * @return mixed
	 */
	private function recursive_replace_presets( $values, $presets ) {
		foreach ( $values as $key => $value ) {
			if ( is_array( $value ) ) {
				$values[ $key ] = $this->recursive_replace_presets( $value, $presets );
			} else {
				$values[ $key ] = self::maybe_convert_preset( $value, $presets );
			}
		}
		return $values;
	}

	/**
	 * Get styles for the e-mail.
	 *
	 * @param \WP_Post|null           $post Post object.
	 * @param \WP_Block_Template|null $template Template object.
	 * @param bool                    $convert_presets Convert presets to valid CSS values.
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
	public function get_styles( $post = null, $template = null, $convert_presets = false ): array {
		$theme_styles = $this->get_theme()->get_data()['styles'];

		// Replace template styles.
		if ( $template && $template->wp_id ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			$template_theme  = (array) get_post_meta( $template->wp_id, 'mailpoet_email_theme', true ); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			$template_styles = (array) ( $template_theme['styles'] ?? array() );
			$theme_styles    = array_replace_recursive( $theme_styles, $template_styles );
		}

		// Replace preset values.
		if ( $convert_presets ) {
			$variables = $this->get_variables_values_map();
			$presets   = array();

			foreach ( $variables as $var_name => $var_value ) {
				$var_pattern             = '/var\(' . preg_quote( $var_name, '/' ) . '\)/i';
				$presets[ $var_pattern ] = $var_value;
			}

			$theme_styles = $this->recursive_replace_presets( $theme_styles, $presets );
		}

		return $theme_styles;
	}

	/**
	 * Get settings from the theme.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		$email_editor_theme_settings                              = $this->get_theme()->get_settings();
		$site_theme_settings                                      = WP_Theme_JSON_Resolver::get_theme_data()->get_settings();
		$email_editor_theme_settings['color']['palette']['theme'] = array();
		if ( isset( $site_theme_settings['color']['palette']['theme'] ) ) {
			$email_editor_theme_settings['color']['palette']['theme'] = $site_theme_settings['color']['palette']['theme'];
		}
		return $email_editor_theme_settings;
	}

	/**
	 * Get stylesheet from the context.
	 *
	 * @param string $context Context.
	 * @param array  $options Options.
	 * @return string
	 */
	public function get_stylesheet_from_context( $context, $options = array() ): string {
		return function_exists( 'gutenberg_style_engine_get_stylesheet_from_context' ) ? gutenberg_style_engine_get_stylesheet_from_context( $context, $options ) : wp_style_engine_get_stylesheet_from_context( $context, $options );
	}

	/**
	 * Get stylesheet for rendering.
	 *
	 * @param WP_Post|null           $post Post object.
	 * @param WP_Block_Template|null $template Template object.
	 * @return string
	 */
	public function get_stylesheet_for_rendering( ?WP_Post $post = null, $template = null ): string {
		$email_theme_settings = $this->get_settings();

		$css_presets = '';
		// Font family classes.
		foreach ( $email_theme_settings['typography']['fontFamilies']['default'] as $font_family ) {
			$css_presets .= ".has-{$font_family['slug']}-font-family { font-family: {$font_family['fontFamily']}; } \n";
		}
		// Font size classes.
		foreach ( $email_theme_settings['typography']['fontSizes']['default'] as $font_size ) {
			$css_presets .= ".has-{$font_size['slug']}-font-size { font-size: {$font_size['size']}; } \n";
		}
		// Color palette classes.
		$color_definitions = array_merge( $email_theme_settings['color']['palette']['theme'], $email_theme_settings['color']['palette']['default'] );
		foreach ( $color_definitions as $color ) {
			$css_presets .= ".has-{$color['slug']}-color { color: {$color['color']}; } \n";
			$css_presets .= ".has-{$color['slug']}-background-color { background-color: {$color['color']}; } \n";
			$css_presets .= ".has-{$color['slug']}-border-color { border-color: {$color['color']}; } \n";
		}

		// Block specific styles.
		$css_blocks = '';
		$blocks     = $this->get_theme()->get_styles_block_nodes();
		foreach ( $blocks as $block_metadata ) {
			$css_blocks .= $this->get_theme()->get_styles_for_block( $block_metadata );
		}

		// Element specific styles.
		$elements_styles = $this->get_theme()->get_raw_data()['styles']['elements'] ?? array();

		// Because the section styles is not a part of the output the `get_styles_block_nodes` method, we need to get it separately.
		if ( $template && $template->wp_id ) {
			$template_theme    = (array) get_post_meta( $template->wp_id, 'mailpoet_email_theme', true );
			$template_styles   = (array) ( $template_theme['styles'] ?? array() );
			$template_elements = $template_styles['elements'] ?? array();
			$elements_styles   = array_replace_recursive( (array) $elements_styles, (array) $template_elements );
		}

		if ( $post ) {
			$post_theme      = (array) get_post_meta( $post->ID, 'mailpoet_email_theme', true );
			$post_styles     = (array) ( $post_theme['styles'] ?? array() );
			$post_elements   = $post_styles['elements'] ?? array();
			$elements_styles = array_replace_recursive( (array) $elements_styles, (array) $post_elements );
		}

		$css_elements = '';
		foreach ( $elements_styles as $key => $elements_style ) {
			$selector = $key;

			if ( 'button' === $key ) {
				$selector      = '.wp-block-button';
				$css_elements .= wp_style_engine_get_styles( $elements_style, array( 'selector' => '.wp-block-button' ) )['css'];
				// Add color to link element.
				$css_elements .= wp_style_engine_get_styles( array( 'color' => array( 'text' => $elements_style['color']['text'] ?? '' ) ), array( 'selector' => '.wp-block-button a' ) )['css'];
				continue;
			}

			switch ( $key ) {
				case 'heading':
					$selector = 'h1, h2, h3, h4, h5, h6';
					break;
				case 'link':
					$selector = 'a:not(.button-link)';
					break;
			}

			$css_elements .= wp_style_engine_get_styles( $elements_style, array( 'selector' => $selector ) )['css'];
		}

		$result = $css_presets . $css_blocks . $css_elements;
		// Because font-size can by defined by the clamp() function that is not supported in the e-mail clients, we need to replace it to the value.
		// Regular expression to match clamp() function and capture its max value.
		$pattern = '/clamp\([^,]+,\s*[^,]+,\s*([^)]+)\)/';
		// Replace clamp() with its maximum value.
		$result = (string) preg_replace( $pattern, '$1', $result );
		return $result;
	}

	/**
	 * Translate font family slug to font family name.
	 *
	 * @param string $font_size Font size slug.
	 * @return string
	 */
	public function translate_slug_to_font_size( string $font_size ): string {
		$settings = $this->get_settings();
		foreach ( $settings['typography']['fontSizes']['default'] as $font_size_definition ) {
			if ( $font_size_definition['slug'] === $font_size ) {
				return $font_size_definition['size'];
			}
		}
		return $font_size;
	}

	/**
	 * Translate color slug to color.
	 *
	 * @param string $color_slug Color slug.
	 * @return string
	 */
	public function translate_slug_to_color( string $color_slug ): string {
		$settings          = $this->get_settings();
		$color_definitions = array_merge( $settings['color']['palette']['theme'], $settings['color']['palette']['default'] );
		foreach ( $color_definitions as $color_definition ) {
			if ( $color_definition['slug'] === $color_slug ) {
				return strtolower( $color_definition['color'] );
			}
		}
		return $color_slug;
	}

	/**
	 * Returns the map of CSS variables and their values from the theme.
	 *
	 * @return array
	 */
	public function get_variables_values_map(): array {
		$variables_css = $this->get_theme()->get_stylesheet( array( 'variables' ) );
		$map           = array();
		// Regular expression to match CSS variable definitions.
		$pattern = '/--(.*?):\s*(.*?);/';

		if ( preg_match_all( $pattern, $variables_css, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				// '--' . $match[1] is the variable name, $match[2] is the variable value.
				$map[ '--' . $match[1] ] = $match[2];
			}
		}

		return $map;
	}
}
