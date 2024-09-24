<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

use MailPoet\EmailEditor\Validator\Builder;

class Email_Api_Controller {
	/**
	 * @return array - Email specific data such styles.
	 */
	public function getEmailData(): array {
		// Here comes code getting Email specific data that will be passed on 'email_data' attribute
		return array();
	}

	/**
	 * Update Email specific data we store.
	 */
	public function saveEmailData( array $data, \WP_Post $emailPost ): void {
		// Here comes code saving of Email specific data that will be passed on 'email_data' attribute
	}

	public function getEmailDataSchema(): array {
		return Builder::object()->toArray();
	}
}