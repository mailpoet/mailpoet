<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

class BlocksRenderer {

  /** @var BlocksRegistry */
  private $blockRenderersRegistry;

  /** @var bool */
  private $blocksInitialized = false;

  public function __construct(
    BlocksRegistry $blockRenderersRegistry
  ) {
    $this->blockRenderersRegistry = $blockRenderersRegistry;
  }

  public function render(array $parsedBlocks): string {
    if (!$this->blocksInitialized) {
      $this->blocksInitialized = true;
      do_action('mailpoet_blocks_renderer_initialized', $this->blockRenderersRegistry);
    }

    $content = '';
    foreach ($parsedBlocks as $parsedBlock) {
      $blockRenderer = $this->blockRenderersRegistry->getBlockRenderer($parsedBlock['blockName'] ?? '');
      if (!$blockRenderer) {
        continue;
      }
      $content .= $blockRenderer->render($parsedBlock, $this);
    }
    return $content;
  }
}
