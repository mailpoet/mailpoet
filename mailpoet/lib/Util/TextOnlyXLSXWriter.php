<?php declare(strict_types = 1);

namespace MailPoet\Util;

use MailPoetVendor\XLSXWriter;
use MailPoetVendor\XLSXWriter_BuffererWriter;

/**
 * The vendored writer stores a string that begins with "=" as a formula element, so a
 * spreadsheet application evaluates it instead of showing it. Exports only ever contain
 * data, never formulas, so route those values into the shared-string table like any
 * other text. Storing them as text this way keeps the cell readable, unlike a prefix
 * character, which the reader would display.
 */
class TextOnlyXLSXWriter extends XLSXWriter {
  /**
   * Mirrors the style map the parent uses, so a redirected cell keeps its formatting.
   */
  private const CELL_STYLES = ['money' => 1, 'dollar' => 1, 'datetime' => 2, 'date' => 3, 'string' => 0];

  /**
   * @param int $rowNumber
   * @param int $columnNumber
   * @param mixed $value
   * @param string $cellFormat
   */
  protected function writeCell(
    XLSXWriter_BuffererWriter &$file,
    $rowNumber,
    $columnNumber,
    $value,
    $cellFormat
  ) {
    if (!is_string($value) || $value === '' || $value[0] !== '=') {
      parent::writeCell($file, $rowNumber, $columnNumber, $value, $cellFormat);
      return;
    }

    $cell = self::xlsCell($rowNumber, $columnNumber);
    $style = self::CELL_STYLES[$cellFormat] ?? 0;
    $file->write(
      '<c r="' . $cell . '" s="' . $style . '" t="s"><v>'
      . self::xmlspecialchars($this->setSharedString($value))
      . '</v></c>'
    );
  }
}
