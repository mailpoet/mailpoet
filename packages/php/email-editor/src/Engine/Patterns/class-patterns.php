<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Patterns;

use MailPoet\EmailEditor\Utils\Cdn_Asset_Url;

/**
 * Register block patterns.
 */
class Patterns {
	/**
	 * Namespace for block patterns.
	 *
	 * @var string $namespace
	 */
	private $namespace = 'mailpoet';
	/**
	 * Cdn_Asset_Url instance.
	 *
	 * @var Cdn_Asset_Url $cdn_asset_url
	 */
	protected $cdn_asset_url;

	/**
	 * Constructor.
	 *
	 * @param Cdn_Asset_Url $cdn_asset_url Cdn_Asset_Url instance.
	 */
	public function __construct(
		Cdn_Asset_Url $cdn_asset_url
	) {
		$this->cdn_asset_url = $cdn_asset_url;
	}

	/**
	 * Initialize block patterns.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->register_block_pattern_category();
		$this->register_patterns();
	}

	/**
	 * Register block pattern category.
	 *
	 * @return void
	 */
	private function register_block_pattern_category(): void {
		register_block_pattern_category(
			'mailpoet',
			array(
				'label'       => _x( 'MailPoet', 'Block pattern category', 'mailpoet' ),
				'description' => __( 'A collection of email template layouts.', 'mailpoet' ),
			)
		);
	}

	/**
	 * Register block patterns.
	 *
	 * @return void
	 */
	private function register_patterns() {
		$this->register_pattern( 'default', new Library\Default_Content( $this->cdn_asset_url ) );
		$this->register_pattern( 'default-full', new Library\Default_Content_Full( $this->cdn_asset_url ) );
	}

	/**
	 * Register block pattern.
	 *
	 * @param string                   $name Name of the pattern.
	 * @param Library\Abstract_Pattern $pattern Pattern to register.
	 */
	private function register_pattern( $name, $pattern ) {
		register_block_pattern( $this->namespace . '/' . $name, $pattern->get_properties() );
	}
}
