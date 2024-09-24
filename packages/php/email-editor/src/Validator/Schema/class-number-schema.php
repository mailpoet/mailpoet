<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Validator\Schema;

use MailPoet\EmailEditor\Validator\Schema;

// See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#numbers
class Number_Schema extends Schema {
	protected $schema = array(
		'type' => 'number',
	);

	public function minimum( float $value ): self {
		return $this->updateSchemaProperty( 'minimum', $value )
		->unsetSchemaProperty( 'exclusiveMinimum' );
	}

	public function exclusiveMinimum( float $value ): self {
		return $this->updateSchemaProperty( 'minimum', $value )
		->updateSchemaProperty( 'exclusiveMinimum', true );
	}

	public function maximum( float $value ): self {
		return $this->updateSchemaProperty( 'maximum', $value )
		->unsetSchemaProperty( 'exclusiveMaximum' );
	}

	public function exclusiveMaximum( float $value ): self {
		return $this->updateSchemaProperty( 'maximum', $value )
		->updateSchemaProperty( 'exclusiveMaximum', true );
	}

	public function multipleOf( float $value ): self {
		return $this->updateSchemaProperty( 'multipleOf', $value );
	}
}
