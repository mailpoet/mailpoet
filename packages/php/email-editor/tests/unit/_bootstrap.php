<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);

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
