<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

class Date {
  /*
    {
      text: '<%= __('Current day of the month number') %>',
      shortcode: 'date:d',
    },
    {
      text: '<%= __('Current day of the month in ordinal, ie. 2nd, 3rd, etc.') %>',
      shortcode: 'date:dordinal',
    },
    {
      text: '<%= __('Full name of current day') %>',
      shortcode: 'date:dtext',
    },
    {
      text: '<%= __('Current month number') %>',
      shortcode: 'date:m',
    },
    {
      text: '<%= __('Full name of current month') %>',
      shortcode: 'date:mtext',
    },
    {
      text: '<%= __('Year') %>',
      shortcode: 'date:y',
    }
   */
  static function process($action) {
    $date = new \DateTime('now');
    $actions = array(
      'd' => $date->format('d'),
      'dordinal' => $date->format('dS'),
      'dtext' => $date->format('D'),
      'm' => $date->format('m'),
      'mtext' => $date->format('F'),
      'y' => $date->format('Y')
    );
    return (isset($actions[$action])) ? $actions[$action] : false;
  }
}