<?php
namespace MailPoet\Util;

class Helpers {
  const DIVIDER = '***MailPoet***';
  const LINK_TAG = 'link';

  static function isJson($string) {
    if(!is_string($string)) return false;
    json_decode($string);
    return json_last_error() == JSON_ERROR_NONE;
  }

  static function replaceLinkTags($source, $link = false, $attributes = array(), $link_tag = false) {
    if(!$link) return $source;
    $link_tag = ($link_tag) ? $link_tag : self::LINK_TAG;
    $attributes = array_map(function($key) use ($attributes) {
      return sprintf('%s="%s"', $key, $attributes[$key]);
    }, array_keys($attributes));
    $source = str_replace(
      '[' . $link_tag . ']',
      sprintf(
        '<a %s href="%s">',
        join(' ', $attributes),
        $link
      ),
      $source
    );
    $source = str_replace('[/' . $link_tag . ']', '</a>', $source);
    return preg_replace('/\s+/', ' ', $source);
  }

  static function getMaxPostSize($bytes = false) {
    $maxPostSize = ini_get('post_max_size');
    if(!$bytes) return $maxPostSize;
    switch(substr($maxPostSize, -1)) {
      case 'M':
      case 'm':
        return (int)$maxPostSize * 1048576;
      case 'K':
      case 'k':
        return (int)$maxPostSize * 1024;
      case 'G':
      case 'g':
        return (int)$maxPostSize * 1073741824;
      default:
        return $maxPostSize;
    }
  }

  static function flattenArray($array) {
    if(!$array) return;
    $flattened_array = array();
    array_walk_recursive($array, function ($a) use (&$flattened_array) {
      $flattened_array[] = $a;
    });
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
    $paramsColumnKey = ($params[1] !== null) ? (string)$params[1] : null;
    $paramsIndexKey = null;
    if(isset($params[2])) {
      if(is_float($params[2]) || is_int($params[2])) {
        $paramsIndexKey = (int)$params[2];
      } else {
        $paramsIndexKey = (string)$params[2];
      }
    }
    $resultArray = array();
    foreach($paramsInput as $row) {
      $key = $value = null;
      $keySet = $valueSet = false;
      if($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
        $keySet = true;
        $key = (string)$row[$paramsIndexKey];
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

  static function underscoreToCamelCase($str, $capitalise_first_char = false) {
    if($capitalise_first_char) {
      $str[0] = strtoupper($str[0]);
    }
    return preg_replace_callback('/_([a-z])/', function ($c) {
      return strtoupper($c[1]);
    }, $str);
  }

  static function camelCaseToUnderscore($str) {
    $str[0] = strtolower($str[0]);
    return preg_replace_callback('/([A-Z])/', function ($c) {
      return "_" . strtolower($c[1]);
    }, $str);
  }

  static function joinObject($object = array()) {
    return implode(self::DIVIDER, $object);
  }

  static function splitObject($object = array()) {
    return explode(self::DIVIDER, $object);
  }

  static function getIP() {
    return (isset($_SERVER['REMOTE_ADDR']))
      ? $_SERVER['REMOTE_ADDR']
      : null;
  }

}
