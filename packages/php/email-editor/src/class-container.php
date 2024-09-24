<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor;

class Container {
	protected array $services  = array();
	protected array $instances = array();

	public function set( string $name, callable $callable ): void {
		$this->services[ $name ] = $callable;
	}

	/**
	 * @template T
	 * @param class-string<T> $name
	 * @return T
	 */
	public function get( string $name ) {
		// Check if the service is already instantiated
		if ( isset( $this->instances[ $name ] ) ) {
			return $this->instances[ $name ];
		}

		// Check if the service is registered
		if ( ! isset( $this->services[ $name ] ) ) {
			throw new \Exception( "Service not found: $name" );
		}

		$this->instances[ $name ] = $this->services[ $name ]( $this );

		return $this->instances[ $name ];
	}
}
