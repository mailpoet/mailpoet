<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\WP\Functions as WPFunctions;

class Date {
  public static function process(
    $shortcodeDetails
  ) {
    $actionMapping = [
      'd' => 'd',
      'dordinal' => 'jS',
      'dtext' => 'l',
      'm' => 'm',
      'mtext' => 'F',
      'y' => 'Y',
    ];
    $wp = new WPFunctions();
    if (!empty($actionMapping[$shortcodeDetails['action']])) {
      return WPFunctions::get()->dateI18n($actionMapping[$shortcodeDetails['action']], $wp->currentTime('timestamp'));
    }
    return ($shortcodeDetails['action'] === 'custom' && $shortcodeDetails['action_argument'] === 'format') ?
      WPFunctions::get()->dateI18n($shortcodeDetails['action_argument_value'], $wp->currentTime('timestamp')) :
      false;
  }
}
