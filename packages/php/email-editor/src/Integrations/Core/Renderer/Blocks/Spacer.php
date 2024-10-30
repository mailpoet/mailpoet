<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\SettingsController;

class Spacer extends AbstractBlockRenderer {

  protected function renderContent($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    return $blockContent;
  }
}
