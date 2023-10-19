<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\SettingsController;

interface BlockRenderer {
  public function render(array $parsedBlock, BlocksRenderer $blocksRenderer, SettingsController $settingsController): string;
}
