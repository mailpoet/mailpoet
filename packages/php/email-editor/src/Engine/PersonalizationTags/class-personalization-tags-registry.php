<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\PersonalizationTags;

/**
 * Registry for personalization tags.
 */
class Personalization_Tags_Registry {

	/**
	 * List of registered personalization tags.
	 *
	 * @var Personalization_Tag[]
	 */
	private $tags = array();

	/**
	 * Initialize the personalization tags registry.
	 *
	 * @return void
	 */
	public function initialize(): void {
		apply_filters( 'mailpoet_email_editor_register_personalization_tags', $this );
	}

	/**
	 * Register a new personalization tag.
	 *
	 * @param Personalization_Tag $tag The personalization tag to register.
	 * @return void
	 */
	public function register( Personalization_Tag $tag ): void {
		if ( isset( $this->tags[ $tag->get_token() ] ) ) {
			return;
		}

		$this->tags[ $tag->get_token() ] = $tag;
	}

	/**
	 * Retrieve a personalization tag by its tag.
	 *
	 * @param string $token The token of the personalization tag.
	 * @return Personalization_Tag|null The array data or null if not found.
	 */
	public function get_by_token( string $token ): ?Personalization_Tag {
		return $this->tags[ $token ] ?? null;
	}

	/**
	 * Retrieve all registered personalization tags.
	 *
	 * @return array List of all registered personalization tags.
	 */
	public function get_all() {
		return $this->tags;
	}
}
