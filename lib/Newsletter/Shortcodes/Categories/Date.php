<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;

class Date {
  static $translations = array(
    // l - full textual representation of the day of the week
    'Monday' => 'Monday',
    'Tuesday' => 'Tuesday',
    'Wednesday' => 'Wednesday',
    'Thursday' => 'Thursday',
    'Friday' => 'Friday',
    'Saturday' => 'Saturday',
    'Sunday' => 'Sunday',
    // D - textual representation of a day, three letters
    'Mon' => 'Mon',
    'Tue' => 'Tue',
    'Wed' => 'Wed',
    'Thu' => 'Thu',
    'Fri' => 'Fri',
    'Sat' => 'Sat',
    'Sun' => 'Sun',
    // F - full textual representation of a month
    'January' => 'January',
    'February' => 'February',
    'March' => 'March',
    'April' => 'April',
    'May' => 'May',
    'June' => 'June',
    'July' => 'July',
    'August' => 'August',
    'September' => 'September',
    'October' => 'October',
    'November' => 'November',
    'December' => 'December',
    // M - short textual representation of a month, three letters
    'Jan' => 'Jan',
    'Feb' => 'Feb',
    'Mar' => 'Mar',
    'Apr' => 'Apr',
    'May' => 'May',
    'Jun' => 'Jun',
    'Jul' => 'Jul',
    'Aug' => 'Aug',
    'Sep' => 'Sep',
    'Oct' => 'Oct',
    'Nov' => 'Nov',
    'Dec' => 'Dec',
    // a - lowercase Ante meridiem and Post meridiem
    'am' => 'am',
    'pm' => 'pm',
    // A - uppercase Ante meridiem and Post meridiem
    'AM' => 'AM',
    'PM' => 'PM'
  );

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
      return ShortcodesHelper::translateShortcode(self::$translations, $action_formats[$action]);
    }
    return ($action === 'custom' && $action_argument === 'format') ?
      ShortcodesHelper::translateShortcode(self::$translations, $date->format($action_argument_value)) :
      false;
  }
}