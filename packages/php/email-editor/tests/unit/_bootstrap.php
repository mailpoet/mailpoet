<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);

require_once __DIR__ . '/../../vendor/autoload.php';

$console = new \Codeception\Lib\Console\Output( array() );

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Mock esc_attr function.
	 *
	 * @param string $attr Attribute to escape.
	 */
	function esc_attr( $attr ) {
		return $attr;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Mock esc_html function.
	 *
	 * @param string $text Text to escape.
	 */
	function esc_html( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Mock add_filter function.
	 *
	 * @param string   $tag Tag to add filter for.
	 * @param callable $callback Callback to call.
	 */
	function add_filter( $tag, $callback ) {
		global $wp_filters;
		if ( ! isset( $wp_filters ) ) {
			$wp_filters = array();
		}
		$wp_filters[ $tag ][] = $callback;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Mock apply_filters function.
	 *
	 * @param string $tag Tag to apply filters for.
	 * @param mixed  $value Value to filter.
	 */
	function apply_filters( $tag, $value ) {
		global $wp_filters;
		if ( isset( $wp_filters[ $tag ] ) ) {
			foreach ( $wp_filters[ $tag ] as $callback ) {
				$value = call_user_func( $callback, $value );
			}
		}
		return $value;
	}
}

/**
 * Base class for unit tests.
 */
abstract class MailPoetUnitTest extends \Codeception\TestCase\Test {
	/**
	 * Disable running tests in separate processes.
	 *
	 * @var bool
	 */
	protected $runTestInSeparateProcess = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
	/**
	 * Disable preserving global state.
	 *
	 * @var bool
	 */
	protected $preserveGlobalState = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
}

require '_stubs.php';
