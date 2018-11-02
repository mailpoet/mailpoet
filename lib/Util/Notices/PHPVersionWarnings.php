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
    $error_string = __('Your website is running on PHP %s. MailPoet runs a whole lot better with version 7. In fact, so does your WordPress website. [link]Your host can help you upgrade to the latest PHP version risk free in a few minutes.[/link]', 'mailpoet');
    $error_string = sprintf($error_string, $php_version);
    $error = Helpers::replaceLinkTags($error_string, 'https://beta.docs.mailpoet.com/article/251-upgrading-the-websites-php-version', array('target' => '_blank'));
    $extra_classes = 'mailpoet-dismissible-notice is-dismissible';

    return \MailPoet\WP\Notice::displayError($error, $extra_classes, self::OPTION_NAME);
  }

  function disable() {
    set_transient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }

}
