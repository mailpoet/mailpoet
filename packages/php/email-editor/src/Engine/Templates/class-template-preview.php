<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Templates;

use MailPoet\EmailEditor\Engine\Settings_Controller;
use MailPoet\EmailEditor\Engine\Theme_Controller;
use MailPoet\EmailEditor\Validator\Builder;
use WP_Theme_JSON;

/**
 * Template_Preview class.
 */
class Template_Preview {
	/**
	 * Provides the theme controller.
	 *
	 * @var Theme_Controller
	 */
	private Theme_Controller $theme_controller;
	/**
	 * Provides the settings controller.
	 *
	 * @var Settings_Controller
	 */
	private Settings_Controller $settings_controller;
	/**
	 * Provides the templates.
	 *
	 * @var Templates
	 */
	private Templates $templates;

	/**
	 * Template_Preview constructor.
	 *
	 * @param Theme_Controller    $theme_controller Theme controller.
	 * @param Settings_Controller $settings_controller Theme controller.
	 * @param Templates           $templates Templates.
	 */
	public function __construct(
		Theme_Controller $theme_controller,
		Settings_Controller $settings_controller,
		Templates $templates
	) {
		$this->theme_controller    = $theme_controller;
		$this->settings_controller = $settings_controller;
		$this->templates           = $templates;
	}

	/**
	 * Initializes the class.
	 */
	public function initialize(): void {
		register_rest_field(
			'wp_template',
			'email_theme_css',
			array(
				'get_callback'    => array( $this, 'get_email_theme_preview_css' ),
				'update_callback' => null,
				'schema'          => Builder::string()->to_array(),
			)
		);
	}

	/**
	 * Generates CSS for preview of email theme
	 * They are applied in the preview BLockPreview in template selection
	 *
	 * @param array $template Template data.
	 */
	public function get_email_theme_preview_css( $template ): string {
		$editor_theme   = clone $this->theme_controller->get_theme();
		$template_theme = $this->templates->get_block_template_theme( $template['id'], $template['wp_id'] );
		if ( is_array( $template_theme ) ) {
			$editor_theme->merge( new WP_Theme_JSON( $template_theme, 'custom' ) );
		}
		$editor_settings = $this->settings_controller->get_settings();
		$additional_css  = '';
		foreach ( $editor_settings['styles'] as $style ) {
			$additional_css .= $style['css'];
		}
		// Set proper content width for previews.
		$layout_settings = $this->theme_controller->get_layout_settings();
		$additional_css .= ".is-root-container { width: {$layout_settings['contentSize']}; margin: 0 auto; }";
		return $editor_theme->get_stylesheet() . $additional_css;
	}
}
