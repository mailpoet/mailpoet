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
    if (!is_string($value) || !self::startsWithTrigger($value)) {
      return $value;
    }
    return self::TEXT_PREFIX . $value;
  }

  /**
   * Reverses format(), so reading back a file MailPoet wrote returns the original
   * value, including one that starts with an apostrophe of its own.
   *
   * @param int|string|float|null $value
   * @return int|string|float|null
   */
  public static function unformat($value) {
    if (!is_string($value) || $value === '' || $value[0] !== self::TEXT_PREFIX || !self::startsWithTrigger($value)) {
      return $value;
    }
    return substr($value, 1);
  }

  /**
   * True when the value, ignoring any apostrophes already in front of it, begins with
   * a trigger. Counting the whole run is what keeps the prefix reversible: a value of
   * "'=x" is written as "''=x", so it stays distinct from "=x" written as "'=x".
   */
  private static function startsWithTrigger(string $value): bool {
    $apostrophes = strspn($value, self::TEXT_PREFIX);
    return isset($value[$apostrophes]) && in_array($value[$apostrophes], self::FORMULA_TRIGGERS, true);
  }

  /**
   * @param array<int|string, int|string|float|null> $row
   * @return array<int|string, int|string|float|null>
   */
  public static function formatRow(array $row): array {
    return array_map([self::class, 'format'], $row);
  }
}
