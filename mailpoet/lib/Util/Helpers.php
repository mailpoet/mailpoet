<?php

namespace MailPoet\Util;

class Helpers {
  const DIVIDER = '***MailPoet***';
  const LINK_TAG = 'link';

  public static function isJson($string) {
    if (!is_string($string)) return false;
    json_decode($string);
    return json_last_error() == JSON_ERROR_NONE;
  }

  public static function replaceLinkTags($source, $link = false, $attributes = [], $linkTag = false) {
    if (!$link) return $source;
    $linkTag = ($linkTag) ? $linkTag : self::LINK_TAG;
    $attributes = array_map(function($key) use ($attributes) {
      return sprintf('%s="%s"', $key, $attributes[$key]);
    }, array_keys($attributes));
    $source = str_replace(
      '[' . $linkTag . ']',
      sprintf(
        '<a %s href="%s">',
        join(' ', $attributes),
        $link
      ),
      $source
    );
    $source = str_replace('[/' . $linkTag . ']', '</a>', $source);
    return preg_replace('/\s+/', ' ', $source);
  }

  public static function getMaxPostSize($bytes = false) {
    $maxPostSize = ini_get('post_max_size');
    if (!$bytes) return $maxPostSize;
    if ($maxPostSize === false) {
      return 0;
    }
    switch (substr($maxPostSize, -1)) {
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

  public static function flattenArray($array) {
    if (!$array) return;
    $flattenedArray = [];
    array_walk_recursive($array, function ($a) use (&$flattenedArray) {
      $flattenedArray[] = $a;
    });
    return $flattenedArray;
  }

  public static function underscoreToCamelCase($str, $capitaliseFirstChar = false) {
    if ($capitaliseFirstChar) {
      $str[0] = strtoupper($str[0]);
    }
    return preg_replace_callback('/_([a-z])/', function ($c) {
      return strtoupper($c[1]);
    }, $str);
  }

  public static function camelCaseToUnderscore($str) {
    $str[0] = strtolower($str[0]);
    return preg_replace_callback('/([A-Z])/', function ($c) {
      return "_" . strtolower($c[1]);
    }, $str);
  }

  public static function joinObject($object = []) {
    return implode(self::DIVIDER, $object);
  }

  public static function splitObject($object = []) {
    return explode(self::DIVIDER, $object);
  }

  public static function getIP() {
    return (isset($_SERVER['REMOTE_ADDR']))
      ? $_SERVER['REMOTE_ADDR']
      : null;
  }

  public static function recursiveTrim($value) {
    if (is_array($value))
      return array_map([__CLASS__, 'recursiveTrim'], $value);
    if (is_string($value))
      return trim($value);
    return $value;
  }

  public static function escapeSearch(string $search): string {
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($search)); // escape for 'LIKE'
  }
}
