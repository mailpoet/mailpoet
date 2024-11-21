<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use MailPoet\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;

/**
 * Test cases for the Personalization_Tags_Registry class.
 */
class PersonalizationTagsRegistryTest extends TestCase {
	/**
	 * Property for the personalization tags registry.
	 *
	 * @var Personalization_Tags_Registry Personalization tags registry.
	 */
	private $registry;

	/**
	 * Set up the test case.
	 */
	protected function setUp(): void {
		$this->registry = new Personalization_Tags_Registry();
	}

	/**
	 * Register tag and retrieve it.
	 */
	public function testRegisterAndGetTag(): void {
		$callback = function () {
			return 'Personalized Value';
		};

		// Register a tag.
		$this->registry->register(
			'first_name_tag',
			'first_name',
			'Subscriber Info',
			$callback,
			array( 'description' => 'First name of the subscriber' )
		);

		// Retrieve the tag.
		$tag_data = $this->registry->get_by_tag( 'first_name' );

		// Assert that the tag is registered correctly.
		$this->assertNotNull( $tag_data );
		$this->assertSame( 'first_name', $tag_data['tag'] );
		$this->assertSame( 'first_name_tag', $tag_data['name'] );
		$this->assertSame( 'Subscriber Info', $tag_data['category'] );
		$this->assertSame( $callback, $tag_data['callback'] );
		$this->assertSame( 'First name of the subscriber', $tag_data['attributes']['description'] );
	}

	/**
	 * Try to retrieve a tag that hasn't been registered.
	 */
	public function testRetrieveNonexistentTag(): void {
		$this->assertNull( $this->registry->get_by_tag( 'nonexistent' ) );
	}

	/**
	 * Register multiple tags and retrieve them.
	 */
	public function testRegisterDuplicateTag(): void {
		$callback1 = function () {
			return 'Value 1';
		};

		$callback2 = function () {
			return 'Value 2';
		};

		// Register a tag.
		$this->registry->register( 'tag1', 'tag-1', 'Category 1', $callback1 );

		// Attempt to register the same tag again.
		$this->registry->register( 'tag2', 'tag-2', 'Category 2', $callback2 );

		// Retrieve the tag and ensure the first registration is preserved.
		$tag_data = $this->registry->get_by_tag( 'tag-1' );
		$this->assertSame( 'tag1', $tag_data['name'] );
		$this->assertSame( 'Category 1', $tag_data['category'] );
		$this->assertSame( $callback1, $tag_data['callback'] );
	}

	/**
	 * Retrieve all registered tags.
	 */
	public function testGetAllTags(): void {
		$callback = function () {
			return 'Value';
		};

		// Register multiple tags.
		$this->registry->register( 'tag1', 'tag-1', 'Category 1', $callback );
		$this->registry->register( 'tag2', 'tag-2', 'Category 2', $callback );

		// Retrieve all tags.
		$all_tags = $this->registry->get_all();

		// Assert the number of registered tags.
		$this->assertCount( 2, $all_tags );
		$this->assertArrayHasKey( 'tag-1', $all_tags );
		$this->assertArrayHasKey( 'tag-2', $all_tags );
	}

	/**
	 * Initialize the registry and apply a filter.
	 */
	public function testInitializeAppliesFilter(): void {
		// Mock WordPress's `apply_filters` function.
		global $wp_filter_applied;
		$wp_filter_applied = false;

		add_filter(
			'mailpoet_email_editor_register_personalization_tags',
			function ( $registry ) use ( &$wp_filter_applied ) {
				$wp_filter_applied = true;
				return $registry;
			}
		);

		// Initialize the registry.
		$this->registry->initialize();

		// Assert that the filter was applied.
		$this->assertTrue( $wp_filter_applied );
	}
}
