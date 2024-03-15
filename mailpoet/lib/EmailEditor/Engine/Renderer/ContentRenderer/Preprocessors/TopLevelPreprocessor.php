<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

class TopLevelPreprocessor implements Preprocessor {
  const SINGLE_COLUMN_TEMPLATE = [
    'blockName' => 'core/columns',
    'attrs' => [],
    'innerBlocks' => [[
      'blockName' => 'core/column',
      'attrs' => [],
      'innerBlocks' => [],
    ]],
  ];

  /**
   * In the editor we allow putting content blocks directly into the root level of the email.
   * But for rendering purposes it is more convenient to have them wrapped in a single column.
   * This method walks through the first level of blocks and wraps non column blocks into a single column.
   */
  public function preprocess(array $parsedBlocks, array $layoutStyles): array {
    $wrappedParsedBlocks = [];
    $nonColumnsBlocksBuffer = [];
    foreach ($parsedBlocks as $block) {
      $blockAlignment = $block['attrs']['align'] ?? null;
      // The next block is columns so we can flush the buffer and add the columns block
      if ($block['blockName'] === 'core/columns' || $blockAlignment === 'full') {
        if ($nonColumnsBlocksBuffer) {
          $columnsBlock = self::SINGLE_COLUMN_TEMPLATE;
          $columnsBlock['innerBlocks'][0]['innerBlocks'] = $nonColumnsBlocksBuffer;
          $nonColumnsBlocksBuffer = [];
          $wrappedParsedBlocks[] = $columnsBlock;
        }
        // If the block is full width and is not core/columns, we need to wrap it in a single column block, and it the columns block has to contain only the block
        if ($blockAlignment === 'full' && $block['blockName'] !== 'core/columns') {
          $columnsBlock = self::SINGLE_COLUMN_TEMPLATE;
          $columnsBlock['attrs']['align'] = 'full';
          $columnsBlock['innerBlocks'][0]['innerBlocks'] = [$block];
          $wrappedParsedBlocks[] = $columnsBlock;
          continue;
        }
        $wrappedParsedBlocks[] = $block;
        continue;
      }
      // Non columns block so we add it to the buffer
      $nonColumnsBlocksBuffer[] = $block;
    }
    // Flush the buffer if there are any blocks left
    if ($nonColumnsBlocksBuffer) {
      $columnsBlock = self::SINGLE_COLUMN_TEMPLATE;
      $columnsBlock['innerBlocks'][0]['innerBlocks'] = $nonColumnsBlocksBuffer;
      $wrappedParsedBlocks[] = $columnsBlock;
    }
    return $wrappedParsedBlocks;
  }
}
