<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Highlighting_Postprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Postprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Variables_Postprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Blocks_Width_Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Cleanup_Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Spacing_Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Typography_Preprocessor;

class Process_Manager {
	/** @var Preprocessor[] */
	private $preprocessors = array();

	/** @var Postprocessor[] */
	private $postprocessors = array();

	public function __construct(
		Cleanup_Preprocessor $cleanupPreprocessor,
		Blocks_Width_Preprocessor $blocksWidthPreprocessor,
		Typography_Preprocessor $typographyPreprocessor,
		Spacing_Preprocessor $spacingPreprocessor,
		Highlighting_Postprocessor $highlightingPostprocessor,
		Variables_Postprocessor $variablesPostprocessor
	) {
		$this->registerPreprocessor( $cleanupPreprocessor );
		$this->registerPreprocessor( $blocksWidthPreprocessor );
		$this->registerPreprocessor( $typographyPreprocessor );
		$this->registerPreprocessor( $spacingPreprocessor );
		$this->registerPostprocessor( $highlightingPostprocessor );
		$this->registerPostprocessor( $variablesPostprocessor );
	}

	/**
	 * @param array                                                                                                             $parsedBlocks
	 * @param array{contentSize: string}                                                                                        $layout
	 * @param array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles
	 * @return array
	 */
	public function preprocess( array $parsedBlocks, array $layout, array $styles ): array {
		foreach ( $this->preprocessors as $preprocessor ) {
			$parsedBlocks = $preprocessor->preprocess( $parsedBlocks, $layout, $styles );
		}
		return $parsedBlocks;
	}

	public function postprocess( string $html ): string {
		foreach ( $this->postprocessors as $postprocessor ) {
			$html = $postprocessor->postprocess( $html );
		}
		return $html;
	}

	public function registerPreprocessor( Preprocessor $preprocessor ): void {
		$this->preprocessors[] = $preprocessor;
	}

	public function registerPostprocessor( Postprocessor $postprocessor ): void {
		$this->postprocessors[] = $postprocessor;
	}
}
