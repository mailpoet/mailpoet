<?php
namespace MailPoet\Newsletter\Renderer\Columns;

class ColumnsHelper {
  static $columnsWidth = array(
    1 => 660,
    2 => 330,
    3 => 220
  );
  
  static $columnsClass = array(
    1 => 'cols-one',
    2 => 'cols-two',
    3 => 'cols-three'
  );

  static $columnsAlignment = array(
    1 => null,
    2 => 'left',
    3 => 'right'
  );
}