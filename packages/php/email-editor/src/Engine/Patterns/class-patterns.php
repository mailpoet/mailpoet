<?php
/**
 * This file is part of the MailPoet Email Editor.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Patterns;

/**
 * Register block patterns.
 */
class Patterns {
	/**
	 * Initialize block patterns.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->register_block_pattern_categories();
		$this->register_patterns();
	}

	/**
	 * Register block pattern category.
	 *
	 * @return void
	 */
	private function register_block_pattern_categories(): void {
		$categories = apply_filters( 'mailpoet_email_editor_block_pattern_categories', array() );
		foreach ( $categories as $category ) {
			if ( ! is_array( $category ) || ! isset( $category['name'], $category['label'] ) ) {
				continue;
			}
			register_block_pattern_category(
				$category['name'],
				array(
					'label'       => $category['label'],
					'description' => $category['description'] ?? '',
				)
			);
		}
	}

	/**
	 * Register block patterns.
	 *
	 * @return void
	 */
	private function register_patterns() {
		$patterns = apply_filters( 'mailpoet_email_editor_block_patterns', array() );
		foreach ( $patterns as $pattern ) {
			if ( ! $pattern instanceof Abstract_Pattern ) {
				continue;
			}
			register_block_pattern( $pattern->get_namespace() . '/' . $pattern->get_name(), $pattern->get_properties() );
		}
	}
}
