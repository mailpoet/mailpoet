<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Templates;

/**
 * Integration test for the Templates class
 */
class Templates_Test extends \MailPoetTest {

	/**
	 * Templates.
	 *
	 * @var Templates
	 */
	private Templates $templates;

	/**
	 * Set up before each test
	 */
	public function _before() {
		parent::_before();
		$this->templates = $this->di_container->get( Templates::class );
	}

	/**
	 * Test it can fetch block template
	 *
	 * @return void
	 */
	public function testItCanFetchBlockTemplate(): void {
		$this->templates->initialize( array( 'mailpoet_email' ) );
		$template = $this->templates->get_block_template( 'email-general' );

		self::assertInstanceOf( \WP_Block_Template::class, $template );
		verify( $template->slug )->equals( 'email-general' );
		verify( $template->id )->stringContainsString( 'email-general' );
		verify( $template->title )->equals( 'General Email' );
		verify( $template->description )->equals( 'A general template for emails.' );
	}

	/**
	 * Test that action for registering templates is triggered
	 *
	 * @return void
	 */
	public function testItTriggersActionForRegisteringTemplates(): void {
		$trigger_check = false;
		add_filter(
			'mailpoet_email_editor_register_templates',
			function ( $registry ) use ( &$trigger_check ) {
				$trigger_check = true;
				return $registry;
			}
		);
		$this->templates->initialize( array( 'mailpoet_email' ) );
		verify( $trigger_check )->true();
	}
}
