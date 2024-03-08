<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

/**
 * This preprocessor is responsible for setting default spacing values for blocks.
 * In the early development phase, we are setting only margin-top for blocks that are not first or last in the columns block.
 */
class SpacingPreprocessor implements Preprocessor {
  public function preprocess(array $parsedBlocks, array $layout, array $styles): array {
    $parsedBlocks = $this->addMarginTopToBlocks($parsedBlocks, $styles);
    return $parsedBlocks;
  }

  private function addMarginTopToBlocks(array $parsedBlocks, array $styles): array {
    $flexGap = $styles['spacing']['blockGap'] ?? '0px';

    foreach ($parsedBlocks as $key => $block) {
      // We don't want to add margin-top to the first block in the email or to the first block in the columns block
      if ($key !== 0) {
        $block['email_attrs']['margin-top'] = $flexGap;
      }

      $block['innerBlocks'] = $this->addMarginTopToBlocks($block['innerBlocks'] ?? [], $styles);

      $parsedBlocks[$key] = $block;
    }

    return $parsedBlocks;
  }
}
