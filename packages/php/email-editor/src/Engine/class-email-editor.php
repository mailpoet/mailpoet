<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine;

use MailPoet\EmailEditor\Engine\Patterns\Patterns;
use MailPoet\EmailEditor\Engine\Templates\Template_Preview;
use MailPoet\EmailEditor\Engine\Templates\Templates;
use WP_Post;
use WP_Theme_JSON;

/**
 * Email editor class.
 *
 * @phpstan-type EmailPostType array{name: string, args: array, meta: array{key: string, args: array}[]}
 * See register_post_type for details about EmailPostType args.
 */
class Email_Editor {
	public const MAILPOET_EMAIL_META_THEME_TYPE = 'mailpoet_email_theme';

	/**
	 * Property for the email API controller.
	 *
	 * @var Email_Api_Controller Email API controller.
	 */
	private Email_Api_Controller $email_api_controller;
	/**
	 * Property for the templates.
	 *
	 * @var Templates Templates.
	 */
	private Templates $templates;
	/**
	 * Property for the template preview.
	 *
	 * @var Template_Preview Template preview.
	 */
	private Template_Preview $template_preview;
	/**
	 * Property for the patterns.
	 *
	 * @var Patterns Patterns.
	 */
	private Patterns $patterns;
	/**
	 * Property for the settings controller.
	 *
	 * @var Settings_Controller Settings controller.
	 */
	private Settings_Controller $settings_controller;

	/**
	 * Property for the send preview email controller.
	 *
	 * @var Send_Preview_Email Send Preview controller.
	 */
	private Send_Preview_Email $send_preview_email;

	/**
	 * Constructor.
	 *
	 * @param Email_Api_Controller $email_api_controller Email API controller.
	 * @param Templates            $templates Templates.
	 * @param Template_Preview     $template_preview Template preview.
	 * @param Patterns             $patterns Patterns.
	 * @param Settings_Controller  $settings_controller Settings controller.
	 * @param Send_Preview_Email   $send_preview_email Preview email controller.
	 */
	public function __construct(
		Email_Api_Controller $email_api_controller,
		Templates $templates,
		Template_Preview $template_preview,
		Patterns $patterns,
		Settings_Controller $settings_controller,
		Send_Preview_Email $send_preview_email
	) {
		$this->email_api_controller = $email_api_controller;
		$this->templates            = $templates;
		$this->template_preview     = $template_preview;
		$this->patterns             = $patterns;
		$this->settings_controller  = $settings_controller;
		$this->send_preview_email   = $send_preview_email;
	}

	/**
	 * Initialize the email editor.
	 *
	 * @return void
	 */
	public function initialize(): void {
		do_action( 'mailpoet_email_editor_initialized' );
		add_filter( 'mailpoet_email_editor_rendering_theme_styles', array( $this, 'extend_email_theme_styles' ), 10, 2 );
		$this->register_block_templates();
		$this->register_block_patterns();
		$this->register_wmail_post_types();
		$this->register_email_post_send_status();
		$is_editor_page = apply_filters( 'mailpoet_is_email_editor_page', false );
		if ( $is_editor_page ) {
			$this->extend_email_post_api();
			$this->settings_controller->init();
		}
		add_action( 'rest_api_init', array( $this, 'register_email_editor_api_routes' ) );
		add_filter( 'mailpoet_email_editor_send_preview_email', array( $this->send_preview_email, 'send_preview_email' ), 11, 1 ); // allow for other filter methods to take precedent.
	}

	/**
	 * Register block templates.
	 *
	 * @return void
	 */
	private function register_block_templates(): void {
		// Since we cannot currently disable blocks in the editor for specific templates, disable templates when viewing site editor. @see https://github.com/WordPress/gutenberg/issues/41062.
		if ( strstr( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), 'site-editor.php' ) === false ) {
			$this->templates->initialize();
			$this->template_preview->initialize();
		}
	}

	/**
	 * Register block patterns.
	 *
	 * @return void
	 */
	private function register_block_patterns(): void {
		$this->patterns->initialize();
	}

	/**
	 * Register all custom post types that should be edited via the email editor
	 * The post types are added via mailpoet_email_editor_post_types filter.
	 *
	 * @return void
	 */
	private function register_wmail_post_types(): void {
		foreach ( $this->get_post_types() as $post_type ) {
			register_post_type(
				$post_type['name'],
				array_merge( $this->get_default_email_post_args(), $post_type['args'] )
			);
		}
	}

	/**
	 * Returns the email post types.
	 *
	 * @return array
	 * @phpstan-return EmailPostType[]
	 */
	private function get_post_types(): array {
		$post_types = array();
		return apply_filters( 'mailpoet_email_editor_post_types', $post_types );
	}

	/**
	 * Returns the default arguments for email post types.
	 *
	 * @return array
	 */
	private function get_default_email_post_args(): array {
		return array(
			'public'            => false,
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_in_nav_menus' => false,
			'supports'          => array( 'editor', 'title', 'custom-fields' ), // 'custom-fields' is required for loading meta fields via API.
			'has_archive'       => true,
			'show_in_rest'      => true, // Important to enable Gutenberg editor.
		);
	}

	/**
	 * Register the 'sent' post status for emails.
	 *
	 * @return void
	 */
	private function register_email_post_send_status(): void {
		register_post_status(
			'sent',
			array(
				'public'                    => false,
				'exclude_from_search'       => true,
				'internal'                  => true, // for now, we hide it, if we use the status in the listings we may flip this and following values.
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
			)
		);
	}

	/**
	 * Extends the email post types with email specific data.
	 *
	 * @return void
	 */
	public function extend_email_post_api() {
		$email_post_types = array_column( $this->get_post_types(), 'name' );
		register_rest_field(
			$email_post_types,
			'email_data',
			array(
				'get_callback'    => array( $this->email_api_controller, 'get_email_data' ),
				'update_callback' => array( $this->email_api_controller, 'save_email_data' ),
				'schema'          => $this->email_api_controller->get_email_data_schema(),
			)
		);
	}

	/**
	 * Registers the API route endpoint for the email editor
	 *
	 * @return void
	 */
	public function register_email_editor_api_routes() {
		register_rest_route(
			'mailpoet-email-editor/v1',
			'/send_preview_email',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this->email_api_controller, 'send_preview_email_data' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Returns the schema for email theme data.
	 *
	 * @return array
	 */
	public function get_email_theme_data_schema(): array {
		return ( new Email_Styles_Schema() )->get_schema();
	}

	/**
	 * Extends the email theme styles with the email specific styles.
	 *
	 * @param WP_Theme_JSON $theme Email theme styles.
	 * @param WP_Post       $post Email post object.
	 * @return WP_Theme_JSON
	 */
	public function extend_email_theme_styles( WP_Theme_JSON $theme, WP_Post $post ): WP_Theme_JSON {
		$email_theme = get_post_meta( $post->ID, self::MAILPOET_EMAIL_META_THEME_TYPE, true );
		if ( $email_theme && is_array( $email_theme ) ) {
			$theme->merge( new WP_Theme_JSON( $email_theme ) );
		}
		return $theme;
	}
}
