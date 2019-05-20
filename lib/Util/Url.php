<?php
namespace MailPoet\Util;

use MailPoet\WP\Functions as WPFunctions;

class Url {
  /** @var WPFunctions */
  private $wp;

  function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  function getCurrentUrl() {
    $home_url = parse_url($this->wp->homeUrl());
    $query_args = $this->wp->addQueryArg(null, null);

    // Remove $this->wp->homeUrl() path from add_query_arg
    if (isset($home_url['path'])) {
      $query_args = str_replace($home_url['path'], '', $query_args);
    }

    return $this->wp->homeUrl($query_args);
  }

  function redirectTo($url = null) {
    $this->wp->wpSafeRedirect($url);
    exit();
  }

  function redirectBack($params = []) {
    // check mailpoet_redirect parameter
    $referer = (isset($_POST['mailpoet_redirect'])
      ? $_POST['mailpoet_redirect']
      : $this->wp->wpGetReferer()
    );

    // fallback: home_url
    if (!$referer) {
      $referer = $this->wp->homeUrl();
    }

    // append extra params to url
    if (!empty($params)) {
      $referer = $this->wp->addQueryArg($params, $referer);
    }

    $this->redirectTo($referer);
    exit();
  }

  function redirectWithReferer($url = null) {
    $current_url = $this->getCurrentUrl();
    $url = $this->wp->addQueryArg(
      [
        'mailpoet_redirect' => urlencode($current_url),
      ],
      $url
    );

    if ($url !== $current_url) {
      $this->redirectTo($url);
    }
    exit();
  }
}
