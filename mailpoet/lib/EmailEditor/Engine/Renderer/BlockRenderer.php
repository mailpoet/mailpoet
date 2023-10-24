<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

interface BlockRenderer {
  public function render(string $blockContent, array $parsedBlock): string;
}
