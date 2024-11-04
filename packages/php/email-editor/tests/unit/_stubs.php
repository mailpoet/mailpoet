<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);

// Dummy WP classes.
if ( ! class_exists( \WP_Theme_JSON::class ) ) {
	/**
	 * Class WP_Theme_JSON
	 */
	class WP_Theme_JSON {
		/**
		 * Get data.
		 *
		 * @return array
		 */
		public function get_data() {
			return array();
		}
		/**
		 * Get settings.
		 *
		 * @return array
		 */
		public function get_settings() {
			return array();
		}
	}
}
