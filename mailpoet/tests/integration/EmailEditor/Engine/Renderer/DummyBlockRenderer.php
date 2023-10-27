<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

class DummyBlockRenderer implements BlockRenderer {
  public function render(string $blockContent, array $parsedBlock): string {
    if (!isset($parsedBlock['innerBlocks']) || empty($parsedBlock['innerBlocks'])) {
      return $parsedBlock['innerHTML'];
    }
    // Wrapper is rendered in parent Columns block because it needs to operate with columns count etc.
    return '[' . $this->render('', $parsedBlock['innerBlocks']) . ']';
  }
}
