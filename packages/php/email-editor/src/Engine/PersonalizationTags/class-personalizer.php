<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\PersonalizationTags;

/**
 * Class for replacing personalization tags with their values in the email content.
 */
class Personalizer {

	/**
	 * Personalization tags registry.
	 *
	 * @var Personalization_Tags_Registry
	 */
	private Personalization_Tags_Registry $tags_registry;

	/**
	 * Context for the personalization tags.
	 *
	 * @var array
	 */
	private array $context;

	/**
	 * Class constructor with required dependencies.
	 *
	 * @param Personalization_Tags_Registry $tags_registry Personalization tags registry.
	 */
	public function __construct( Personalization_Tags_Registry $tags_registry ) {
		$this->tags_registry = $tags_registry;
		$this->context       = array();
	}

	/**
	 * Set the context for the personalization.
	 *
	 * @param array $context The context to set.
	 */
	public function set_context( array $context ) {
		$this->context = $context;
	}

	/**
	 * Personalize the content by replacing the personalization tags with their values.
	 *
	 * @param string $content The content to personalize.
	 * @return string The personalized content.
	 */
	public function personalize_content( string $content ): string {
		$content_processor = new HTML_Tag_Processor( $content );
		while ( $content_processor->next_token() ) {
			if ( $content_processor->get_token_type() === '#comment' ) {
				$token = $this->parse_token( $content_processor->get_modifiable_text() );
				$tag   = $this->tags_registry->get_by_tag( $token['tag'] );
				if ( ! $tag || ! isset( $tag['callback'] ) ) {
					continue;
				}

				$value = call_user_func( $tag['callback'], ...array_merge( array( $this->context ), array( 'args' => $token ['arguments'] ) ) );
				$content_processor->replace_token( $value );

			} elseif ( $content_processor->get_token_type() === '#tag' && $content_processor->get_tag() === 'TITLE' ) {
				// The title tag contains the subject of the email which should be personalized. HTML_Tag_Processor does parse the header tags.
				$title = $this->personalize_content( $content_processor->get_modifiable_text() );
				$content_processor->set_modifiable_text( $title );
			}
		}

		$content_processor->flush_updates();
		return $content_processor->get_updated_html();
	}

	/**
	 * Parse a personalization tag token.
	 *
	 * @param string $token The token to parse.
	 * @return array{tag: string, attributes: array} The parsed token.
	 */
	private function parse_token( string $token ): array {
		$result = array(
			'tag'       => '',
			'arguments' => array(),
		);

		// Step 1: Separate the tag and attributes.
		if ( preg_match( '/^([a-zA-Z0-9\-\/]+)\s*(.*)$/', trim( $token ), $matches ) ) {
			$result['tag']     = $matches[1]; // The tag part (e.g., "mailpoet/subscriber-firstname").
			$attributes_string = $matches[2]; // The attributes part (e.g., 'default="subscriber"').

			// Step 2: Extract attributes from the attribute string.
			if ( preg_match_all( '/(\w+)=["\']([^"\']+)["\']/', $attributes_string, $attribute_matches, PREG_SET_ORDER ) ) {
				foreach ( $attribute_matches as $attribute ) {
					$result['arguments'][ $attribute[1] ] = $attribute[2];
				}
			}
		}

		return $result;
	}
}
