<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare( strict_types = 1 );
namespace MailPoet\EmailEditor\Validator\Schema;

use MailPoet\EmailEditor\Validator\Schema;

/**
 * Represents a schema for a boolean.
 * See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types
 */
class Boolean_Schema extends Schema {
	/**
	 * Schema definition.
	 *
	 * @var array
	 */
	protected $schema = array(
		'type' => 'boolean',
	);
}
