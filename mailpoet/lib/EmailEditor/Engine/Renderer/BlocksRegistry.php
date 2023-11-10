<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\SettingsController;

class BlocksRegistry {

  /** @var BlockRenderer[] */
  private $blockRenderersMap = [];

  /** @var SettingsController */
  private $settingsController;

  public function __construct(
    SettingsController $settingsController
  ) {
    $this->settingsController = $settingsController;
  }

  public function addBlockRenderer(string $blockName, BlockRenderer $renderer): void {
    $this->blockRenderersMap[$blockName] = $renderer;
    add_filter('render_block_' . $blockName, [$this, 'renderBlock'], 10, 2);
  }

  public function getBlockRenderer(string $blockName): ?BlockRenderer {
    return apply_filters('mailpoet_block_renderer_' . $blockName, $this->blockRenderersMap[$blockName] ?? null);
  }

  public function removeAllBlockRendererFilters(): void {
    foreach (array_keys($this->blockRenderersMap) as $blockName) {
      $this->removeBlockRenderer($blockName);
    }
  }

  public function renderBlock($blockContent, $parsedBlock): string {
    // Here we could add a default renderer for blocks that don't have a renderer registered
    if (!isset($this->blockRenderersMap[$parsedBlock['blockName']])) {
      throw new \InvalidArgumentException('Block renderer not found for block ' . $parsedBlock['name']);
    }
    return $this->blockRenderersMap[$parsedBlock['blockName']]->render($blockContent, $parsedBlock, $this->settingsController);
  }

  private function removeBlockRenderer(string $blockName): void {
    unset($this->blockRenderersMap[$blockName]);
    remove_filter('render_block_' . $blockName, [$this, 'renderBlock']);
  }
}
