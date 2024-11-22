<?php
/**
 * This file is part of the MailPoet Email Editor package.
 *
 * @package MailPoet\EmailEditor
 */

declare( strict_types = 1 );

namespace MailPoet\EmailEditor\Engine;

use MailPoet\EmailEditor\Engine\Renderer\Renderer;

/**
 * Class Send_Preview_Email
 *
 * This class is responsible for handling the functionality to send preview emails.
 * It is part of the email editor integrations utilities.
 *
 * @package MailPoet\EmailEditor\Integrations\Utils
 */
class Send_Preview_Email {

	/**
	 * Instance of the Renderer class used for rendering the editor emails.
	 *
	 * @var Renderer $renderer
	 */
	private Renderer $renderer;

	/**
	 * Send_Preview_Email constructor.
	 *
	 * @param Renderer $renderer renderer instance.
	 */
	public function __construct(
		Renderer $renderer
	) {
		$this->renderer = $renderer;
	}

	/**
	 * Sends a preview email.
	 *
	 * @param array $data The data required to send the preview email.
	 * @return bool Returns true if the preview email was sent successfully, false otherwise.
	 * @throws \Exception If the data is invalid.
	 */
	public function send_preview_email( $data ): bool {

		if ( is_bool( $data ) ) {
			// preview mail already sent. Do not process again.
			return $data;
		}

		$this->validate_data( $data );

		$email   = $data['email'];
		$post_id = $data['postId'];

		$post = $this->fetch_post( $post_id );

		$subject  = $post->post_title;
		$language = get_bloginfo( 'language' );

		$rendered_data = $this->renderer->render(
			$post,
			$subject,
			__( 'Preview', 'mailpoet' ),
			$language
		);

		$email_html_content = $rendered_data['html'];

		return $this->send_email( $email, $subject, $email_html_content );
	}

	/**
	 * Sends an email preview.
	 *
	 * @param string $to The recipient email address.
	 * @param string $subject The subject of the email.
	 * @param string $body The body content of the email.
	 * @return bool Returns true if the email was sent successfully, false otherwise.
	 */
	public function send_email( string $to, string $subject, string $body ): bool {
		add_filter( 'wp_mail_content_type', array( $this, 'set_mail_content_type' ) );

		$result = wp_mail( $to, $subject, $body );

		// Reset content-type to avoid conflicts.
		remove_filter( 'wp_mail_content_type', array( $this, 'set_mail_content_type' ) );

		return $result;
	}


	/**
	 * Sets the mail content type. Used by $this->send_email.
	 *
	 * @param string $content_type The content type to be set for the mail.
	 * @return string The content type that was set.
	 */
	public function set_mail_content_type( string $content_type ): string {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return 'text/html';
	}

	/**
	 * Validates the provided data array.
	 *
	 * @param array $data The data array to be validated.
	 *
	 * @return void
	 * @throws \InvalidArgumentException If the data is invalid.
	 */
	private function validate_data( array $data ) {
		if ( empty( $data['email'] ) || empty( $data['postId'] ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Missing required data', 'mailpoet' ) );
		}

		if ( ! is_email( $data['email'] ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Invalid email', 'mailpoet' ) );
		}
	}


	/**
	 * Fetches a post_id post object based on the provided post ID.
	 *
	 * @param int $post_id The ID of the post to fetch.
	 * @return \WP_Post The WordPress post object.
	 * @throws \Exception If the post is invalid.
	 */
	private function fetch_post( $post_id ): \WP_Post {
		$post = get_post( intval( $post_id ) );
		if ( ! $post instanceof \WP_Post ) {
			throw new \Exception( esc_html__( 'Invalid post', 'mailpoet' ) );
		}
		return $post;
	}
}
