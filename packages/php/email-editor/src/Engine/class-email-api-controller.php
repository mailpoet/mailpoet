<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine;

use MailPoet\EmailEditor\Integrations\Utils\Send_Preview_Email;
use MailPoet\EmailEditor\Validator\Builder;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class for email API controller.
 */
class Email_Api_Controller {

	/**
	 * Property for the send preview email controller.
	 *
	 * @var Send_Preview_Email Send Preview controller.
	 */
	private Send_Preview_Email $send_Preview_Email;

	/**
	 * Email_Api_Controller constructor.
	 */
	public function __construct(
		Send_Preview_Email $send_Preview_Email
	) {
		$this->send_Preview_Email = $send_Preview_Email;
	}

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

	public function send_preview_email_data( WP_REST_Request $request ): WP_REST_Response {
		$data = $request->get_params();
		try {
			$result = $this->send_Preview_Email->sendPreviewEmail($data);
			return new WP_REST_Response(['success' => true, 'result' => $result], 200);
		} catch ( \Exception $exception ) {
			return new WP_REST_Response(['error' => $exception->getMessage()], 400);
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
