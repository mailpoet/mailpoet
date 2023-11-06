<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\SettingsController;

interface BlockRenderer {
  public function render(string $blockContent, array $parsedBlock, SettingsController $settingsController): string;
}
