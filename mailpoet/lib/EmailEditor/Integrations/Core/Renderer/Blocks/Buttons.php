<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\Renderer\Layout\FlexLayoutRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class Buttons implements BlockRenderer {
  /** @var FlexLayoutRenderer */
  private $flexLayoutRenderer;

  public function __construct(
    FlexLayoutRenderer $flexLayoutRenderer
  ) {
    $this->flexLayoutRenderer = $flexLayoutRenderer;
  }

  public function render($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $contentStyles = $settingsController->getEmailContentStyles();
    $typography = $parsedBlock['attrs']['style']['typography'] ?? [];
    $typography['fontSize'] = $typography['fontSize'] ?? $contentStyles['typography']['fontSize'];
    $parsedBlock['attrs']['style']['typography'] = $typography;
    return $this->flexLayoutRenderer->renderInnerBlocksInLayout($parsedBlock, $settingsController);
  }
}
