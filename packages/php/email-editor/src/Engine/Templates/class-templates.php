<?php
/**
 * This file is part of the MailPoet Email Editor package.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Templates;

use MailPoet\EmailEditor\Validator\Builder;
use WP_Block_Template;

/**
 * Templates class.
 */
class Templates {
	/**
	 * The plugin slug.
	 *
	 * @var string $plugin_slug
	 */
	private string $template_prefix = 'mailpoet';
	/**
	 * The post type.
	 *
	 * @var string $post_type
	 */
	private string $post_type = 'mailpoet_email';
	/**
	 * The template directory.
	 *
	 * @var string $template_directory
	 */
	private string $template_directory = __DIR__ . DIRECTORY_SEPARATOR;
	/**
	 * The templates.
	 *
	 * @var array $templates
	 */
	private array $templates = array();

	/**
	 * Initializes the class.
	 */
	public function initialize(): void {
		$this->register_templates();
		$this->register_post_types_to_api();
	}

	/**
	 * Get a block template by ID.
	 *
	 * @param string $template_slug The template slug.
	 * @return WP_Block_Template|null
	 */
	public function get_block_template( $template_slug ) {
		// Template id is always prefixed by active theme and get_stylesheet returns the active theme slug.
		$template_id = get_stylesheet() . '//' . $template_slug;
		return get_block_template( $template_id );
	}

	/**
	 * Register the templates via register_block_template
	 */
	private function register_templates(): void {
		// The function was added in WordPress 6.7. We can remove this check after we drop support for WordPress 6.6.
		if ( ! function_exists( 'register_block_template' ) ) {
			return;
		}
		$this->templates['email-general'] = array(
			'title'       => __( 'General Email', 'mailpoet' ),
			'description' => __( 'A general template for emails.', 'mailpoet' ),
		);
		$this->templates['simple-light']  = array(
			'title'       => __( 'Simple Light', 'mailpoet' ),
			'description' => __( 'A basic template with header and footer.', 'mailpoet' ),
		);

		foreach ( $this->templates as $template_slug => $template ) {
			$template_filename = $template_slug . '.html';
			register_block_template(
				$this->template_prefix . '//' . $template_slug,
				array(
					'title'       => $template['title'],
					'description' => $template['description'],
					'content'     => (string) file_get_contents( $this->template_directory . $template_filename ),
					'post_types'  => array( $this->post_type ),
				)
			);
		}
	}

	/**
	 * Register post_types property to the templates rest api response.
	 *
	 * There is a PR that adds the property into the core https://github.com/WordPress/wordpress-develop/pull/7530
	 * Until it is merged, we need to add it manually.
	 */
	public function register_post_types_to_api(): void {
		$controller = new \WP_REST_Templates_Controller( 'wp_template' );
		$schema     = $controller->get_item_schema();
		// Future compatibility check if the post_types property is already registered.
		if ( isset( $schema['properties']['post_types'] ) ) {
			return;
		}
		register_rest_field(
			'wp_template',
			'post_types',
			array(
				'get_callback'    => array( $this, 'get_post_types' ),
				'update_callback' => null,
				'schema'          => Builder::string()->to_array(),
			)
		);
	}

	/**
	 * This is a callback function for adding post_types property to templates rest api response.
	 *
	 * @param array $response_object The rest API response object.
	 * @return array
	 */
	public function get_post_types( $response_object ): array {
		if ( isset( $response_object['plugin'] ) && $response_object['plugin'] !== $this->template_prefix ) {
			return array();
		}
		return array( $this->post_type );
	}
}
