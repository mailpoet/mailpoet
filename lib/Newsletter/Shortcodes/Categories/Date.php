<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

class Date {
  static function process(
    $action,
    $action_argument = false,
    $action_argument_value = false
  ) {
    $action_mapping = array(
      'd' => 'd',
      'dordinal' => 'dS',
      'dtext' => 'l',
      'm' => 'm',
      'mtext' => 'F',
      'y' => 'Y'
    );
    if(!empty($action_mapping[$action])) {
      return date_i18n($action_mapping[$action], current_time('timestamp'));
    }
    return ($action === 'custom' && $action_argument === 'format') ?
      date_i18n($action_argument_value, current_time('timestamp')) :
      false;
  }
}