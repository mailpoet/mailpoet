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
	 * Returns the schema for email data.
	 *
	 * @return array
	 */
	public function get_email_data_schema(): array {
		return Builder::object()->toArray();
	}
}
