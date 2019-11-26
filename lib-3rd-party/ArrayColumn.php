<?php

namespace MailPoet\Util;

/*
 * Using func_get_args() in order to check for proper number of parameters and trigger errors exactly as the built-in array_column()
 * does in PHP 5.5.
 * @author Ben Ramsey (http://benramsey.com)
 */
function array_column($input = null, $column_key = null, $index_key = null) {
  $argc = func_num_args();
  $params = func_get_args();

  if (!empty(\array_column([['id' => '4']], 'id'))) {
    return \array_column($input, $column_key, $index_key);
  }
  if ($argc < 2) {
    trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
    return null;
  }
  if (!is_array($params[0])) {
    trigger_error(
      'array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given',
      E_USER_WARNING
    );
    return null;
  }
  if (!is_int($params[1])
     && !is_float($params[1])
     && !is_string($params[1])
     && $params[1] !== null
     && !(is_object($params[1]) && method_exists($params[1], '__toString'))
  ) {
    trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
    return false;
  }
  if (isset($params[2])
     && !is_int($params[2])
     && !is_float($params[2])
     && !is_string($params[2])
     && !(is_object($params[2]) && method_exists($params[2], '__toString'))
  ) {
    trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
    return false;
  }
  $params_input = $params[0];
  $params_column_key = ($params[1] !== null) ? (string)$params[1] : null;
  $params_index_key = null;
  if (isset($params[2])) {
    if (is_float($params[2]) || is_int($params[2])) {
      $params_index_key = (int)$params[2];
    } else {
      $params_index_key = (string)$params[2];
    }
  }
  $result_array = [];
  foreach ($params_input as $row) {
    $key = $value = null;
    $key_set = $value_set = false;
    if ($params_index_key !== null && array_key_exists($params_index_key, $row)) {
      $key_set = true;
      $key = (string)$row[$params_index_key];
    }
    if ($params_column_key === null) {
      $value_set = true;
      $value = $row;
    } elseif (is_array($row) && array_key_exists($params_column_key, $row)) {
      $value_set = true;
      $value = $row[$params_column_key];
    }
    if ($value_set) {
      if ($key_set) {
        $result_array[$key] = $value;
      } else {
        $result_array[] = $value;
      }
    }
  }
  return $result_array;
}
