<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\WP\Functions as WPFunctions;

class Date {
  static function process(
    $shortcode_details
  ) {
    $action_mapping = array(
      'd' => 'd',
      'dordinal' => 'dS',
      'dtext' => 'l',
      'm' => 'm',
      'mtext' => 'F',
      'y' => 'Y'
    );
    if(!empty($action_mapping[$shortcode_details['action']])) {
      return date_i18n($action_mapping[$shortcode_details['action']], WPFunctions::currentTime('timestamp'));
    }
    return ($shortcode_details['action'] === 'custom' && $shortcode_details['action_argument'] === 'format') ?
      date_i18n($shortcode_details['action_argument_value'], WPFunctions::currentTime('timestamp')) :
      false;
  }
}