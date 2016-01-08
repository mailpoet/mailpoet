<?php
namespace MailPoet\Newsletter\Renderer\Columns;

class ColumnsHelper {
  static $columns_width = array(
    1 => 660,
    2 => 330,
    3 => 220
  );

  static $columns_class = array(
    1 => 'cols-one',
    2 => 'cols-two',
    3 => 'cols-three'
  );

  static $columns_alignment = array(
    1 => null,
    2 => 'left',
    3 => 'right'
  );

  static function columnWidth($columns_count) {
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