<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\SettingsController;

/**
 * Fallback block renderer.
 * This renderer is used when no specific renderer is found for a block.
 *
 * AbstractBlockRenderer applies some adjustments to the block content, like adding spacers.
 * By using fallback renderer for all blocks we apply there adjustments to all blocks that don't have any renderer.
 *
 * We need to find a better abstraction/architecture for this.
 */
class Fallback extends AbstractBlockRenderer {
  protected function renderContent($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    return $blockContent;
  }
}
