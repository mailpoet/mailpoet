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
		$template_id = get_stylesheet() . '//email-general'; // Templates id is prefixed with the theme name.
		$template    = $this->templates->get_block_template( $template_id );

		self::assertInstanceOf( \WP_Block_Template::class, $template );
		verify( $template->slug )->equals( 'email-general' );
		verify( $template->id )->equals( $template_id );
		verify( $template->title )->equals( 'General Email' );
		verify( $template->description )->equals( 'A general template for emails.' );
	}

	/**
	 * Test it can add block templates
	 *
	 * @return void
	 */
	public function testItCanAddBlockTemplates(): void {
		$result = $this->templates->add_block_templates( array(), array( 'post_type' => 'mailpoet_email' ), 'wp_template' );

		verify( $result )->arrayCount( 2 );
		verify( $result[0]->content )->notEmpty();
		verify( $result[1]->content )->notEmpty();
	}

	/**
	 * Test it can add and update block template details.
	 *
	 * @return void
	 */
	public function testItCanAddBlockTemplateDetails(): void {
		// add_block_template_details.
		$basic_template       = new \WP_Block_Template();
		$basic_template->slug = 'simple-light';

		// confirm it has no title or description.
		verify( $basic_template->title )->equals( '' );
		verify( $basic_template->description )->equals( '' );

		$result = $this->templates->add_block_template_details( $basic_template );

		// confirm template was updated.
		verify( $basic_template->title )->equals( 'Simple Light' );
		verify( $basic_template->description )->equals( 'A basic template with header and footer.' );
		verify( $result )->equals( $basic_template );
	}
}
