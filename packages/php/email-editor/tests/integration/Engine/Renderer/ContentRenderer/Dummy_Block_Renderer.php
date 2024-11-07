<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer;

use MailPoet\EmailEditor\Engine\Settings_Controller;

class Dummy_Block_Renderer implements Block_Renderer {
  public function render(string $blockContent, array $parsedBlock, Settings_Controller $settingsController): string {
    return $parsedBlock['innerHtml'];
  }
}
