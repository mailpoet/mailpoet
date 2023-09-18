<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\StylesController;

class BlocksRenderer {

  /** @var BlocksRegistry */
  private $blockRenderersRegistry;

  /** @var StylesController */
  private $stylesController;

  /** @var bool */
  private $blocksInitialized = false;

  public function __construct(
    BlocksRegistry $blockRenderersRegistry,
    StylesController $stylesController
  ) {
    $this->blockRenderersRegistry = $blockRenderersRegistry;
    $this->stylesController = $stylesController;
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
      $content .= $blockRenderer->render($parsedBlock, $this, $this->stylesController);
    }
    return $content;
  }
}
