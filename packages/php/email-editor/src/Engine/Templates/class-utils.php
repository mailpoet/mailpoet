<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Templates;

use WP_Block_Template;
use WP_Error;

/**
 * Utils class.
 */
class Utils {
	/**
	 * Gets the prefix and slug from the template ID.
	 *
	 * @param string $template_id Id of the template in prefix//slug format.
	 * @return array Associative array with keys 'prefix' and 'slug'.
	 */
	public function get_template_id_parts( string $template_id ): array {
		$template_name_parts = explode( '//', $template_id );

		if ( count( $template_name_parts ) < 2 ) {
			return array(
				'prefix' => '',
				'slug'   => '',
			);
		}

		return array(
			'prefix' => $template_name_parts[0],
			'slug'   => $template_name_parts[1],
		);
	}

	/**
	 * Gets the block template slug from the path.
	 *
	 * @param string $path Path to the block template.
	 * @return string
	 */
	public static function get_block_template_slug_from_path( $path ) {
		return basename( $path, '.html' );
	}

	/**
	 * Build a block template from a post.
	 *
	 * @param object $post Post object.
	 * @return WP_Block_Template|WP_Error
	 */
	public function build_block_template_from_post( $post ) {
		$terms = get_the_terms( $post, 'wp_theme' );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		if ( ! $terms ) {
			return new WP_Error( 'template_missing_theme', 'No theme is defined for this template.' );
		}

		$template_prefix = $terms[0]->name;
		$template_slug   = $post->post_name;
		$template_id     = $template_prefix . '//' . $template_slug;

		$template                 = new WP_Block_Template();
		$template->wp_id          = $post->ID;
		$template->id             = $template_id;
		$template->theme          = $template_prefix;
		$template->content        = $post->post_content ? $post->post_content : '<p>empty</p>';
		$template->slug           = $template_slug;
		$template->source         = 'custom';
		$template->type           = $post->post_type;
		$template->description    = $post->post_excerpt;
		$template->title          = $post->post_title;
		$template->status         = $post->post_status;
		$template->has_theme_file = false;
		$template->is_custom      = true;
		$template->post_types     = array();

		if ( 'wp_template_part' === $post->post_type ) {
			$type_terms = get_the_terms( $post, 'wp_template_part_area' );

			if ( ! is_wp_error( $type_terms ) && false !== $type_terms ) {
				$template->area = $type_terms[0]->name;
			}
		}

		return $template;
	}

	/**
	 * Build a block template from a file.
	 *
	 * @param object $template_object Template object.
	 * @return WP_Block_Template
	 */
	public function build_block_template_from_file( $template_object ): WP_Block_Template {
		$template                 = new WP_Block_Template();
		$template->id             = $template_object->id;
		$template->theme          = $template_object->theme;
		$template->content        = (string) file_get_contents( $template_object->path );
		$template->source         = $template_object->source;
		$template->slug           = $template_object->slug;
		$template->type           = $template_object->type;
		$template->title          = $template_object->title;
		$template->description    = $template_object->description;
		$template->status         = 'publish';
		$template->has_theme_file = false;
		$template->post_types     = $template_object->post_types;
		$template->is_custom      = false; // Templates are only custom if they are loaded from the DB.
		$template->area           = 'uncategorized';
		return $template;
	}
}
