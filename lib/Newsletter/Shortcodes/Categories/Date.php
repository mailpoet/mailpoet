<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

class Date {
  static function process($format) {
    $date = new \DateTime('now');
    $available_formats = array(
      'd' => $date->format('d'),
      'dordinal' => $date->format('dS'),
      'dtext' => $date->format('l'),
      'm' => $date->format('m'),
      'mtext' => $date->format('F'),
      'y' => $date->format('Y')
    );
    if(!empty($available_formats[$format])) {
      return $available_formats[$format];
    }
    return (preg_match('/^custom_(.*?)$/', $format, $custom_format)) ?
      $date->format($custom_format[1]) :
      false;
  }
}