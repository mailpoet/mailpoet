<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine;

use MailPoet\EmailEditor\Validator\Builder;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class for email API controller.
 */
class Email_Api_Controller {

	/**
	 * Returns email specific data.
	 *
	 * @return array - Email specific data such styles.
	 */
	public function get_email_data(): array {
		// Here comes code getting Email specific data that will be passed on 'email_data' attribute.
		return array();
	}

	/**
	 * Update Email specific data we store.
	 *
	 * @param array   $data - Email specific data.
	 * @param WP_Post $email_post - Email post object.
	 */
	public function save_email_data( array $data, WP_Post $email_post ): void {
		// Here comes code saving of Email specific data that will be passed on 'email_data' attribute.
	}

	/**
	 * Sends preview email
	 *
	 * @param WP_REST_Request $request route request.
	 * @return WP_REST_Response
	 */
	public function send_preview_email_data( WP_REST_Request $request ): WP_REST_Response {
		/**
		 * $data - Post Data
		 * format
		 * [_locale] => user
		 * [newsletterId] => NEWSLETTER_ID
		 * [email] => Provided email address
		 * [postId] => POST_ID
		 */
		$data = $request->get_params();
		try {
			$result = apply_filters( 'mailpoet_email_editor_send_preview_email', $data );
			return new WP_REST_Response(
				array(
					'success' => (bool) $result,
					'result'  => $result,
				),
				$result ? 200 : 400
			);
		} catch ( \Exception $exception ) {
			return new WP_REST_Response( array( 'error' => $exception->getMessage() ), 400 );
		}
	}

	/**
	 * Returns the schema for email data.
	 *
	 * @return array
	 */
	public function get_email_data_schema(): array {
		return Builder::object()->to_array();
	}
}
