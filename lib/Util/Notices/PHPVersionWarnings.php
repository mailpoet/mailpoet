<?php

namespace MailPoet\Util\Notices;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class PHPVersionWarnings {

  const DISMISS_NOTICE_TIMEOUT_SECONDS = 2592000; // 30 days
  const OPTION_NAME = 'dismissed-php-version-outdated-notice';

  function init($php_version, $should_display) {
    if ($should_display && $this->isOutdatedPHPVersion($php_version)) {
      return $this->display($php_version);
    }
  }

  function isOutdatedPHPVersion($php_version) {
    return version_compare($php_version, '7.0', '<') && !get_transient(self::OPTION_NAME);
  }

  function display($php_version) {
    $error_string = __('Your website is running on PHP %s which MailPoet does not officially support. Read our [link]simple PHP upgrade guide[/link] or let MailPoet’s support team upgrade it for you.', 'mailpoet');
    $error_string = sprintf($error_string, $php_version);
    $get_in_touch_string = __('[link]Yes, I want MailPoet to help me upgrade for free[/link]', 'mailpoet');
    $error = Helpers::replaceLinkTags($error_string, 'https://kb.mailpoet.com/article/251-upgrading-the-websites-php-version', ['target' => '_blank']);
    $error .= '<br><br>' . Helpers::replaceLinkTags($get_in_touch_string, 'https://www.mailpoet.com/let-us-handle-your-php-upgrade/', [
      'target' => '_blank',
      'class' => 'button',
    ]);

    $extra_classes = 'mailpoet-dismissible-notice is-dismissible';

    return Notice::displayWarning($error, $extra_classes, self::OPTION_NAME);
  }

  function disable() {
    WPFunctions::get()->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }

}
