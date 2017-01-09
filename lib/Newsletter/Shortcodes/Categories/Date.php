<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

class Date {
  static function process(
    $action,
    $action_argument = false,
    $action_argument_value = false
  ) {
    $date = new \DateTime('now');
    $action_formats = array(
      'd' => $date->format('d'),
      'dordinal' => $date->format('dS'),
      'dtext' => $date->format('l'),
      'm' => $date->format('m'),
      'mtext' => $date->format('F'),
      'y' => $date->format('Y')
    );
    if(!empty($action_formats[$action])) {
      return $action_formats[$action];
    }
    return ($action === 'custom' && $action_argument === 'format') ?
      $date->format($action_argument_value) :
      false;
  }
}