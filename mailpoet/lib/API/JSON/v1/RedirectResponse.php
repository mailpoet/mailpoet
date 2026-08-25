<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Response;
use MailPoet\WP\Functions as WPFunctions;

class RedirectResponse extends Response {

  public function __construct($location) { // phpcs:ignore
    $wp = WPFunctions::get();
    // Keep redirects on-site unless a host is explicitly allowed via WordPress'
    // allowed_redirect_hosts filter, mirroring wp_safe_redirect() handling.
    $location = $wp->wpValidateRedirect($location, $wp->homeUrl());
    parent::__construct(self::REDIRECT, [], $location);
  }

  public function getData() {
    return [];
  }
}
