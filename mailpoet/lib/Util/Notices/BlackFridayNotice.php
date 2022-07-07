<?php

namespace MailPoet\Util\Notices;

use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class BlackFridayNotice {

  const OPTION_NAME = 'dismissed-black-friday-notice';
  const DISMISS_NOTICE_TIMEOUT_SECONDS = 2592000; // 30 days

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    SubscribersRepository $subscribersRepository
  ) {
    $this->subscribersRepository = $subscribersRepository;
  }

  public function init($shouldDisplay) {
    $shouldDisplay = $shouldDisplay
      && (time() <= strtotime('2021-11-30 12:00:00'))
      && (time() >= strtotime('2021-11-24 12:00:00'))
      && !get_transient(self::OPTION_NAME);
    if ($shouldDisplay) {
      $this->display();
    }
  }

  private function display() {
    $subscribers = $this->subscribersRepository->countBy(['deletedAt' => null]);
    $header = '<h3 class="mailpoet-h3">' . __('Save big on MailPoet – 40% off this Black Friday', 'mailpoet') . '</h3>';
    $body = '<h5 class="mailpoet-h5">' . __('Our biggest ever sale is here! Save 40% on all annual plans and licenses until 8 am UTC, November 30. Terms and conditions apply.', 'mailpoet') . '</h5>';
    $link = "<p><a href='https://account.mailpoet.com/?s=$subscribers' class='mailpoet-button button-primary' target='_blank'>"
      . __('Shop now', 'mailpoet')
      . '</a></p>';

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    WPNotice::displaySuccess($header . $body . $link, $extraClasses, self::OPTION_NAME, false);
  }

  public function disable() {
    WPFunctions::get()->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }
}
