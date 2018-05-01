<?php

namespace MailPoet\Config;

use MailPoet\Util\Helpers;

class PHPVersionWarnings {

  function init($php_version, $is_enabled) {
    add_action('wp_ajax_dismissed_notice_handler', array(
      $this,
      'ajaxDismissNoticeHandler'
    ));
    $error = null;
    if (!$is_enabled) return $error;
    if (is_null($error)) $error = $this->checkPHP53Version($php_version);
    if (is_null($error)) $error = $this->checkPHP55Version($php_version);
    return $error;
  }

  function checkPHP53Version($php_version) {
    $error_string = null;
    if(version_compare($php_version, '5.5', '<')) {
      $error_string = 'Your website is running on PHP %s. MailPoet will require version 7 soon. Please consider upgrading your site\'s PHP version. [link]Your host can help you.[/link]';
      $error_string = sprintf($error_string, $php_version);
      $error = Helpers::replaceLinkTags(__($error_string, 'mailpoet'), 'https://beta.docs.mailpoet.com/article/251-upgrading-the-websites-php-version', array('target' => '_blank'));
      return $this->displayWPNotice($error, false);
    }
  }

  function checkPHP55Version($php_version) {
    $error_string = null;
    if(version_compare($php_version, '5.6', '<')) {
      $error_string = 'Your website is running on PHP %s. MailPoet will require version 7 by the end of the year. Please consider upgrading your site\'s PHP version. [link]Your host can help you.[/link]';
      $error_string = sprintf($error_string, $php_version);
      $error = Helpers::replaceLinkTags(__($error_string, 'mailpoet'), 'https://beta.docs.mailpoet.com/article/251-upgrading-the-websites-php-version', array('target' => '_blank'));
      return $this->displayWPNotice($error, true);
    }
  }

  private function displayWPNotice($message, $dismisable = false) {
    $class = 'notice notice-error notice-php-warning mailpoet_notice_server';
    if($dismisable) $class .= ' is-dismissible';

    if(!get_option('dismissed-php-version-outdated-notice', false)) {
      return sprintf('<div class="%1$s" data-notice="php-version-outdated"><p>%2$s</p></div>', $class, $message);
    }
  }

  function ajaxDismissNoticeHandler() {
    update_option('dismissed-php-version-outdated-notice', true);
  }

}