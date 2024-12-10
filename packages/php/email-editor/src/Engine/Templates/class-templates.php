<?php
/**
 * This file is part of the MailPoet Email Editor package.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Templates;

use WP_Block_Template;

/**
 * Templates class.
 */
class Templates {
	const MAILPOET_TEMPLATE_EMPTY_THEME = array( 'version' => 3 ); // The version 3 is important to merge themes correctly.

	/**
	 * Provides the utils.
	 *
	 * @var Utils $utils
	 */
	private Utils $utils;
	/**
	 * The plugin slug.
	 *
	 * @var string $plugin_slug
	 */
	private string $plugin_slug = 'mailpoet/mailpoet';
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
	 * Templates constructor.
	 *
	 * @param Utils $utils The utils.
	 */
	public function __construct(
		Utils $utils
	) {
		$this->utils = $utils;
	}

	/**
	 * Initializes the class.
	 */
	public function initialize(): void {
		add_filter( 'pre_get_block_file_template', array( $this, 'get_block_file_template' ), 10, 3 );
		add_filter( 'get_block_templates', array( $this, 'add_block_templates' ), 10, 3 );
		add_filter( 'theme_templates', array( $this, 'add_theme_templates' ), 10, 4 ); // Needed when saving post – template association.
		add_filter( 'get_block_template', array( $this, 'add_block_template_details' ), 10, 1 );
		add_filter( 'rest_pre_insert_wp_template', array( $this, 'force_post_content' ), 9, 1 );
		$this->initialize_templates();
	}

	/**
	 * Get a block template by ID.
	 *
	 * @param string $template_id The template ID.
	 * @return WP_Block_Template|null
	 */
	public function get_block_template( $template_id ) {
		$templates = $this->get_block_templates();
		return $templates[ $template_id ] ?? null;
	}

	/**
	 * Get block template from file.
	 *
	 * @param WP_Block_Template $result The result.
	 * @param string            $template_id The template ID.
	 * @param string            $template_type The template type.
	 * @return WP_Block_Template
	 */
	public function get_block_file_template( $result, $template_id, $template_type ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		['prefix' => $template_prefix, 'slug' => $template_slug] = $this->utils->get_template_id_parts( $template_id );

		if ( $this->plugin_slug !== $template_prefix ) {
			return $result;
		}

		$template_path = $template_slug . '.html';

		if ( ! is_readable( $this->template_directory . $template_path ) ) {
			return $result;
		}

		return $this->get_block_template_from_file( $template_path );
	}

	/**
	 * Add block templates to the block templates list.
	 *
	 * @param array  $query_result The query result.
	 * @param array  $query The query.
	 * @param string $template_type The template type.
	 * @return array
	 */
	public function add_block_templates( $query_result, $query, $template_type ) {
		if ( 'wp_template' !== $template_type ) {
			return $query_result;
		}

		$post_type = isset( $query['post_type'] ) ? $query['post_type'] : '';

		if ( $post_type && $post_type !== $this->post_type ) {
			return $query_result;
		}

		foreach ( $this->get_block_templates() as $block_template ) {
			$fits_slug_query = ! isset( $query['slug__in'] ) || in_array( $block_template->slug, $query['slug__in'], true );
			$fits_area_query = ! isset( $query['area'] ) || ( property_exists( $block_template, 'area' ) && $block_template->area === $query['area'] );
			$should_include  = $fits_slug_query && $fits_area_query;

			if ( $should_include ) {
				$query_result[] = $block_template;
			}
		}

		return $query_result;
	}

	/**
	 * Add theme templates to the theme templates list.
	 *
	 * @param array    $templates The templates.
	 * @param string   $theme The theme.
	 * @param \WP_Post $post The post.
	 * @param string   $post_type The post type.
	 * @return array
	 */
	public function add_theme_templates( $templates, $theme, $post, $post_type ) {
		if ( $post_type && $post_type !== $this->post_type ) {
			return $templates;
		}
		foreach ( $this->get_block_templates() as $block_template ) {
			$templates[ $block_template->slug ] = $block_template;
		}
		return $templates;
	}

	/**
	 * This is a workaround to ensure the post object passed to `inject_ignored_hooked_blocks_metadata_attributes` contains
	 * content to prevent the template being empty when saved. The issue currently occurs when WooCommerce enables block hooks,
	 * and when older versions of `inject_ignored_hooked_blocks_metadata_attributes` are
	 * used (before https://github.com/WordPress/WordPress/commit/725f302121c84c648c38789b2e88dbd1eb41fa48).
	 * This can be removed in the future.
	 *
	 * To test the issue create a new email, revert template changes, save a color change, then save a color change again.
	 * When you refresh if the post is blank, the issue is present.
	 *
	 * @param \stdClass $changes The changes to the post object.
	 * @return \stdClass
	 */
	public function force_post_content( $changes ) {
		if ( empty( $changes->post_content ) && ! empty( $changes->ID ) ) {
			// Find the existing post object.
			$post = get_post( $changes->ID );
			if ( $post && ! empty( $post->post_content ) ) {
				$changes->post_content = $post->post_content;
			}
		}
		return $changes;
	}

	/**
	 * Add details to templates in editor.
	 *
	 * @param WP_Block_Template $block_template Block template object.
	 * @return WP_Block_Template
	 */
	public function add_block_template_details( $block_template ) {
		if ( ! $block_template || ! isset( $this->templates[ $block_template->slug ] ) ) {
			return $block_template;
		}
		if ( empty( $block_template->title ) ) {
			$block_template->title = $this->templates[ $block_template->slug ]['title'];
		}
		if ( empty( $block_template->description ) ) {
			$block_template->description = $this->templates[ $block_template->slug ]['description'];
		}
		return $block_template;
	}

	/**
	 * Initialize template details. This is done at runtime because of localisation.
	 */
	private function initialize_templates(): void {
		$this->templates['email-general'] = array(
			'title'       => __( 'General Email', 'mailpoet' ),
			'description' => __( 'A general template for emails.', 'mailpoet' ),
		);
		$this->templates['simple-light']  = array(
			'title'       => __( 'Simple Light', 'mailpoet' ),
			'description' => __( 'A basic template with header and footer.', 'mailpoet' ),
		);
	}

	/**
	 * Gets block templates indexed by ID.
	 *
	 * @return WP_Block_Template[]
	 */
	private function get_block_templates() {
		$block_templates     = array_map(
			function ( $template_slug ) {
				return $this->get_block_template_from_file( $template_slug . '.html' );
			},
			array_keys( $this->templates )
		);
		$custom_templates    = $this->get_custom_templates(); // From the DB.
		$custom_template_ids = wp_list_pluck( $custom_templates, 'id' );

		// Combine to remove duplicates if a custom template has the same ID as a file template.
		return array_column(
			array_merge(
				$custom_templates,
				array_filter(
					$block_templates,
					function ( $block_template ) use ( $custom_template_ids ) {
						return ! in_array( $block_template->id, $custom_template_ids, true );
					}
				),
			),
			null,
			'id'
		);
	}

	/**
	 * Get a block template from a file.
	 *
	 * @param string $template The template file.
	 * @return WP_Block_Template
	 */
	private function get_block_template_from_file( string $template ) {
		$template_slug   = $this->utils->get_block_template_slug_from_path( $template );
		$template_object = (object) array(
			'slug'        => $template_slug,
			'id'          => $this->plugin_slug . '//' . $template_slug,
			'title'       => $this->templates[ $template_slug ]['title'] ?? '',
			'description' => $this->templates[ $template_slug ]['description'] ?? '',
			'path'        => $this->template_directory . $template,
			'type'        => 'wp_template',
			'theme'       => $this->plugin_slug,
			'source'      => 'plugin',
			'post_types'  => array(
				$this->post_type,
			),
		);
		return $this->utils->build_block_template_from_file( $template_object );
	}

	/**
	 * Get custom templates from the database.
	 *
	 * @param array  $slugs Array of template slugs to get.
	 * @param string $template_type The template type to get.
	 * @return array
	 */
	private function get_custom_templates( $slugs = array(), $template_type = 'wp_template' ): array {
		$check_query_args = array(
			'post_type'      => $template_type,
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => array( $this->plugin_slug, get_stylesheet() ),
				),
			),
		);

		if ( is_array( $slugs ) && count( $slugs ) > 0 ) {
			$check_query_args['post_name__in'] = $slugs;
		}

		$check_query      = new \WP_Query( $check_query_args );
		$custom_templates = $check_query->posts;

		return array_map(
			function ( $custom_template ) {
				/** @var \WP_Post $custom_template */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
				return $this->utils->build_block_template_from_post( $custom_template );
			},
			$custom_templates
		);
	}
}
