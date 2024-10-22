<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\SettingsController;

class ListItem extends AbstractBlockRenderer {
  /**
   * Override this method to disable spacing (block gap) for list items.
   */
  protected function addSpacer($content, $emailAttrs): string {
    return $content;
  }

  protected function renderContent($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    return $blockContent;
  }
}
