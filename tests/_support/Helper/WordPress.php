<?php
namespace Helper;
// here you can define custom actions
// all public methods declared in helper class will be available in $I

class WordPress extends \Codeception\Module
{
  private static $functions_to_intercept = array();

  static function interceptFunction($function_name, $callback) {
    self::$functions_to_intercept[$function_name] = $callback;
  }

  static function releaseFunction($function_name) {
    unset(self::$functions_to_intercept[$function_name]);
  }

  static function releaseAllFunctions() {
    self::$functions_to_intercept = array();
  }

  static function getInterceptor($function_name) {
    if (isset(self::$functions_to_intercept[$function_name]))
      return self::$functions_to_intercept[$function_name];
  }
}

// WP function overrides for \MailPoet namespace go here
namespace MailPoet\WP;

function override($func, $args) {
  $func = str_replace(__NAMESPACE__ . '\\', '', $func);
  if($callback = \Helper\WordPress::getInterceptor($func)) {
    return call_user_func_array($callback, $args);
  }
  return call_user_func_array('\\' . $func, $args);
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
