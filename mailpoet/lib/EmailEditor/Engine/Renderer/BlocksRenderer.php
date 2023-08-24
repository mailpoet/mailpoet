<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

class BlocksRenderer {

  /** @var BlocksRegistry */
  private $blockRenderersRegistry;

  public function __construct(
    BlocksRegistry $blockRenderersRegistry
  ) {
    $this->blockRenderersRegistry = $blockRenderersRegistry;
  }

  public function render(array $parsedBlocks): string {
    $content = '';
    foreach ($parsedBlocks as $parsedBlock) {
      $blockRenderer = $this->blockRenderersRegistry->getBlockRenderer($parsedBlock['type']);
      if (!$blockRenderer) {
        continue;
      }
      $content .= $blockRenderer->render($parsedBlock, $this);
    }
    return $content;
  }
}
