<?php declare(strict_types = 1);

namespace MailPoet\Util;

class SpreadsheetCellFormatter {
  /**
   * Leading characters that make a spreadsheet application read a cell as a formula
   * rather than as text. Excel also acts on the tab and carriage return variants.
   */
  private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

  private const TEXT_PREFIX = "'";

  /**
   * Only strings are guarded, so a value MailPoet computed as a number stays a number.
   *
   * @param int|string|float|null $value
   * @return int|string|float|null
   */
  public static function format($value) {
    if (!is_string($value) || $value === '' || !in_array($value[0], self::FORMULA_TRIGGERS, true)) {
      return $value;
    }
    return self::TEXT_PREFIX . $value;
  }

  /**
   * Reverses format(), so reading back a file MailPoet wrote returns the original
   * value. Only a prefix in front of a trigger is removed, leaving a value that
   * genuinely starts with an apostrophe alone.
   *
   * @param int|string|float|null $value
   * @return int|string|float|null
   */
  public static function unformat($value) {
    if (!is_string($value) || strlen($value) < 2 || $value[0] !== self::TEXT_PREFIX) {
      return $value;
    }
    return in_array($value[1], self::FORMULA_TRIGGERS, true) ? substr($value, 1) : $value;
  }

  /**
   * @param array<int|string, int|string|float|null> $row
   * @return array<int|string, int|string|float|null>
   */
  public static function formatRow(array $row): array {
    return array_map([self::class, 'format'], $row);
  }
}
