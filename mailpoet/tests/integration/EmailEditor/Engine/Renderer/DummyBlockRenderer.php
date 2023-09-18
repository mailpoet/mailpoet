<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\StylesController;

class DummyBlockRenderer implements BlockRenderer {
  public function render($parsedBlock, BlocksRenderer $blocksRenderer, StylesController $stylesController): string {
    if (!isset($parsedBlock['innerBlocks']) || empty($parsedBlock['innerBlocks'])) {
      return $parsedBlock['innerHTML'];
    }
    // Wrapper is rendered in parent Columns block because it needs to operate with columns count etc.
    return '[' . $blocksRenderer->render($parsedBlock['innerBlocks']) . ']';
  }
}
