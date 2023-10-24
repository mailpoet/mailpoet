<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;

class Heading implements BlockRenderer {
  public function render($blockContent, array $parsedBlock): string {
    return $blockContent;
  }
}
