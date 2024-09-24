<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Validator\Schema;

use MailPoet\EmailEditor\Validator\Schema;

// See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#numbers
class Integer_Schema extends Schema {
	protected $schema = array(
		'type' => 'integer',
	);

	public function minimum( int $value ): self {
		return $this->updateSchemaProperty( 'minimum', $value )
		->unsetSchemaProperty( 'exclusiveMinimum' );
	}

	public function exclusiveMinimum( int $value ): self {
		return $this->updateSchemaProperty( 'minimum', $value )
		->updateSchemaProperty( 'exclusiveMinimum', true );
	}

	public function maximum( int $value ): self {
		return $this->updateSchemaProperty( 'maximum', $value )
		->unsetSchemaProperty( 'exclusiveMaximum' );
	}

	public function exclusiveMaximum( int $value ): self {
		return $this->updateSchemaProperty( 'maximum', $value )
		->updateSchemaProperty( 'exclusiveMaximum', true );
	}

	public function multipleOf( int $value ): self {
		return $this->updateSchemaProperty( 'multipleOf', $value );
	}
}
