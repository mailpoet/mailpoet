<?php
namespace MailPoet\Util;

class Url {
  function __construct() {
  }

  static function getCurrentUrl() {
    global $wp;
    return home_url(
      add_query_arg(
        $wp->query_string,
        $wp->request
      )
    );
  }

  static function redirectTo($url = null) {
    wp_safe_redirect($url);
    exit();
  }

  static function redirectBack() {
    $referer = (isset($_REQUEST['mailpoet_redirect'])
      ? $_REQUEST['mailpoet_redirect']
      : null
    );
    if($referer === null) {
      // try to get the server's referer
      if(!empty($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
      }
    }

    if($referer !== null) {
      self::redirectTo($referer);
    }
    exit();
  }

  static function redirectWithReferer($url = null) {
    $current_url = self::getCurrentUrl();
    $url = add_query_arg(
      array(
        'mailpoet_redirect' => urlencode($current_url)
      ),
      $url
    );

    if($url !== $current_url) {
      self::redirectTo($url);
    }
    exit();
  }
}