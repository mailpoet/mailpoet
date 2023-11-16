<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Util\Notices;

use MailPoet\Config\ServicesChecker;
use MailPoet\Util\License\Features\Subscribers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class BlackFridayNotice {

  const OPTION_NAME = 'dismissed-black-friday-notice';
  const DISMISS_NOTICE_TIMEOUT_SECONDS = 2592000; // 30 days
  const DATE_FROM = '2023-11-22 14:00:00 UTC';
  const DATE_TO = '2023-11-28 14:00:00 UTC';
  const PARAM_REF = 'sale-bfcm-2023-plugin';
  const PARAM_UTM_CAMPAIGN = 'sale_bfcm_2023';

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var Subscribers */
  private $subscribers;

  public function __construct(
    ServicesChecker $servicesChecker,
    Subscribers $subscribers
  ) {
    $this->servicesChecker = $servicesChecker;
    $this->subscribers = $subscribers;
  }

  public function init($shouldDisplay) {
    $shouldDisplay = $shouldDisplay
      && (time() >= strtotime(self::DATE_FROM))
      && (time() <= strtotime(self::DATE_TO))
      && !get_transient(self::OPTION_NAME);
    if ($shouldDisplay) {
      $this->display();
    }
  }

  private function display() {
    $header = '<h3 class="mailpoet-h3">' . __('Get up to 40% off all MailPoet annual plans and upgrades', 'mailpoet') . '</h3>';
    $body = '<h5 class="mailpoet-h5">' . __('Our Black Friday sale is live. Save up to 40% when you switch to or upgrade an annual plan — no coupon needed. Offer ends at 2 PM UTC, 28 November.', 'mailpoet') . '</h5>';
    $link = "<p><a href='" . $this->getSaleUrl() . "' class='mailpoet-button button-primary' target='_blank'>"
      // translators: a button on a sale banner
      . __('Shop annual plans', 'mailpoet')
      . '</a></p>';

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    WPNotice::displaySuccess($header . $body . $link, $extraClasses, self::OPTION_NAME, false);
  }

  public function disable() {
    WPFunctions::get()->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }

  private function getSaleUrl(): string {
    $params = 'ref=' . self::PARAM_REF . '&utm_source=plugin&utm_medium=banner&utm_campaign=' . self::PARAM_UTM_CAMPAIGN;
    $partialApiKey = $this->servicesChecker->generatePartialApiKey();
    if ($partialApiKey) {
      return 'https://account.mailpoet.com/orders/upgrade/' . $partialApiKey . '?' . $params;
    }
    return 'https://account.mailpoet.com/?s=' . $this->subscribers->getSubscribersCount() . '&' . $params;
  }
}
