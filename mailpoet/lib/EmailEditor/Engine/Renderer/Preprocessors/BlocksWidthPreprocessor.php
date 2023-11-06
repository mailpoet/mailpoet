<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Preprocessors;

/**
 * This class sets the width of the blocks based on the layout width or column count.
 * The final width in pixels is stored in the email_attrs array because we would like to avoid changing the original attributes.
 */
class BlocksWidthPreprocessor implements Preprocessor {

  const BLOCKS_WITHOUT_WIDTH = [
    'core/paragraph',
  ];

  public function preprocess(array $parsedBlocks, array $layoutStyles): array {
    $layoutWidth = $this->parseNumberFromStringWithPixels($layoutStyles['width']);
    // Subtract padding from the width of the layout element
    $layoutWidth -= $this->parseNumberFromStringWithPixels($layoutStyles['padding']['left'] ?? '0px');
    $layoutWidth -= $this->parseNumberFromStringWithPixels($layoutStyles['padding']['right'] ?? '0px');
    foreach ($parsedBlocks as $key => $block) {
      $width = $this->convertWidthToPixels($block['attrs']['width'] ?? '100%', $layoutWidth);

      if ($block['blockName'] === 'core/columns') {
        // Calculate width of the columns based on the layout width and padding
        $columnsWidth = $layoutWidth;
        $columnsWidth -= $this->parseNumberFromStringWithPixels($block['attrs']['style']['spacing']['padding']['left'] ?? '0px');
        $columnsWidth -= $this->parseNumberFromStringWithPixels($block['attrs']['style']['spacing']['padding']['right'] ?? '0px');
        $block['innerBlocks'] = $this->addMissingColumnWidths($block['innerBlocks'], $columnsWidth);
      }

      // Copy layout styles and update width and padding
      $modifiedLayoutStyles = $layoutStyles;
      $modifiedLayoutStyles['width'] = "{$width}px";
      $modifiedLayoutStyles['padding']['left'] = $block['attrs']['style']['spacing']['padding']['left'] ?? '0px';
      $modifiedLayoutStyles['padding']['right'] = $block['attrs']['style']['spacing']['padding']['right'] ?? '0px';

      // Set current block values and reassign it to $parsedBlocks, but don't set width for blocks that are not supposed to have it
      if (!in_array($block['blockName'], self::BLOCKS_WITHOUT_WIDTH, true)) {
        $block['email_attrs']['width'] = "{$width}px";
      }
      $block['innerBlocks'] = $this->preprocess($block['innerBlocks'], $modifiedLayoutStyles);
      $parsedBlocks[$key] = $block;
    }
    return $parsedBlocks;
  }

  // TODO: We could add support for other units like em, rem, etc.
  private function convertWidthToPixels(string $currentWidth, float $layoutWidth): float {
    $width = $layoutWidth;
    if (strpos($currentWidth, '%') !== false) {
      $width = (float)str_replace('%', '', $currentWidth);
      $width = round($width / 100 * $layoutWidth);
    } elseif (strpos($currentWidth, 'px') !== false) {
      $width = $this->parseNumberFromStringWithPixels($currentWidth);
    }

    return $width;
  }

  private function parseNumberFromStringWithPixels(string $string): float {
    return (float)str_replace('px', '', $string);
  }

  private function addMissingColumnWidths(array $columns, float $columnsWidth): array {
    $columnsCountWithDefinedWidth = 0;
    $definedColumnWidth = 0;
    $columnsCount = count($columns);
    foreach ($columns as $column) {
      if (isset($column['attrs']['width']) && !empty($column['attrs']['width'])) {
        $columnsCountWithDefinedWidth++;
        $definedColumnWidth += $this->convertWidthToPixels($column['attrs']['width'], $columnsWidth);
      }
    }

    if ($columnsCount - $columnsCountWithDefinedWidth > 0) {
      $defaultColumnsWidth = round(($columnsWidth - $definedColumnWidth) / ($columnsCount - $columnsCountWithDefinedWidth), 2);
      foreach ($columns as $key => $column) {
        if (!isset($column['attrs']['width']) || empty($column['attrs']['width'])) {
          $columns[$key]['attrs']['width'] = "{$defaultColumnsWidth}px";
        }
      }
    }
    return $columns;
  }
}
