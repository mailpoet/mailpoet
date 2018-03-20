<?php
namespace MailPoet\WP;

class Functions {
  static function wpRemotePost() {
    return self::callWithFallback('wp_remote_post', func_get_args());
  }

  static function wpRemoteGet() {
    return self::callWithFallback('wp_remote_get', func_get_args());
  }

  static function wpRemoteRetrieveBody() {
    return self::callWithFallback('wp_remote_retrieve_body', func_get_args());
  }

  static function wpRemoteRetrieveResponseCode() {
    return self::callWithFallback('wp_remote_retrieve_response_code', func_get_args());
  }

  static function wpRemoteRetrieveResponseMessage() {
    return self::callWithFallback('wp_remote_retrieve_response_message', func_get_args());
  }

  static function currentTime() {
    return self::callWithFallback('current_time', func_get_args());
  }

  private static function callWithFallback($func, $args) {
    $local_func = __NAMESPACE__ . '\\' . $func;
    if(function_exists($local_func)) {
      return call_user_func_array($local_func, $args);
    }
    return call_user_func_array($func, $args);
  }
}