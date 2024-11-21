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
	 * @var array
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
	 * @param string   $name        Unique identifier for the callback.
	 * @param string   $tag         The tag to be used in the email content.
	 * @param string   $category    The category of the personalization tag.
	 * @param callable $callback    The callable function/method.
	 * @param array    $attributes  Additional data or settings for the callback (optional).
	 * @return void
	 */
	public function register( string $name, string $tag, string $category, callable $callback, array $attributes = array() ): void {
		if ( isset( $this->tags[ $tag ] ) ) {
			return;
		}

		$this->tags[ $tag ] = array(
			'tag'        => $tag,
			'name'       => $name,
			'category'   => $category,
			'callback'   => $callback,
			'attributes' => $attributes,
		);
	}

	/**
	 * Retrieve a personalization tag by its tag.
	 *
	 * @param string $tag The tag of the personalization tag.
	 * @return array|null The array data or null if not found.
	 */
	public function get_by_tag( string $tag ): ?array {
		return $this->tags[ $tag ] ?? null;
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
