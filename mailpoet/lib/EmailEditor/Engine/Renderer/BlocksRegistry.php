<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

class BlocksRegistry {

  /** @var BlockRenderer[] */
  private $blockRenderersMap = [];

  public function addBlockRenderer(string $blockName, BlockRenderer $renderer): void {
    $this->blockRenderersMap[$blockName] = $renderer;
    add_filter('render_block_' . $blockName, [$renderer, 'render'], 10, 2);
  }

  public function removeBlockRenderer(string $blockName, BlockRenderer $renderer): void {
    unset($this->blockRenderersMap[$blockName]);
    remove_filter('render_block_' . $blockName, [$renderer, 'render']);
  }

  public function getBlockRenderer(string $blockName): ?BlockRenderer {
    return apply_filters('mailpoet_block_renderer_' . $blockName, $this->blockRenderersMap[$blockName] ?? null);
  }
}
