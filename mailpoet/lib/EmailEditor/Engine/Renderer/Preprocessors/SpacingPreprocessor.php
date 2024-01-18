<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\SettingsController;

/**
 * This preprocessor is responsible for setting default spacing values for blocks.
 * In the early development phase, we are setting only margin-top for blocks that are not first or last in the columns block.
 */
class SpacingPreprocessor implements Preprocessor {
  public function preprocess(array $parsedBlocks, array $layoutStyles): array {
    $parsedBlocks = $this->addMarginTopToBlocks($parsedBlocks);
    return $parsedBlocks;
  }

  private function addMarginTopToBlocks(array $parsedBlocks): array {
    foreach ($parsedBlocks as $key => $block) {
      // We don't want to add margin-top to column block
      if ($key !== 0 && $block['blockName'] !== 'core/column') {
        $block['email_attrs']['margin-top'] = SettingsController::FLEX_GAP;
      }

      $block['innerBlocks'] = $this->addMarginTopToBlocks($block['innerBlocks'] ?? []);

      $parsedBlocks[$key] = $block;
    }

    return $parsedBlocks;
  }
}
