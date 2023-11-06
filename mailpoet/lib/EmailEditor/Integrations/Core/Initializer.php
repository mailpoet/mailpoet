<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core;

use MailPoet\EmailEditor\Engine\Renderer\BlocksRegistry;

class Initializer {
  public function initialize(): void {
    add_action('mailpoet_blocks_renderer_initialized', [$this, 'registerCoreBlocksRenderers'], 10, 1);
    add_action('mailpoet_blocks_renderer_uninitialized', [$this, 'unregisterCoreBlocksRenderers'], 10, 1);
  }

  /**
   * Register core blocks email renderers when the blocks renderer is initialized.
   */
  public function registerCoreBlocksRenderers(BlocksRegistry $blocksRegistry): void {
    $blocksRegistry->addBlockRenderer('core/paragraph', new Renderer\Blocks\Paragraph());
    $blocksRegistry->addBlockRenderer('core/heading', new Renderer\Blocks\Heading());
    $blocksRegistry->addBlockRenderer('core/column', new Renderer\Blocks\Column());
    $blocksRegistry->addBlockRenderer('core/columns', new Renderer\Blocks\Columns());
  }

  public function unregisterCoreBlocksRenderers(BlocksRegistry $blocksRegistry): void {
    $blocksRegistry->removeBlockRenderer('core/paragraph');
    $blocksRegistry->removeBlockRenderer('core/heading');
    $blocksRegistry->removeBlockRenderer('core/column');
    $blocksRegistry->removeBlockRenderer('core/columns');
  }
}
