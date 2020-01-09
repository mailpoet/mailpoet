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
      && (time() <= strtotime('2019-11-30 23:59:59'))
      && (time() >= strtotime('2019-11-08 00:00:00'))
      && !get_transient(self::OPTION_NAME);
    if ($shouldDisplay) {
      $this->display();
    }
  }

  private function display() {
    $subscribers = Subscriber
      ::whereNull('deleted_at')
      ->count();
    $header = '<h1>' . __('MailPoet Black Friday: 33% discount on all our plans!', 'mailpoet') . '</h1>';
    $body = '<p>' . __('Signup to a yearly plan today and get 4 months for free.', 'mailpoet') . '</p>';
    $link = "<a href='https://account.mailpoet.com/?s=$subscribers' class='button button-primary' target='_blank'>"
      . __('Buy Now', 'mailpoet')
      . '</a>';

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    WPNotice::displaySuccess($header . $body . $link, $extraClasses, self::OPTION_NAME);
  }

  public function disable() {
    WPFunctions::get()->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }

}
