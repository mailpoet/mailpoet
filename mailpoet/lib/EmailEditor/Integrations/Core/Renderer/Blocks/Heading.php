<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\Renderer\BlocksRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class Heading implements BlockRenderer {
  public function render($parsedBlock, BlocksRenderer $blocksRenderer, SettingsController $settingsController): string {
    return $parsedBlock['innerHTML'] ?? '';
  }
}
