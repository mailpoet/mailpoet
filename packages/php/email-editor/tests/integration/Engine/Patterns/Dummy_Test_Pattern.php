<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Patterns;

/**
 * Dummy test pattern
 */
class Dummy_Test_Pattern extends Abstract_Pattern {
	/**
	 * Name of the pattern
	 *
	 * @var string
	 */
	protected $name = 'dummy-test-pattern';
	/**
	 * Namespace of the pattern
	 *
	 * @var string
	 */
	protected $namespace = 'dummy';
	/**
	 * Get the pattern content
	 *
	 * @return string
	 */
	public function get_content(): string {
		return '<!-- wp:paragraph --><p>Test pattern</p><!-- /wp:paragraph -->';
	}
	/**
	 * Get the pattern title
	 *
	 * @return string
	 */
	public function get_title(): string {
		return 'Test pattern title';
	}
}
