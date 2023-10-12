<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TopLevelPreprocessor;

class PreprocessManager {
  /** @var Preprocessor[] */
  private $preprocessors = [];

  public function __construct(
    TopLevelPreprocessor $topLevelPreprocessor
  ) {
    $this->registerPreprocessor($topLevelPreprocessor);
  }

  /**
   * @param array $parsedBlocks
   * @return array
   */
  public function preprocess(array $parsedBlocks): array {
    foreach ($this->preprocessors as $preprocessor) {
      $parsedBlocks = $preprocessor->preprocess($parsedBlocks);
    }
    return $parsedBlocks;
  }

  public function registerPreprocessor(Preprocessor $preprocessor): void {
    $this->preprocessors[] = $preprocessor;
  }
}
