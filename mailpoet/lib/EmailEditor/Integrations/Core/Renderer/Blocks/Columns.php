<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\Renderer\BlocksRenderer;

class Columns implements BlockRenderer {
  public function render($parsedBlock, BlocksRenderer $blocksRenderer): string {
    if (!isset($parsedBlock['innerBlocks']) || empty($parsedBlock['innerBlocks'])) {
      return '';
    }
    return "<tr>{$blocksRenderer->render($parsedBlock['innerBlocks'])}</tr>";
  }
}
