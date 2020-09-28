<?php

namespace MailPoet\Util\Notices;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class PHPVersionWarnings {

  const DISMISS_NOTICE_TIMEOUT_SECONDS = 2592000; // 30 days
  const OPTION_NAME = 'dismissed-php-version-outdated-notice';

  public function init($phpVersion, $shouldDisplay) {
    if ($shouldDisplay && $this->isOutdatedPHPVersion($phpVersion)) {
      return $this->display($phpVersion);
    }
  }

  public function isOutdatedPHPVersion($phpVersion) {
    return version_compare($phpVersion, '7.2', '<') && !get_transient(self::OPTION_NAME);
  }

  public function display($phpVersion) {
    $errorString = __('Your website is running on PHP %s which MailPoet does not officially support. Read our [link]simple PHP upgrade guide[/link] or let MailPoet’s support team upgrade it for you.', 'mailpoet');
    $errorString = sprintf($errorString, $phpVersion);
    $getInTouchString = __('[link]Yes, I want MailPoet to help me upgrade for free[/link]', 'mailpoet');
    $error = Helpers::replaceLinkTags($errorString, 'https://kb.mailpoet.com/article/251-upgrading-the-websites-php-version', [
      'target' => '_blank',
      'data-beacon-article' => '5ad5f8982c7d3a0e93676666',
    ]);
    $error .= '<br><br>' . Helpers::replaceLinkTags($getInTouchString, 'https://www.mailpoet.com/let-us-handle-your-php-upgrade/', [
      'target' => '_blank',
      'class' => 'button',
    ]);

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    return Notice::displayWarning($error, $extraClasses, self::OPTION_NAME);
  }

  public function disable() {
    WPFunctions::get()->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }
}
