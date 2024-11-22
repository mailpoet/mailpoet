<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\PersonalizationTags;

use WP_HTML_Text_Replacement;

/**
 * Integration test for HTML_Tag_Processor class which tests the token replacement.
 */
class HTMLTagProcessorTest extends \MailPoetTest {

	/**
	 * Test replacing a token and deferring updates.
	 */
	public function testReplaceToken(): void {
		// Example HTML content to process.
		$html_content = '<div>Hello!</div>';

		// Instantiate the HTML_Tag_Processor with the HTML content.
		$processor = new HTML_Tag_Processor( $html_content );
		$processor->next_token();
		// Replace the token.
		$processor->replace_token( 'John!' );

		// Verify deferred updates.
		$deferred_updates = $this->getPrivateProperty( $processor, 'deferred_updates' );

		$this->assertCount( 1, $deferred_updates );
		$this->assertInstanceOf( WP_HTML_Text_Replacement::class, $deferred_updates[0] );
		$this->assertSame( 0, $deferred_updates[0]->start );
		$this->assertSame( 5, $deferred_updates[0]->length );
		$this->assertSame( 'John!', $deferred_updates[0]->text );
	}

	/**
	 * Test flushing updates.
	 */
	public function testFlushUpdates(): void {
		// Example HTML content to process.
		$html_content = '<div>Hello!</div>';

		// Instantiate the HTML_Tag_Processor with the HTML content.
		$processor = new HTML_Tag_Processor( $html_content );

		// Mock deferred updates.
		$this->setPrivateProperty(
			$processor,
			'deferred_updates',
			array(
				new WP_HTML_Text_Replacement( 0, 3, 'Hi!' ),
			)
		);

		// Flush the updates.
		$processor->flush_updates();

		// Verify lexical updates.
		$lexical_updates = $this->getPrivateProperty( $processor, 'lexical_updates' );

		$this->assertCount( 1, $lexical_updates );
		$this->assertSame( 'Hi!', $lexical_updates[0]->text );

		// Verify deferred updates are cleared.
		$deferred_updates = $this->getPrivateProperty( $processor, 'deferred_updates' );
		$this->assertEmpty( $deferred_updates );
	}

	/**
	 * Helper method to access private properties via reflection.
	 *
	 * @param object $instance The object instance.
	 * @param string $property The property name.
	 * @return mixed The property value.
	 */
	private function getPrivateProperty( object $instance, string $property ) {
		$reflection = new \ReflectionClass( $instance );
		$prop       = $reflection->getProperty( $property );
		$prop->setAccessible( true );
		return $prop->getValue( $instance );
	}

	/**
	 * Helper method to set private properties via reflection.
	 *
	 * @param object $instance The object instance.
	 * @param string $property The property name.
	 * @param mixed  $value The value to set.
	 * @return void
	 */
	private function setPrivateProperty( object $instance, string $property, $value ): void {
		$reflection = new \ReflectionClass( $instance );
		$prop       = $reflection->getProperty( $property );
		$prop->setAccessible( true );
		$prop->setValue( $instance, $value );
	}
}
