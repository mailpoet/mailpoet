<?php declare(strict_types = 1);

namespace MailPoet\Util;

class SpreadsheetCellFormatter {
  /**
   * Leading characters that make a spreadsheet application read a cell as a formula
   * rather than as text. Excel also acts on the tab and carriage return variants.
   */
  private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

  private const TEXT_PREFIX = "'";

  public static function formatString(string $value): string {
    if ($value === '') {
      return $value;
    }
    return in_array($value[0], self::FORMULA_TRIGGERS, true) ? self::TEXT_PREFIX . $value : $value;
  }

  /**
   * Only strings are guarded. Other types are numbers MailPoet computed itself, and
   * prefixing them would export a genuine negative number as text.
   *
   * @param int|string|float|null $value
   * @return int|string|float|null
   */
  public static function format($value) {
    return is_string($value) ? self::formatString($value) : $value;
  }

  /**
   * @param array<int|string, int|string|float|null> $row
   * @return array<int|string, int|string|float|null>
   */
  public static function formatRow(array $row): array {
    return array_map([self::class, 'format'], $row);
  }

  /**
   * @param array<int|string, string> $values
   * @return array<int|string, string>
   */
  public static function formatStrings(array $values): array {
    return array_map([self::class, 'formatString'], $values);
  }
}
