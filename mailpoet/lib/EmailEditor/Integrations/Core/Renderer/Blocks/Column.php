<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\Renderer\BlocksRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class Column implements BlockRenderer {
  public function render($parsedBlock, BlocksRenderer $blocksRenderer, StylesController $stylesController): string {
    if (!isset($parsedBlock['innerBlocks']) || empty($parsedBlock['innerBlocks'])) {
      return '';
    }
    // Wrapper is rendered in parent Columns block because it needs to operate with columns count etc.
    return $blocksRenderer->render($parsedBlock['innerBlocks']);
  }
}
