<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\Renderer\BlocksRenderer;

class Paragraph implements BlockRenderer {
  public function render($pasedBlock, BlocksRenderer $blocksRenderer): string {
    return $pasedBlock['innerHTML'] ?? '';
  }
}
