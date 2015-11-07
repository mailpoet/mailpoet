<?php
namespace MailPoet\Util;

class Helpers {
  
  /*
   * Matches each symbol of PHP date format standard
   * with jQuery equivalent codeword
   * @author Tristan Jahier
   */
  static function dateformat_PHP_to_jQueryUI($php_format) {
    $SYMBOLS_MATCHING = array(
      // Day
      'd' => 'dd',
      'D' => 'D',
      'j' => 'd',
      'l' => 'DD',
      'N' => '',
      'S' => '',
      'w' => '',
      'z' => 'o',
      // Week
      'W' => '',
      // Month
      'F' => 'MM',
      'm' => 'mm',
      'M' => 'M',
      'n' => 'm',
      't' => '',
      // Year
      'L' => '',
      'o' => '',
      'Y' => 'yy',
      'y' => 'y',
      // Time
      'a' => '',
      'A' => '',
      'B' => '',
      'g' => '',
      'G' => '',
      'h' => '',
      'H' => '',
      'i' => '',
      's' => '',
      'u' => ''
    );
    $jqueryui_format = "";
    $escaping = false;
    for ($i = 0; $i < strlen($php_format); $i++) {
      $char = $php_format[$i];
      if($char === '\\') // PHP date format escaping character
      {
        $i++;
        if($escaping) {
          $jqueryui_format .= $php_format[$i];
        } else {
          $jqueryui_format .= '\'' . $php_format[$i];
        }
        $escaping = true;
      } else {
        if($escaping) {
          $jqueryui_format .= "'";
          $escaping = false;
        }
        if(isset($SYMBOLS_MATCHING[$char])) {
          $jqueryui_format .= $SYMBOLS_MATCHING[$char];
        } else {
          $jqueryui_format .= $char;
        }
      }
    }
    
    return $jqueryui_format;
  }

  static function getMaxPostSize($bytes = false) {
    $maxPostSize = ini_get('post_max_size');
    if(!$bytes) return $maxPostSize;
    $maxPostSizeBytes = (int) $maxPostSize;
    $unit = strtolower($maxPostSize[strlen($maxPostSize) - 1]);
    switch ($unit) {
      case 'g':
        $maxPostSizeBytes *= 1024;
      case 'm':
        $maxPostSizeBytes *= 1024;
      case 'k':
        $maxPostSizeBytes *= 1024;
    }
    return $maxPostSizeBytes;
  }

  static function flattenArray($array) {
    if(!$array) return;
    $flattened_array = array();
    array_walk_recursive($array, function ($a) use (&$flattened_array) { $flattened_array[] = $a; });
    return $flattened_array;
  }

  /*
  * Using func_get_args() in order to check for proper number ofparameters and trigger errors exactly as the built-in array_column()
  * does in PHP 5.5.
  * @author Ben Ramsey (http://benramsey.com)
  */
  static function arrayColumn($input = null, $columnKey = null, $indexKey = null) {
    $argc = func_num_args();
    $params = func_get_args();
    if($argc < 2) {
      trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
      return null;
    }
    if(!is_array($params[0])) {
      trigger_error(
        'array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given',
        E_USER_WARNING
      );
      return null;
    }
    if(!is_int($params[1])
      && !is_float($params[1])
      && !is_string($params[1])
      && $params[1] !== null
      && !(is_object($params[1]) && method_exists($params[1], '__toString'))
    ) {
      trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
      return false;
    }
    if(isset($params[2])
      && !is_int($params[2])
      && !is_float($params[2])
      && !is_string($params[2])
      && !(is_object($params[2]) && method_exists($params[2], '__toString'))
    ) {
      trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
      return false;
    }
    $paramsInput = $params[0];
    $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;
    $paramsIndexKey = null;
    if(isset($params[2])) {
      if(is_float($params[2]) || is_int($params[2])) {
        $paramsIndexKey = (int) $params[2];
      } else {
        $paramsIndexKey = (string) $params[2];
      }
    }
    $resultArray = array();
    foreach ($paramsInput as $row) {
      $key = $value = null;
      $keySet = $valueSet = false;
      if($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
        $keySet = true;
        $key = (string) $row[$paramsIndexKey];
      }
      if($paramsColumnKey === null) {
        $valueSet = true;
        $value = $row;
      } elseif(is_array($row) && array_key_exists($paramsColumnKey, $row)) {
        $valueSet = true;
        $value = $row[$paramsColumnKey];
      }
      if($valueSet) {
        if($keySet) {
          $resultArray[$key] = $value;
        } else {
          $resultArray[] = $value;
        }
      }
    }
    return $resultArray;
  }
}