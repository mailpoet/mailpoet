<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Integrations\Utils;

use MailPoet\EmailEditor\Engine\Renderer\Renderer;

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
	 * Set up before each test
	 */
	public function _before() {
		parent::_before();

		$renderer_mock = $this->createMock( Renderer::class );
		$renderer_mock->method( 'render' )->willReturn(
			array(
				'html' => 'test html',
				'text' => 'test text',
			)
		);

		$this->send_preview_email = $this->getServiceWithOverrides(
			Send_Preview_Email::class,
			array(
				'renderer' => $renderer_mock,
			)
		);
	}

	/**
	 * Test it sends preview email.
	 */
	public function testItSendsPreviewEmail(): void {
		$this->send_preview_email->send_email = function () {
			return true;
		};

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

		$result = $this->send_preview_email->send_preview_email( $post_data );

		verify( $result )->equals( true );
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
