<?php

namespace MailPoet\Newsletter\Renderer\Columns;

class ColumnsHelper {
  static $columns_width = [
    1 => [660],
    2 => [330, 330],
    "1_2" => [220, 440],
    "2_1" => [440, 220],
    3 => [220, 220, 220],
  ];

  static $columns_class = [
    1 => 'cols-one',
    2 => 'cols-two',
    3 => 'cols-three',
  ];

  static $columns_alignment = [
    1 => null,
    2 => 'left',
    3 => 'right',
  ];

  /** @return int[] */
  public static function columnWidth($columnsCount, $columnsLayout) {
    if (isset(self::$columnsWidth[$columnsLayout])) {
      return self::$columnsWidth[$columnsLayout];
    }
    return self::$columnsWidth[$columnsCount];
  }

  public static function columnClass($columnsCount) {
    return self::$columnsClass[$columnsCount];
  }

  public static function columnClasses() {
    return self::$columnsClass;
  }

  public static function columnAlignment($columnsCount) {
    return self::$columnsAlignment[$columnsCount];
  }
}
