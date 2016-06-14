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

function get_option($key) {
  if($callback = \Helper\WordPress::getInterceptor('get_option')) {
    return $callback($key);
  }
  return \get_option($key);
}
