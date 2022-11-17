<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

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
      && (time() >= strtotime('2022-11-23 15:00:00 UTC'))
      && (time() <= strtotime('2022-11-29 15:00:00 UTC'))
      && !get_transient(self::OPTION_NAME);
    if ($shouldDisplay) {
      $this->display();
    }
  }

  private function display() {
    $subscribers = $this->subscribersRepository->countBy(['deletedAt' => null]);
    $header = '<h3 class="mailpoet-h3">' . __('Our Black Friday sale is live! Save 40% for a limited time.', 'mailpoet') . '</h3>';
    $body = '<h5 class="mailpoet-h5">' . __('Get a 40% discount on all MailPoet plans and upgrades until 3 PM UTC on 29 November. Terms & conditions apply.', 'mailpoet') . '</h5>';
    $link = "<p><a href='https://account.mailpoet.com/?s=$subscribers&billing=yearly&ref=sale-bfcm-2022-plugin&utm_source=MP&utm_medium=plugin&utm_campaign=mp_bfcm22' class='mailpoet-button button-primary' target='_blank'>"
      . __('Shop Now', 'mailpoet')
      . '</a></p>';

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    WPNotice::displaySuccess($header . $body . $link, $extraClasses, self::OPTION_NAME, false);
  }

  public function disable() {
    WPFunctions::get()->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }
}
