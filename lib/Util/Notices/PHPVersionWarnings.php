<?php

namespace MailPoet\Util\Notices;

use MailPoet\Util\Helpers;
use MailPoet\WP\Notice as WPNotice;

class PHPVersionWarnings {

  const DISMISS_NOTICE_TIMEOUT_SECONDS = 2592000; // 30 days
  const OPTION_NAME = 'dismissed-php-version-outdated-notice';

  function init($php_version, $should_display) {

    if($should_display && $this->isOutdatedPHPVersion($php_version)) {
      return $this->display($php_version);
    }
  }

  function isOutdatedPHPVersion($php_version) {
    return version_compare($php_version, '5.6', '<') && !get_transient(self::OPTION_NAME);
  }
  
  function display($php_version) {
    $error_string = __('Your website is running on PHP %s. MailPoet requires version 5.6. Please consider upgrading your site\'s PHP version. [link]Your host can help you.[/link]', 'mailpoet');
    $error_string = sprintf($error_string, $php_version);
    $error = Helpers::replaceLinkTags($error_string, 'https://beta.docs.mailpoet.com/article/251-upgrading-the-websites-php-version', array('target' => '_blank'));
    $extra_classes = 'mailpoet-dismissible-notice is-dismissible';

    return WPNotice::displayError($error, $extra_classes, self::OPTION_NAME);
  }

  function disable() {
    set_transient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }

}
