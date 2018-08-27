<?php

namespace MailPoet\Config;

use MailPoet\Util\Helpers;
use MailPoet\WP\Notice as WPNotice;

class PHPVersionWarnings {

  const DISMISS_NOTICE_TIMEOUT_SECONDS = 2592000; // 30 days

  function init($php_version, $is_enabled) {
    add_action('wp_ajax_dismissed_notice_handler', array(
      $this,
      'ajaxDismissNoticeHandler'
    ));

    if($is_enabled && $this->isOutdatedPHPVersion($php_version)) {
      return $this->displayError($php_version);
    }
  }

  function isOutdatedPHPVersion($php_version) {
    return version_compare($php_version, '7.0', '<') && !get_transient('dismissed-php-version-outdated-notice');
  }

  function displayError($php_version) {
    $error_string = __('Your website is running on PHP %s. MailPoet will require version 7 by the end of the year. Please consider upgrading your site\'s PHP version. [link]Your host can help you.[/link]', 'mailpoet');
    $error_string = sprintf($error_string, $php_version);
    $error = Helpers::replaceLinkTags($error_string, 'https://beta.docs.mailpoet.com/article/251-upgrading-the-websites-php-version', array('target' => '_blank'));
    $extra_classes = 'notice-php-warning is-dismissible';
    $data_notice_name = 'php-version-outdated';

    return WPNotice::displayError($error, $extra_classes, $data_notice_name);
  }

  function ajaxDismissNoticeHandler() {
    set_transient('dismissed-php-version-outdated-notice', true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }

}
