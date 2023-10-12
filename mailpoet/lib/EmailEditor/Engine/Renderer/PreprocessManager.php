<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\CleanupPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TopLevelPreprocessor;

class PreprocessManager {
  /** @var Preprocessor[] */
  private $preprocessors = [];

  public function __construct(
    CleanupPreprocessor $cleanupPreprocessor,
    TopLevelPreprocessor $topLevelPreprocessor
  ) {
    $this->registerPreprocessor($cleanupPreprocessor);
    $this->registerPreprocessor($topLevelPreprocessor);
  }

  /**
   * @param array $parsedBlocks
   * @param array{width: int, background: string, padding: array{bottom: int, left: int, right: int, top: int}} $layoutStyles
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
