<?php

namespace MailPoet\Config;

use MailPoet\Util\Helpers;

class PHPVersionWarnings {

  const DISMISS_NOTICE_TIMEOUT_SECONDS = 2592000; // 30 days

  function init($php_version, $is_enabled) {
    add_action('wp_ajax_dismissed_notice_handler', array(
      $this,
      'ajaxDismissNoticeHandler'
    ));
    $error = null;
    if (!$is_enabled) return $error;
    if (is_null($error)) $error = $this->checkPHP70Version($php_version);
    return $error;
  }

  function checkPHP70Version($php_version) {
    $error_string = null;
    if(version_compare($php_version, '7.0', '<') && !get_transient('dismissed-php-version-outdated-notice')) {
      $error_string = __('Your website is running on PHP %s. MailPoet will require version 7 by the end of the year. Please consider upgrading your site\'s PHP version. [link]Your host can help you.[/link]', 'mailpoet');
      $error_string = sprintf($error_string, $php_version);
      $error = Helpers::replaceLinkTags($error_string, 'https://beta.docs.mailpoet.com/article/251-upgrading-the-websites-php-version', array('target' => '_blank'));
      $class = 'notice notice-error notice-php-warning mailpoet_notice_server is-dismissible';

      return sprintf('<div class="%1$s" data-notice="php-version-outdated"><p>%2$s</p></div>', $class, $error);
    }
  }

  function ajaxDismissNoticeHandler() {
    set_transient('dismissed-php-version-outdated-notice', true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }

}
