<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\BlocksWidthPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\CleanupPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\SpacingPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TopLevelPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TypographyPreprocessor;

class PreprocessManager {
  /** @var Preprocessor[] */
  private $preprocessors = [];

  public function __construct(
    CleanupPreprocessor $cleanupPreprocessor,
    TopLevelPreprocessor $topLevelPreprocessor,
    BlocksWidthPreprocessor $blocksWidthPreprocessor,
    TypographyPreprocessor $typographyPreprocessor,
    SpacingPreprocessor $spacingPreprocessor
  ) {
    $this->registerPreprocessor($cleanupPreprocessor);
    $this->registerPreprocessor($topLevelPreprocessor);
    $this->registerPreprocessor($blocksWidthPreprocessor);
    $this->registerPreprocessor($typographyPreprocessor);
    $this->registerPreprocessor($spacingPreprocessor);
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

  public function registerPreprocessor(Preprocessor $preprocessor): void {
    $this->preprocessors[] = $preprocessor;
  }
}
