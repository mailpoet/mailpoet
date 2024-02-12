<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\Renderer\Postprocessors\HighlightingPostprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Postprocessors\Postprocessor;
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
    HighlightingPostprocessor $highlightingPostprocessor
  ) {
    $this->registerPreprocessor($cleanupPreprocessor);
    $this->registerPreprocessor($topLevelPreprocessor);
    $this->registerPreprocessor($blocksWidthPreprocessor);
    $this->registerPreprocessor($typographyPreprocessor);
    $this->registerPreprocessor($spacingPreprocessor);
    $this->registerPostprocessor($highlightingPostprocessor);
  }

  /**
   * @param array $parsedBlocks
   * @param array{width: string, background: string, padding: array{bottom: string, left: string, right: string, top: string}} $layoutStyles
   * @return array
   */
  public function preprocess(array $parsedBlocks, array $layoutStyles): array {
    foreach ($this->preprocessors as $preprocessor) {
      $parsedBlocks = $preprocessor->preprocess($parsedBlocks, $layoutStyles);
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
