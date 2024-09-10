<?php declare(strict_types = 1);

namespace EmailEditor\Engine\Renderer\ContentRenderer;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class DummyBlockRenderer implements BlockRenderer {
  public function render(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    return $parsedBlock['innerHtml'];
  }
}
