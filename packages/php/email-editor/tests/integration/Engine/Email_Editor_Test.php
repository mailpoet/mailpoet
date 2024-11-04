<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine;

/**
 * Integration test for Email_Editor class
 */
class Email_Editor_Test extends \MailPoetTest {
	/**
	 * Email editor instance
	 *
	 * @var Email_Editor
	 */
	private $email_editor;

	/**
	 * Callback to register custom post type
	 *
	 * @var callable
	 */
	private $post_register_callback;

	/**
	 * Set up before each test
	 */
	public function _before() {
		parent::_before();
		$this->email_editor           = $this->di_container->get( Email_Editor::class );
		$this->post_register_callback = function ( $post_types ) {
			$post_types[] = array(
				'name' => 'custom_email_type',
				'args' => array(),
				'meta' => array(),
			);
			return $post_types;
		};
		add_filter( 'mailpoet_email_editor_post_types', $this->post_register_callback );
		$this->email_editor->initialize();
	}

	/**
	 * Test if the email register custom post type
	 */
	public function testItRegistersCustomPostTypeAddedViaHook() {
		$post_types = get_post_types();
		$this->assertArrayHasKey( 'custom_email_type', $post_types );
	}

	/**
	 * Clean up after each test
	 */
	public function _after() {
		parent::_after();
		remove_filter( 'mailpoet_email_editor_post_types', $this->post_register_callback );
	}
}
