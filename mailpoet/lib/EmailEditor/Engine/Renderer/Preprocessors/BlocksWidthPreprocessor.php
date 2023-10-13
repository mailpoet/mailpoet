<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Preprocessors;

/**
 * This class sets the width of the blocks based on the layout width or column count.
 * The final width in pixels is stored in the email_attrs array because we would like to avoid changing the original attributes.
 */
class BlocksWidthPreprocessor implements Preprocessor {
  public function preprocess(array $parsedBlocks, array $layoutStyles): array {
    $layoutWidth = $layoutStyles['width'];
    // Subtract padding from the width of the layout element
    $layoutWidth -= $layoutStyles['padding']['left'] ?? 0;
    $layoutWidth -= $layoutStyles['padding']['right'] ?? 0;
    foreach ($parsedBlocks as $key => $block) {
      $width = $this->convertWithToPixels($block['attrs']['width'] ?? '100%', $layoutWidth);

      if ($block['blockName'] === 'core/columns') {
        $block['innerBlocks'] = $this->addMissingColumnWidths($block['innerBlocks']);
      }

      // Copy layout styles and update width and padding
      $modifiedLayoutStyles = $layoutStyles;
      $modifiedLayoutStyles['width'] = $width;
      $modifiedLayoutStyles['padding']['left'] = $this->parseNumberFromStringWithPixels($block['attrs']['style']['spacing']['padding']['left'] ?? '0px');
      $modifiedLayoutStyles['padding']['right'] = $this->parseNumberFromStringWithPixels($block['attrs']['style']['spacing']['padding']['right'] ?? '0px');

      // Set current block values and reassign it to $parsedBlocks
      $block['email_attrs']['width'] = $width;
      $block['innerBlocks'] = $this->preprocess($block['innerBlocks'], $modifiedLayoutStyles);
      $parsedBlocks[$key] = $block;
    }
    return $parsedBlocks;
  }

  // TODO: We could add support for other units like em, rem, etc.
  private function convertWithToPixels(string $currentWidth, float $layoutWidth): float {
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

  private function addMissingColumnWidths(array $columns): array {
    $columnsCount = count($columns);
    $defaultColumnsWidth = round(100 / $columnsCount, 2);

    foreach ($columns as $key => $column) {
      if (!isset($column['attrs']['width'])) {
        $columns[$key]['attrs']['width'] = "{$defaultColumnsWidth}%";
      }
    }
    return $columns;
  }
}
