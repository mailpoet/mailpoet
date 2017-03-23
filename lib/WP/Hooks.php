<?php
namespace MailPoet\WP;

class Hooks {
  static function addFilter() {
    return self::callWithFallback('add_filter', func_get_args());
  }

  static function applyFilters() {
    return self::callWithFallback('apply_filters', func_get_args());
  }

  static function addAction() {
    return self::callWithFallback('add_action', func_get_args());
  }

  static function doAction() {
    return self::callWithFallback('do_action', func_get_args());
  }

  private static function callWithFallback($func, $args) {
    $local_func = __NAMESPACE__ . '\\' . $func;
    if(function_exists($local_func)) {
      return call_user_func_array($local_func, $args);
    }
    return call_user_func_array($func, $args);
  }
}
