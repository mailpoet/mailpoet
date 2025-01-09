<?php
/**
 * This file is part of the MailPoet Email Editor package.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine;

/**
 * This class is responsible checking the dependencies of the email editor.
 */
class Dependency_Check {
	/**
	 * Minimum WordPress version required for the email editor.
	 */
	public const MIN_WP_VERSION = '6.7';

	/**
	 * Checks if all dependencies are met.
	 */
	public function are_dependencies_met(): bool {
		if ( ! $this->is_wp_version_compatible() ) {
			return false;
		}
		return true;
	}

	/**
	 * Checks if the WordPress version is supported.
	 */
	private function is_wp_version_compatible(): bool {
		return version_compare( get_bloginfo( 'version' ), self::MIN_WP_VERSION, '>=' );
	}
}
