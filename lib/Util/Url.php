<?php
namespace MailPoet\Util;

class Url {
  static function getCurrentUrl() {
    $home_url = parse_url(home_url());
    $query_args = add_query_arg(null, null);

    // Remove home_url() path from add_query_arg
    if(isset($home_url['path'])) {
      $query_args = str_replace($home_url['path'], null, $query_args);
    }

    return home_url($query_args);
  }

  static function redirectTo($url = null) {
    wp_safe_redirect($url);
    exit();
  }

  static function redirectBack($params = array()) {
    // check mailpoet_redirect parameter
    $referer = (isset($_POST['mailpoet_redirect'])
      ? $_POST['mailpoet_redirect']
      : wp_get_referer()
    );

    // fallback: home_url
    if(!$referer) {
      $referer = home_url();
    }

    // append extra params to url
    if(!empty($params)) {
      $referer = add_query_arg($params, $referer);
    }

    self::redirectTo($referer);
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