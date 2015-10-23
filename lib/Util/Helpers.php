<?php
namespace MailPoet\Util;

/*
 * Matches each symbol of PHP date format standard
 * with jQuery equivalent codeword
 * @author Tristan Jahier
 */
function dateformat_PHP_to_jQueryUI($php_format) {
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

/*
 * Determine maximum post size in bytes
 */
function get_maximum_post_size() {
  $maximum_post_size = ini_get('post_max_size');
  $maximum_post_size_bytes = (int) $maximum_post_size;
  $unit = strtolower($maximum_post_size[strlen($maximum_post_size) - 1]);
  switch ($unit) {
  case 'g':
    $maximum_post_size_bytes *= 1024;
  case 'm':
    $maximum_post_size_bytes *= 1024;
  case 'k':
    $maximum_post_size_bytes *= 1024;
  }

  return $maximum_post_size_bytes;
}

/*
 * Flatten multidimensional array
 */
function flatten_array($array) {
  return call_user_func_array(
    'array_merge_recursive', array_map('array_values', $array)
  );
}