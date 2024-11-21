<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Patterns;

require_once 'Dummy_Test_Pattern.php';

/**
 * Integration test for Patterns class
 */
class Patterns_Test extends \MailPoetTest {
	/**
	 * Patterns instance
	 *
	 * @var Patterns
	 */
	private $patterns;
	/**
	 * Set up before each test
	 */
	public function _before() {
		parent::_before();
		$this->patterns = $this->di_container->get( Patterns::class );
		$this->cleanup_patterns();
	}

	/**
	 * Test that the pattern categories are registered in WP_Block_Patterns_Registry
	 */
	public function testItRegistersPatternCategories() {
		$this->patterns->initialize();
		$categories = \WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered();
		$category   = array_pop( $categories );
		$this->assertEquals( 'email-contents', $category['name'] );
		$this->assertEquals( 'Email Contents', $category['label'] );
		$this->assertEquals( 'A collection of email content layouts.', $category['description'] );
	}

	/**
	 * Test that the patterns added via filter are registered in WP_Block_Patterns_Registry
	 */
	public function testItRegistersPatterns() {
		$pattern = new Dummy_Test_Pattern();
		add_filter(
			'mailpoet_email_editor_block_patterns',
			function ( $patterns ) use ( $pattern ) {
				$patterns[] = $pattern;
				return $patterns;
			}
		);
		$this->patterns->initialize();
		$block_patterns = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
		$block_pattern  = array_pop( $block_patterns );
		$this->assertEquals( 'dummy/dummy-test-pattern', $block_pattern['name'] );
		$this->assertEquals( $pattern->get_content(), $block_pattern['content'] );
		$this->assertEquals( $pattern->get_title(), $block_pattern['title'] );
	}

	/**
	 * Clean registered patterns and categories
	 */
	private function cleanup_patterns() {
		$registry       = \WP_Block_Patterns_Registry::get_instance();
		$block_patterns = $registry->get_all_registered();
		foreach ( $block_patterns as $pattern ) {
			$registry->unregister( $pattern['name'] );
		}

		$categories_registry = \WP_Block_Pattern_Categories_Registry::get_instance();
		$categories          = $categories_registry->get_all_registered();
		foreach ( $categories as $category ) {
			$categories_registry->unregister( $category['name'] );
		}
	}
}
