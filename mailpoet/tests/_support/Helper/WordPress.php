<?php declare(strict_types = 1);

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class WordPress extends \Codeception\Module {

  private static $functionsToIntercept = [];

  public static function interceptFunction($functionName, $callback) {
    self::$functionsToIntercept[$functionName] = $callback;
  }

  public static function releaseFunction($functionName) {
    unset(self::$functionsToIntercept[$functionName]);
  }

  public static function releaseAllFunctions() {
    self::$functionsToIntercept = [];
  }

  public static function getInterceptor($functionName) {
    if (isset(self::$functionsToIntercept[$functionName]))
      return self::$functionsToIntercept[$functionName];
  }
}

// WP function overrides for \MailPoet namespace go here

namespace MailPoet\WP; // phpcs:ignore

function override($func, $args) {
  $func = str_replace(__NAMESPACE__ . '\\', '', $func);
  $callback = \Helper\WordPress::getInterceptor($func);
  if ($callback) {
    return call_user_func_array($callback, $args);
  }
  $func = '\\' . $func;
  if (!is_callable($func)) {
    throw new \RuntimeException("Function $func doesn't exist.");
  }
  return call_user_func_array($func, $args);
}

function get_terms($key) {
  return override(__FUNCTION__, func_get_args());
}

function get_bloginfo($key) {
  return override(__FUNCTION__, func_get_args());
}

function get_option($key) {
  return override(__FUNCTION__, func_get_args());
}

function add_filter() {
  return override(__FUNCTION__, func_get_args());
}

function apply_filters() {
  return override(__FUNCTION__, func_get_args());
}

function add_action() {
  return override(__FUNCTION__, func_get_args());
}

function do_action() {
  return override(__FUNCTION__, func_get_args());
}

function wp_remote_post() {
  return override(__FUNCTION__, func_get_args());
}

function wp_remote_get() {
  return override(__FUNCTION__, func_get_args());
}

function current_time() {
  return override(__FUNCTION__, func_get_args());
}
