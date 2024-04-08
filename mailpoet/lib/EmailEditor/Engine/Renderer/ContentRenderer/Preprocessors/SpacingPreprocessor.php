<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

/**
 * This preprocessor is responsible for setting default spacing values for blocks.
 * In the early development phase, we are setting only margin-top for blocks that are not first or last in the columns block.
 */
class SpacingPreprocessor implements Preprocessor {
  public function preprocess(array $parsedBlocks, array $layout, array $styles): array {
    $parsedBlocks = $this->addBlockGaps($parsedBlocks, $styles['spacing']['blockGap'] ?? '');
    $parsedBlocks = $this->addBlockPadding(
      $parsedBlocks,
      $styles['spacing']['padding']['left'] ?? '',
      $styles['spacing']['padding']['right'] ?? '',
    );

    return $parsedBlocks;
  }

  private function addBlockGaps(array $parsedBlocks, string $gap = ''): array {
    foreach ($parsedBlocks as $key => $block) {
      if ($key !== 0 && $gap) {
        $block['email_attrs']['margin-top'] = $gap;
      }
      $block['innerBlocks'] = $this->addBlockGaps($block['innerBlocks'] ?? [], $gap);
      $parsedBlocks[$key] = $block;
    }

    return $parsedBlocks;
  }

  /**
   * Add padding to child blocks. Blocks that are not aligned full width will use this value.
   */
  private function addBlockPadding(array $parsedBlocks, string $parentPaddingLeft, string $parentPaddingRight): array {
    foreach ($parsedBlocks as $key => $block) {
      $align = $block['attrs']['align'] ?? '';

      if ($align !== 'full') {
        $block['email_attrs']['padding-left'] = $parentPaddingLeft;
        $block['email_attrs']['padding-right'] = $parentPaddingRight;
      }

      $block['innerBlocks'] = $this->addBlockPadding(
        $block['innerBlocks'] ?? [],
        $block['attrs']['style']['spacing']['padding']['padding-left'] ?? '',
        $block['attrs']['style']['spacing']['padding']['padding-right'] ?? ''
      );

      $parsedBlocks[$key] = $block;
    }

    return $parsedBlocks;
  }
}
