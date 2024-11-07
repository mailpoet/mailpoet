<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer;

class Blocks_Registry {

  /** @var Block_Renderer[] */
  private $blockRenderersMap = [];
  /** @var BlockRenderer */
  private $fallbackRenderer = null;

  public function addBlockRenderer(string $blockName, Block_Renderer $renderer): void {
    $this->blockRenderersMap[$blockName] = $renderer;
  }

  public function addFallbackRenderer(BlockRenderer $renderer): void {
    $this->fallbackRenderer = $renderer;
  }

  public function hasBlockRenderer(string $blockName): bool {
    return isset($this->blockRenderersMap[$blockName]);
  }

  public function getBlockRenderer(string $blockName): ?Block_Renderer {
    return $this->blockRenderersMap[$blockName] ?? null;
  }

  public function getFallbackRenderer(): ?BlockRenderer {
    return $this->fallbackRenderer;
  }

  public function removeAllBlockRenderers(): void {
    foreach (array_keys($this->blockRenderersMap) as $blockName) {
      $this->removeBlockRenderer($blockName);
    }
  }

  private function removeBlockRenderer(string $blockName): void {
    unset($this->blockRenderersMap[$blockName]);
  }
}
