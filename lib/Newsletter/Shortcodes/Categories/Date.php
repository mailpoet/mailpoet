<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;
use MailPoet\WP\Functions as WPFunctions;

class Date {
  static function process(
    $shortcode_details
  ) {
    $action_mapping = [
      'd' => 'd',
      'dordinal' => 'jS',
      'dtext' => 'l',
      'm' => 'm',
      'mtext' => 'F',
      'y' => 'Y',
    ];
    $wp = new WPFunctions();
    if (!empty($action_mapping[$shortcode_details['action']])) {
      return WPFunctions::get()->dateI18n($action_mapping[$shortcode_details['action']], $wp->currentTime('timestamp'));
    }
    return ($shortcode_details['action'] === 'custom' && $shortcode_details['action_argument'] === 'format') ?
      WPFunctions::get()->dateI18n($shortcode_details['action_argument_value'], $wp->currentTime('timestamp')) :
      false;
  }
}
