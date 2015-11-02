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
    if (!$bytes) return $maxPostSize;
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
    return call_user_func_array(
      'array_merge_recursive', array_map('array_values', $array)
    );
  }
}  