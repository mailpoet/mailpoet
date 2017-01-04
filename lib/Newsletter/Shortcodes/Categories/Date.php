<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

class Date {
  static function process($action) {
    $date = new \DateTime('now');
    $actions = array(
      'd' => $date->format('d'),
      'dordinal' => $date->format('dS'),
      'dtext' => $date->format('l'),
      'm' => $date->format('m'),
      'mtext' => $date->format('F'),
      'y' => $date->format('Y')
    );
    return (isset($actions[$action])) ? $actions[$action] : false;
  }
}