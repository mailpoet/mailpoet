<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine;

use Codeception\Stub\Expected;
use MailPoet\EmailEditor\Engine\Renderer\Renderer;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Unit test for Send_Preview_Email_Test class.
 */
class Send_Preview_Email_Test extends \MailPoetTest {

	/**
	 * Instance of Send_Preview_Email
	 *
	 * @var Send_Preview_Email
	 */
	private $send_preview_email;

	/**
	 * Instance of Renderer
	 *
	 * @var Renderer
	 */
	private $renderer_mock;

	/**
	 * Set up before each test
	 */
	public function _before() {
		parent::_before();

		$this->renderer_mock = $this->createMock( Renderer::class );
		$this->renderer_mock->method( 'render' )->willReturn(
			array(
				'html' => 'test html',
				'text' => 'test text',
			)
		);

		$this->send_preview_email = $this->getServiceWithOverrides(
			Send_Preview_Email::class,
			array(
				'renderer' => $this->renderer_mock,
			)
		);
	}

	/**
	 * Test it sends preview email.
	 */
	public function testItSendsPreviewEmail(): void {
		$spe = $this->make(
			Send_Preview_Email::class,
			array(
				'renderer'                => $this->renderer_mock,
				'send_email'              => Expected::once( true ),
				'set_personalize_content' => function ( $param ) {
					return $param;
				},
			)
		);

		$email_post = $this->tester->create_post(
			array(
				'post_content' => '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link has-background wp-element-button">Button</a></div><!-- /wp:button -->',
			)
		);

		$post_data = array(
			'newsletterId' => 2,
			'email'        => 'hello@example.com',
			'postId'       => $email_post->ID,
		);

		$result = $spe->send_preview_email( $post_data );

		verify( $result )->equals( true );
	}

	/**
	 * Test it returns the status of send_mail.
	 */
	public function testItReturnsTheStatusOfSendMail(): void {
		$mailing_status = false;

		$spe = $this->make(
			Send_Preview_Email::class,
			array(
				'renderer'                => $this->renderer_mock,
				'send_email'              => Expected::once( $mailing_status ),
				'set_personalize_content' => function ( $param ) {
					return $param;
				},
			)
		);

		$email_post = $this->tester->create_post(
			array(
				'post_content' => '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link has-background wp-element-button">Button</a></div><!-- /wp:button -->',
			)
		);

		$post_data = array(
			'newsletterId' => 2,
			'email'        => 'hello@example.com',
			'postId'       => $email_post->ID,
		);

		$result = $spe->send_preview_email( $post_data );

		verify( $result )->equals( $mailing_status );
	}

	/**
	 * Test it throws an exception with invalid email
	 */
	public function testItThrowsAnExceptionWithInvalidEmail(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid email' );
		$post_data = array(
			'newsletterId' => 2,
			'email'        => 'hello@example',
			'postId'       => 4,
		);
		$this->send_preview_email->send_preview_email( $post_data );
	}

	/**
	 * Test it throws an exception when post id is not provided
	 */
	public function testItThrowsAnExceptionWhenPostIdIsNotProvided(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Missing required data' );
		$post_data = array(
			'newsletterId' => 2,
			'email'        => 'hello@example.com',
			'postId'       => null,
		);
		$this->send_preview_email->send_preview_email( $post_data );
	}

	/**
	 * Test it throws an exception when the post cannot be found
	 */
	public function testItThrowsAnExceptionWhenPostCannotBeFound(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid post' );
		$post_data = array(
			'newsletterId' => 2,
			'email'        => 'hello@example.com',
			'postId'       => 100,
		);
		$this->send_preview_email->send_preview_email( $post_data );
	}
}
