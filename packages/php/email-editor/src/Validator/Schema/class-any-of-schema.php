<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Validator\Schema;

use MailPoet\EmailEditor\Validator\Schema;

// See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#oneof-and-anyof
class Any_Of_Schema extends Schema {
	protected $schema = array(
		'anyOf' => array(),
	);

	/** @param Schema[] $schemas */
	public function __construct(
		array $schemas
	) {
		foreach ( $schemas as $schema ) {
			$this->schema['anyOf'][] = $schema->toArray();
		}
	}

	public function nullable(): self {
		$null  = array( 'type' => 'null' );
		$anyOf = $this->schema['anyOf'];
		$value = in_array( $null, $anyOf, true ) ? $anyOf : array_merge( $anyOf, array( $null ) );
		return $this->updateSchemaProperty( 'anyOf', $value );
	}

	public function nonNullable(): self {
		$null  = array( 'type' => 'null' );
		$anyOf = $this->schema['anyOf'];
		$value = array_filter(
			$anyOf,
			function ( $item ) use ( $null ) {
				return $item !== $null;
			}
		);
		return $this->updateSchemaProperty( 'anyOf', $value );
	}
}