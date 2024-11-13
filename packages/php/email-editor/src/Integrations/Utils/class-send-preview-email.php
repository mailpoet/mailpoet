<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare( strict_types = 1 );

namespace MailPoet\EmailEditor\Integrations\Utils;

use MailPoet\EmailEditor\Engine\Renderer\Renderer;

class Send_Preview_Email {

	private Renderer $renderer;

	/**
	 * Send_Preview_Email constructor.
	 */
	public function __construct(
		Renderer $renderer
	) {
	$this->renderer = $renderer;
	}

	/**
	 * Sends preview email
	 * @throws \Exception
	 */
	public function sendPreviewEmail(array $data): bool {
		$this->validateData($data);

		$email = $data['email'];
		$postId = $data['postId'];

		$post = $this->fetchPost($postId);

		$subject = $post->post_title ?: __('Email Preview', 'mailpoet');
		$language = get_bloginfo('language');

		$renderedData = $this->renderer->render(
			$post,
			$subject,
			__('Preview', 'mailpoet'),
			$language
		);

		$emailHtmlContent = $renderedData['html'];

		return $this->sendEmail($email, $subject, $emailHtmlContent);
	}

	public function sendEmail($to, $subject, $body) {
		add_filter( 'wp_mail_content_type', [$this, 'set_mail_content_type'] );

		$result = wp_mail( $to, $subject, $body );

		// Reset content-type to avoid conflicts
		remove_filter( 'wp_mail_content_type', [$this, 'set_mail_content_type'] );

		return $result;
	}

	public function set_mail_content_type( $content_type ): string {
		return 'text/html';
	}

	private function validateData( array $data ) {
		if ( empty( $data['email'] ) || empty( $data['postId'] ) ) {
			throw new \InvalidArgumentException(__('Missing required data', 'mailpoet'));
		}

		if ( ! is_email( $data['email']) ) {
			throw new \InvalidArgumentException(__('Invalid email', 'mailpoet'));
		}
	}

	/**
	 *
	 * @throws \Exception
	 */
	private function fetchPost( $postId ): \WP_Post {
		$post = get_post(intval($postId));
		if (!$post instanceof \WP_Post) {
			throw new \Exception(__('Invalid post', 'mailpoet'));
		}
		return $post;
	}
}
