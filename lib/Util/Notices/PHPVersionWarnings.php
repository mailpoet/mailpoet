<?php

namespace MailPoet\Util\Notices;

use MailPoet\Util\Helpers;

class PHPVersionWarnings {

  const DISMISS_NOTICE_TIMEOUT_SECONDS = 2592000; // 30 days
  const OPTION_NAME = 'dismissed-php-version-outdated-notice';

  function init($php_version, $should_display) {

    if($should_display && $this->isOutdatedPHPVersion($php_version)) {
      return $this->display($php_version);
    }
  }

  function isOutdatedPHPVersion($php_version) {
    return version_compare($php_version, '7.0', '<') && !get_transient(self::OPTION_NAME);
  }

  function display($php_version) {
    $error_string = __('Your website is running on PHP %s which will not be supported by WordPress starting in April 2019. Read our [link]simple PHP upgrade guide[/link] or let MailPoet\'s support team upgrade it for you for free.', 'mailpoet');
    $error_string = sprintf($error_string, $php_version);
    $get_in_touch_string = _x('[link]Get in touch.[/link]', 'A link with an offer to upgrade PHP version for free', 'mailpoet');
    $error = Helpers::replaceLinkTags($error_string, 'https://kb.mailpoet.com/article/251-upgrading-the-websites-php-version', array('target' => '_blank'));
    $error .= ' ' . Helpers::replaceLinkTags($get_in_touch_string, 'https://www.mailpoet.com/let-us-handle-your-php-upgrade/', array('target' => '_blank'));

    $extra_classes = 'mailpoet-dismissible-notice is-dismissible';

    return \MailPoet\WP\Notice::displayWarning($error, $extra_classes, self::OPTION_NAME);
  }

  function disable() {
    set_transient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }

}
