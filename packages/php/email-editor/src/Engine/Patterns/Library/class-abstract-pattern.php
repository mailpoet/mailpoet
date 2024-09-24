<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Patterns\Library;

use MailPoet\EmailEditor\Utils\Cdn_Asset_Url;

abstract class Abstract_Pattern {
	protected $cdnAssetUrl;
	protected $blockTypes    = array();
	protected $templateTypes = array();
	protected $inserter      = true;
	protected $source        = 'plugin';
	protected $categories    = array( 'mailpoet' );
	protected $viewportWidth = 620;

	public function __construct(
		Cdn_Asset_Url $cdnAssetUrl
	) {
		$this->cdnAssetUrl = $cdnAssetUrl;
	}

	public function getProperties() {
		return array(
			'title'         => $this->getTitle(),
			'content'       => $this->getContent(),
			'description'   => $this->getDescription(),
			'categories'    => $this->categories,
			'inserter'      => $this->inserter,
			'blockTypes'    => $this->blockTypes,
			'templateTypes' => $this->templateTypes,
			'source'        => $this->source,
			'viewportWidth' => $this->viewportWidth,
		);
	}

	abstract protected function getContent(): string;

	abstract protected function getTitle(): string;

	protected function getDescription(): string {
		return '';
	}
}
