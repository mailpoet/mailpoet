<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\Renderer\BlocksRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class Paragraph implements BlockRenderer {
  public function render($parsedBlock, BlocksRenderer $blocksRenderer, StylesController $stylesController): string {
    return $parsedBlock['innerHTML'] ?? '';
  }
}
