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
      && (time() >= strtotime('2023-06-14 14:00:00 UTC'))
      && (time() <= strtotime('2023-06-19 14:00:00 UTC'))
      && !get_transient(self::OPTION_NAME);
    if ($shouldDisplay) {
      $this->display();
    }
  }

  private function display() {
    $subscribers = $this->subscribersRepository->countBy(['deletedAt' => null]);
    $header = '<h3 class="mailpoet-h3">' . __('Get 40% off all MailPoet annual plans and upgrades – no coupon required.', 'mailpoet') . '</h3>';
    $body = '<h5 class="mailpoet-h5">' . __('Offer ends at 2 pm UTC on Monday, June 19, 2023. Terms and conditions apply.', 'mailpoet') . '</h5>';
    $link = "<p><a href='https://account.mailpoet.com/?s=$subscribers&billing=yearly&ref=sale-june-2023-plugin&utm_source=MP&utm_medium=plugin&utm_campaign=sale_june_2023' class='mailpoet-button button-primary' target='_blank'>"
      // translators: a button on a sale banner. "Save" meaning to save money - 40% discount
      . __('Pick a plan and save', 'mailpoet')
      . '</a></p>';

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    WPNotice::displaySuccess($header . $body . $link, $extraClasses, self::OPTION_NAME, false);
  }

  public function disable() {
    WPFunctions::get()->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }
}
