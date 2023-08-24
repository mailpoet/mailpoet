<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\Renderer\BlocksRenderer;

class Heading implements BlockRenderer {
  public function render($parsedBlock, BlocksRenderer $blocksRenderer): string {
    return $parsedBlock['innerHTML'] ?? '';
  }
}
