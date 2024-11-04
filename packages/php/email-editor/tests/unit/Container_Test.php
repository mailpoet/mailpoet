<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor;

use Exception;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit test for Container class.
 */
class Container_Test extends TestCase {
	/**
	 * Test if sets and gets service.
	 */
	public function testSetAndGetService(): void {
		$container = new Container();

		$container->set(
			'simple_service',
			function () {
				return new stdClass();
			}
		);

		$service = $container->get( 'simple_service' );

		$this->assertInstanceOf( stdClass::class, $service );
	}

	/**
	 * Test if sets and gets service with dependencies.
	 */
	public function testGetReturnsSameInstance(): void {
		$container = new Container();

		$container->set(
			'singleton_service',
			function () {
				return new stdClass();
			}
		);

		// Retrieve the service twice.
		$service1 = $container->get( 'singleton_service' );
		$service2 = $container->get( 'singleton_service' );

		// Check that both instances are the same.
		$this->assertSame( $service1, $service2 );
	}

	/**
	 * Test if it throws exception for non-existing service.
	 */
	public function testExceptionForNonExistingService(): void {
		// Create the container instance.
		$container = new Container();

		// Attempt to get a non-existing service should throw an exception.
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Service not found: non_existing_service' );

		$container->get( 'non_existing_service' );
	}
}
