<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Patterns\Library;

use MailPoet\EmailEditor\Utils\Cdn_Asset_Url;

/**
 * Abstract class for block patterns.
 */
abstract class Abstract_Pattern {
	/**
	 * Cdn_Asset_Url instance.
	 *
	 * @var Cdn_Asset_Url $cdn_asset_url
	 */
	protected $cdn_asset_url;
	/**
	 * List of block types.
	 *
	 * @var array $block_types
	 */
	protected $block_types = array();
	/**
	 * Flag to enable/disable inserter.
	 *
	 * @var bool $inserter
	 */
	protected $inserter = true;
	/**
	 * Source of the pattern.
	 *
	 * @var string $source
	 */
	protected $source = 'plugin';
	/**
	 * List of categories.
	 *
	 * @var array $categories
	 */
	protected $categories = array( 'mailpoet' );
	/**
	 * Viewport width.
	 *
	 * @var int $viewport_width
	 */
	protected $viewport_width = 620;

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
	 * Return properties of the pattern.
	 *
	 * @return array
	 */
	public function get_properties(): array {
		return array(
			'title'         => $this->get_title(),
			'content'       => $this->get_content(),
			'description'   => $this->get_description(),
			'categories'    => $this->categories,
			'inserter'      => $this->inserter,
			'blockTypes'    => $this->block_types,
			'source'        => $this->source,
			'viewportWidth' => $this->viewport_width,
		);
	}

	/**
	 * Get content.
	 *
	 * @return string
	 */
	abstract protected function get_content(): string;

	/**
	 * Get title.
	 *
	 * @return string
	 */
	abstract protected function get_title(): string;

	/**
	 * Get description.
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return '';
	}
}
