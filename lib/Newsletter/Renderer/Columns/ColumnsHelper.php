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
  static function columnWidth($columns_count, $columns_layout) {
    if (isset(self::$columns_width[$columns_layout])) {
      return self::$columns_width[$columns_layout];
    }
    return self::$columns_width[$columns_count];
  }

  static function columnClass($columns_count) {
    return self::$columns_class[$columns_count];
  }

  static function columnClasses() {
    return self::$columns_class;
  }

  static function columnAlignment($columns_count) {
    return self::$columns_alignment[$columns_count];
  }
}
