<?php
namespace MailPoet\Util;

use MailPoet\WP\Functions as WPFunctions;

class Url {
  static function getCurrentUrl() {
    $home_url = parse_url(home_url());
    $query_args = WPFunctions::get()->addQueryArg(null, null);

    // Remove WPFunctions::get()->homeUrl() path from add_query_arg
    if (isset($home_url['path'])) {
      $query_args = str_replace($home_url['path'], '', $query_args);
    }

    return WPFunctions::get()->homeUrl($query_args);
  }

  static function redirectTo($url = null) {
    WPFunctions::get()->wpSafeRedirect($url);
    exit();
  }

  static function redirectBack($params = array()) {
    // check mailpoet_redirect parameter
    $referer = (isset($_POST['mailpoet_redirect'])
      ? $_POST['mailpoet_redirect']
      : WPFunctions::get()->wpGetReferer()
    );

    // fallback: home_url
    if (!$referer) {
      $referer = WPFunctions::get()->homeUrl();
    }

    // append extra params to url
    if (!empty($params)) {
      $referer = WPFunctions::get()->addQueryArg($params, $referer);
    }

    self::redirectTo($referer);
    exit();
  }

  static function redirectWithReferer($url = null) {
    $current_url = self::getCurrentUrl();
    $url = WPFunctions::get()->addQueryArg(
      array(
        'mailpoet_redirect' => urlencode($current_url)
      ),
      $url
    );

    if ($url !== $current_url) {
      self::redirectTo($url);
    }
    exit();
  }
}
