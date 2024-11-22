<?php
/**
 * This file is part of the MailPoet Email Editor package.
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
	}

	/**
	 * Register block pattern category.
	 *
	 * @return void
	 */
	private function register_block_pattern_categories(): void {
		$categories = array(
			array(
				'name'        => 'email-contents',
				'label'       => _x( 'Email Contents', 'Block pattern category', 'mailpoet' ),
				'description' => __( 'A collection of email content layouts.', 'mailpoet' ),
			),
		);
		foreach ( $categories as $category ) {
			register_block_pattern_category(
				$category['name'],
				array(
					'label'       => $category['label'],
					'description' => $category['description'] ?? '',
				)
			);
		}
	}
}
