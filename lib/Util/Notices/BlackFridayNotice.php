<?php

namespace MailPoet\Util\Notices;

use MailPoet\Models\Subscriber;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class BlackFridayNotice {

  const OPTION_NAME = 'dismissed-black-friday-notice';
  const DISMISS_NOTICE_TIMEOUT_SECONDS = 2592000; // 30 days

  public function init($shouldDisplay) {
    $shouldDisplay = $shouldDisplay
      && (time() <= strtotime('2020-12-06 12:00:00'))
      && (time() >= strtotime('2020-11-23 12:00:00'))
      && !get_transient(self::OPTION_NAME);
    if ($shouldDisplay) {
      $this->display();
    }
  }

  private function display() {
    $subscribers = Subscriber
      ::whereNull('deleted_at')
      ->count();
    $header = '<h3 class="mailpoet-h3">' . __('MailPoet Black Friday: 33% discount on all our plans!', 'mailpoet') . '</h3>';
    $body = '<h5 class="mailpoet-h5">' . __('Signup to a yearly plan today and get 4 months for free.', 'mailpoet') . '</h5>';
    $link = "<p><a href='https://account.mailpoet.com/?s=$subscribers' class='mailpoet-button mailpoet-button-small' target='_blank'>"
      . __('Buy Now', 'mailpoet')
      . '</a></p>';

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    WPNotice::displaySuccess($header . $body . $link, $extraClasses, self::OPTION_NAME, false);
  }

  public function disable() {
    WPFunctions::get()->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }
}
