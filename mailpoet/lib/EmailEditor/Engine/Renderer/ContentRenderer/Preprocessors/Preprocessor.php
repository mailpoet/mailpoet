<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

interface Preprocessor {
  public function preprocess(array $parsedBlocks, array $layoutStyles): array;
}
