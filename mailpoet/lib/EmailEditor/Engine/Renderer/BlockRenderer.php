<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

interface BlockRenderer {
  public function render(array $parsedBlock, BlocksRenderer $blocksRenderer): string;
}
