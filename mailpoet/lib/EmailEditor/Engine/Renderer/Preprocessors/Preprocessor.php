<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Preprocessors;

interface Preprocessor {
  public function preprocess(array $parsedBlocks, array $layoutStyles): array;
}
