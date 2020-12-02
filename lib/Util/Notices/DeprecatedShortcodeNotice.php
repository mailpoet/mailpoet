<?php

namespace MailPoet\Util\Notices;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

/**
 * This can be removed after 2021-08-01
 */
class DeprecatedShortcodeNotice {
  const DISMISS_NOTICE_TIMEOUT_SECONDS = 15552000; // 6 months
  const OPTION_NAME = 'dismissed-deprecated-shortcode-notice';

  public function init($shouldDisplay) {
    if ($shouldDisplay && $this->isUsingDeprecatedShortcode()) {
      return $this->display();
    }
    return null;
  }

  public function isUsingDeprecatedShortcode() {
    global $wp_filter;// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $hook = 'mailpoet_newsletter_shortcode';
    if (empty($wp_filter[$hook])) return false;// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $callbacks = $wp_filter[$hook];// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    if (empty($callbacks->callbacks) || !is_array($callbacks->callbacks)) return false;
    foreach ($callbacks->callbacks as $callbackByPriority) {
      if (empty($callbackByPriority) || !is_array($callbackByPriority)) continue;
      foreach ($callbackByPriority as $callback) {
        if (!is_array($callback)) return true;// this is not our callback
        if (empty($callback['function']) || !is_array($callback['function'])) return true; // not our callback
        if (isset($callback['function'][1]) && $callback['function'][1] === 'handleOrderTotalShortcode') continue;
        if (isset($callback['function'][1]) && $callback['function'][1] === 'handleOrderDateShortcode') continue;
        return true;
      }
    }
  }

  public function display() {
    $errorString = __('MailPoet recently changed how custom email shortcodes work, you may need to update your custom shortcodes.', 'mailpoet');
    $getInTouchString = __('[link]See the documentation for necessary changes[/link]', 'mailpoet');
    $error = Helpers::replaceLinkTags($errorString, 'https://kb.mailpoet.com/article/160-create-a-custom-shortcode', [
      'target' => '_blank',
      'data-beacon-article' => '581f6faac697914aa838044f',
    ]);
    $error .= '<br><br>' . Helpers::replaceLinkTags($getInTouchString, 'https://www.mailpoet.com/let-us-handle-your-php-upgrade/', [
      'target' => '_blank',
      'class' => 'mailpoet-button mailpoet-button-small',
    ]);

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    return Notice::displayWarning($error, $extraClasses, self::OPTION_NAME);
  }

  public function disable() {
    WPFunctions::get()->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }
}
