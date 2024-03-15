<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Layout\FlexLayoutRenderer;
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
    // Ignore font size set on the buttons block
    // We rely on TypographyPreprocessor to set the font size on the buttons
    // Rendering font size on the wrapper causes unwanted whitespace below the buttons
    if (isset($parsedBlock['attrs']['style']['typography']['fontSize'])) {
      unset($parsedBlock['attrs']['style']['typography']['fontSize']);
    }
    return $this->flexLayoutRenderer->renderInnerBlocksInLayout($parsedBlock, $settingsController);
  }
}
