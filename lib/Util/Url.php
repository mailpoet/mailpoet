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
    // check mailpoet_redirect parameter
    $referer = (isset($_POST['mailpoet_redirect'])
      ? $_POST['mailpoet_redirect']
      : null
    );

    // fallback: http referer
    if($referer === null) {
      if(!empty($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
      }
    }

    // fallback: home_url
    if($referer === null) {
      $referer = home_url();
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