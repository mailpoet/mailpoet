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
	 * Minimum Gutenberg version required for the email editor.
	 *
	 * @see https://developer.wordpress.org/block-editor/contributors/versions-in-wordpress/
	 */
	public const MIN_GUTENBERG_VERSION = '19.3'; // Version released in WP 6.7.

	/**
	 * Checks if all dependencies are met.
	 */
	public function are_dependencies_met(): bool {
		if ( ! $this->is_wp_version_compatible() ) {
			return false;
		}
		if ( is_plugin_active( 'gutenberg/gutenberg.php' ) ) {
			return $this->is_gutenberg_version_compatible();
		}
		return true;
	}

	/**
	 * Checks if the WordPress version is supported.
	 */
	private function is_wp_version_compatible(): bool {
		return version_compare( get_bloginfo( 'version' ), self::MIN_WP_VERSION, '>=' );
	}

	/**
	 * Checks if the WordPress version is supported.
	 */
	private function is_gutenberg_version_compatible(): bool {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/gutenberg/gutenberg.php', false, false );
		$version     = $plugin_data['Version'] ?? '0.0.0';
		return version_compare( $version, self::MIN_GUTENBERG_VERSION, '>=' );
	}
}
