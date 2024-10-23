<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

/**
 * This preprocessor is responsible for setting default spacing values for blocks.
 * In the early development phase, we are setting only margin-top for blocks that are not first or last in the columns block.
 */
class SpacingPreprocessor implements Preprocessor {
  public function preprocess(array $parsedBlocks, array $layout, array $styles): array {
    $parsedBlocks = $this->addBlockGaps($parsedBlocks, $styles['spacing']['blockGap'] ?? '', null);
    return $parsedBlocks;
  }

  private function addBlockGaps(array $parsedBlocks, string $gap = '', $parentBlock = null): array {
    foreach ($parsedBlocks as $key => $block) {
      $parentBlockName = $parentBlock['blockName'] ?? '';
      // Ensure that email_attrs are set
      $block['email_attrs'] = $block['email_attrs'] ?? [];
      /**
       * Do not add a gap to:
       * - the top level blocks - they are post-content, and header and footer wrappers and we don't want a gap between those
       * - first child
       * - parent block is a buttons block (where buttons are side by side).
       **/
      if ($parentBlock && $key !== 0 && $gap && $parentBlockName !== 'core/buttons') {
        $block['email_attrs']['margin-top'] = $gap;
      }

      $block['innerBlocks'] = $this->addBlockGaps($block['innerBlocks'] ?? [], $gap, $block);
      $parsedBlocks[$key] = $block;
    }

    return $parsedBlocks;
  }
}
