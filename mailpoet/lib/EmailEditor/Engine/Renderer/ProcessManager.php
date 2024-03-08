<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\Renderer\Postprocessors\HighlightingPostprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Postprocessors\Postprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Postprocessors\VariablesPostprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\BlocksWidthPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\CleanupPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\SpacingPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TopLevelPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TypographyPreprocessor;

class ProcessManager {
  /** @var Preprocessor[] */
  private $preprocessors = [];

  /** @var Postprocessor[] */
  private $postprocessors = [];

  public function __construct(
    CleanupPreprocessor $cleanupPreprocessor,
    TopLevelPreprocessor $topLevelPreprocessor,
    BlocksWidthPreprocessor $blocksWidthPreprocessor,
    TypographyPreprocessor $typographyPreprocessor,
    SpacingPreprocessor $spacingPreprocessor,
    HighlightingPostprocessor $highlightingPostprocessor,
    VariablesPostprocessor $variablesPostprocessor
  ) {
    $this->registerPreprocessor($cleanupPreprocessor);
    $this->registerPreprocessor($topLevelPreprocessor);
    $this->registerPreprocessor($blocksWidthPreprocessor);
    $this->registerPreprocessor($typographyPreprocessor);
    $this->registerPreprocessor($spacingPreprocessor);
    $this->registerPostprocessor($highlightingPostprocessor);
    $this->registerPostprocessor($variablesPostprocessor);
  }

  /**
   * @param array $parsedBlocks
   * @param array{contentSize: string} $layout
   * @param array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles
   * @return array
   */
  public function preprocess(array $parsedBlocks, array $layout, array $styles): array {
    foreach ($this->preprocessors as $preprocessor) {
      $parsedBlocks = $preprocessor->preprocess($parsedBlocks, $layout, $styles);
    }
    return $parsedBlocks;
  }

  public function postprocess(string $html): string {
    foreach ($this->postprocessors as $postprocessor) {
      $html = $postprocessor->postprocess($html);
    }
    return $html;
  }

  public function registerPreprocessor(Preprocessor $preprocessor): void {
    $this->preprocessors[] = $preprocessor;
  }

  public function registerPostprocessor(Postprocessor $postprocessor): void {
    $this->postprocessors[] = $postprocessor;
  }
}
