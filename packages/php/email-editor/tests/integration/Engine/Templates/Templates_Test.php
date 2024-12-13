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
		$this->templates->initialize();
	}

	/**
	 * Test it can fetch block template
	 *
	 * @return void
	 */
	public function testItCanFetchBlockTemplate(): void {
		$template = $this->templates->get_block_template( 'email-general' );

		self::assertInstanceOf( \WP_Block_Template::class, $template );
		verify( $template->slug )->equals( 'email-general' );
		verify( $template->id )->stringContainsString( 'email-general' );
		verify( $template->title )->equals( 'General Email' );
		verify( $template->description )->equals( 'A general template for emails.' );
	}
}
