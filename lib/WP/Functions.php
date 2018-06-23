<?php
namespace MailPoet\WP;

use Mailpoet\Config\Env;

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

  static function getImageInfo($id) {
    /*
     * In some cases wp_get_attachment_image_src ignore the second parameter
     * and use global variable $content_width value instead.
     * By overriding it ourselves when ensure a constant behaviour regardless
     * of the user setup.
     *
     * https://mailpoet.atlassian.net/browse/MAILPOET-1365
     */
    global $content_width; // default is NULL

    $content_width_copy = $content_width;
    $content_width = Env::NEWSLETTER_CONTENT_WIDTH;
    $image_info = wp_get_attachment_image_src($id, 'mailpoet_newsletter_max');
    $content_width = $content_width_copy;

    return $image_info;
  }
}
