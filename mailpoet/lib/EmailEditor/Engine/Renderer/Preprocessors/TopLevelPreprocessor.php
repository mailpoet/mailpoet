<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Preprocessors;

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
  public function preprocess(array $parsedBlocks): array {
    $wrappedParsedBlocks = [];
    $nonColumnsBlocksBuffer = [];
    foreach ($parsedBlocks as $block) {
      // The next block is columns so we can flush the buffer and add the columns block
      if ($block['blockName'] === 'core/columns') {
        if ($nonColumnsBlocksBuffer) {
          $columnsBlock = self::SINGLE_COLUMN_TEMPLATE;
          $columnsBlock['innerBlocks'][0]['innerBlocks'] = $nonColumnsBlocksBuffer;
          $nonColumnsBlocksBuffer = [];
          $wrappedParsedBlocks[] = $columnsBlock;
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
