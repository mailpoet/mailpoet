<?php
namespace MailPoet\Form\Block;

use Carbon\Carbon;
use MailPoet\WP\Functions as WPFunctions;

class Date extends Base {

  static function render($block) {
    $html = '';
    $html .= '<p class="mailpoet_paragraph">';
    $html .= static::renderLabel($block);
    $html .= static::renderDateSelect($block);
    $html .= '</p>';

    return $html;
  }

  private static function renderDateSelect($block = []) {
    $html = '';

    $field_name = 'data[' . static::getFieldName($block) . ']';

    $date_formats = static::getDateFormats();

    // automatically select first date format
    $date_format = $date_formats[$block['params']['date_type']][0];

    // set date format if specified
    if (isset($block['params']['date_format'])
    && strlen(trim($block['params']['date_format'])) > 0) {
      $date_format = $block['params']['date_format'];
    }

    // generate an array of selectors based on date format
    $date_selectors = explode('/', $date_format);

    // format value if present
    $value = self::getFieldValue($block);
    $day = null;
    $month = null;
    $year = null;

    if (strlen(trim($value)) > 0) {
      $value = explode('-', $value);

      switch ($block['params']['date_type']) {
        case 'year_month_day':
          $year = (isset($value[0]) ? (int)$value[0] : null);
          $month = (isset($value[1]) ? (int)$value[1] : null);
          $day = (isset($value[2]) ? (int)$value[2] : null);
          break;

        case 'year_month':
          $year = (isset($value[0]) ? (int)$value[0] : null);
          $month = (isset($value[1]) ? (int)$value[1] : null);
          break;

        case 'month':
          $month = (isset($value[0]) ? (int)$value[0] : null);
          break;

        case 'year':
          $year = (isset($value[0]) ? (int)$value[0] : null);
          break;
      }
    }

    foreach ($date_selectors as $date_selector) {
      if ($date_selector === 'DD') {
        $block['selected'] = $day;
        $html .= '<select class="mailpoet_date_day" ';
        $html .= static::getInputValidation($block, [
          'required-message' => WPFunctions::get()->__('Please select a day', 'mailpoet'),
        ]);
        $html .= 'name="' . $field_name . '[day]" placeholder="' . __('Day', 'mailpoet') . '">';
        $html .= static::getDays($block);
        $html .= '</select>';
      } else if ($date_selector === 'MM') {
        $block['selected'] = $month;
        $html .= '<select class="mailpoet_select mailpoet_date_month" ';
        $html .= static::getInputValidation($block, [
          'required-message' => WPFunctions::get()->__('Please select a month', 'mailpoet'),
        ]);
        $html .= 'name="' . $field_name . '[month]" placeholder="' . __('Month', 'mailpoet') . '">';
        $html .= static::getMonths($block);
        $html .= '</select>';
      } else if ($date_selector === 'YYYY') {
        $block['selected'] = $year;
        $html .= '<select class="mailpoet_date_year" ';
        $html .= static::getInputValidation($block, [
          'required-message' => WPFunctions::get()->__('Please select a year', 'mailpoet'),
        ]);
        $html .= 'name="' . $field_name . '[year]" placeholder="' . __('Year', 'mailpoet') . '">';
        $html .= static::getYears($block);
        $html .= '</select>';
      }
    }

    $html .= '<span class="mailpoet_error_' . $block['id'] . '"></span>';

    return $html;
  }

  static function getDateTypes() {
    return [
      'year_month_day' => WPFunctions::get()->__('Year, month, day', 'mailpoet'),
      'year_month' => WPFunctions::get()->__('Year, month', 'mailpoet'),
      'month' => WPFunctions::get()->__('Month (January, February,...)', 'mailpoet'),
      'year' => WPFunctions::get()->__('Year', 'mailpoet'),
    ];
  }

  static function getDateFormats() {
    return [
      'year_month_day' => ['MM/DD/YYYY', 'DD/MM/YYYY', 'YYYY/MM/DD'],
      'year_month' => ['MM/YYYY', 'YYYY/MM'],
      'year' => ['YYYY'],
      'month' => ['MM'],
    ];
  }
  static function getMonthNames() {
    return [__('January', 'mailpoet'), WPFunctions::get()->__('February', 'mailpoet'), WPFunctions::get()->__('March', 'mailpoet'), WPFunctions::get()->__('April', 'mailpoet'),
      WPFunctions::get()->__('May', 'mailpoet'), WPFunctions::get()->__('June', 'mailpoet'), WPFunctions::get()->__('July', 'mailpoet'), WPFunctions::get()->__('August', 'mailpoet'), WPFunctions::get()->__('September', 'mailpoet'),
      WPFunctions::get()->__('October', 'mailpoet'), WPFunctions::get()->__('November', 'mailpoet'), WPFunctions::get()->__('December', 'mailpoet'),
    ];
  }

  static function getMonths($block = []) {
    $defaults = [
      'selected' => null,
    ];

    // merge block with defaults
    $block = array_merge($defaults, $block);

    $month_names = static::getMonthNames();

    $html = '';

    // empty value label
    $html .= '<option value="">' . __('Month', 'mailpoet') . '</option>';

    for ($i = 1; $i < 13; $i++) {
      $is_selected = ($i === $block['selected']) ? 'selected="selected"' : '';
      $html .= '<option value="' . $i . '" ' . $is_selected . '>';
      $html .= $month_names[$i - 1];
      $html .= '</option>';
    }

    return $html;
  }

  static function getYears($block = []) {
    $defaults = [
      'selected' => null,
      'from' => (int)strftime('%Y') - 100,
      'to' => (int)strftime('%Y'),
    ];

    // is default today
    if (!empty($block['params']['is_default_today'])) {
      $defaults['selected'] = (int)strftime('%Y');
    }

    // merge block with defaults
    $block = array_merge($defaults, $block);

    $html = '';

    // empty value label
    $html .= '<option value="">' . __('Year', 'mailpoet') . '</option>';

    // return years as an array
    for ($i = (int)$block['to']; $i > (int)($block['from'] - 1); $i--) {
      $is_selected = ($i === $block['selected']) ? 'selected="selected"' : '';
      $html .= '<option value="' . $i . '" ' . $is_selected . '>' . $i . '</option>';
    }

    return $html;
  }

  static function getDays($block = []) {
    $defaults = [
      'selected' => null,
    ];
    // is default today
    if (!empty($block['params']['is_default_today'])) {
      $defaults['selected'] = (int)strftime('%d');
    }

    // merge block with defaults
    $block = array_merge($defaults, $block);

    $html = '';

    // empty value label
    $html .= '<option value="">' . __('Day', 'mailpoet') . '</option>';

    // return days as an array
    for ($i = 1; $i < 32; $i++) {
      $is_selected = ($i === $block['selected']) ? 'selected="selected"' : '';
      $html .= '<option value="' . $i . '" ' . $is_selected . '>' . $i . '</option>';
    }

    return $html;
  }

  static function convertDateToDatetime($date, $date_format) {
    $datetime = false;
    if ($date_format === 'datetime') {
      $datetime = $date;
    } else {
      $parsed_date = explode('/', $date);
      $parsed_date_format = explode('/', $date_format);
      $year_position = array_search('YYYY', $parsed_date_format);
      $month_position = array_search('MM', $parsed_date_format);
      $day_position = array_search('DD', $parsed_date_format);
      if (count($parsed_date) === 3) {
        // create date from any combination of month, day and year
        $parsed_date = [
          'year' => $parsed_date[$year_position],
          'month' => $parsed_date[$month_position],
          'day' => $parsed_date[$day_position],
        ];
      } else if (count($parsed_date) === 2) {
        // create date from any combination of month and year
        $parsed_date = [
          'year' => $parsed_date[$year_position],
          'month' => $parsed_date[$month_position],
          'day' => '01',
        ];
      } else if ($date_format === 'MM' && count($parsed_date) === 1) {
        // create date from month
        if ((int)$parsed_date[$month_position] === 0) {
          $datetime = '';
          $parsed_date = false;
        } else {
          $parsed_date = [
            'month' => $parsed_date[$month_position],
            'day' => '01',
            'year' => date('Y'),
          ];
        }
      } else if ($date_format === 'YYYY' && count($parsed_date) === 1) {
        // create date from year
        if ((int)$parsed_date[$year_position] === 0) {
          $datetime = '';
          $parsed_date = false;
        } else {
          $parsed_date = [
            'year' => $parsed_date[$year_position],
            'month' => '01',
            'day' => '01',
          ];
        }
      } else {
        $parsed_date = false;
      }
      if ($parsed_date) {
        $year = $parsed_date['year'];
        $month = $parsed_date['month'];
        $day = $parsed_date['day'];
        // if all date parts are set to 0, date value is empty
        if ((int)$year === 0 && (int)$month === 0 && (int)$day === 0) {
          $datetime = '';
        } else {
          if ((int)$year === 0) $year = date('Y');
          if ((int)$month === 0) $month = date('m');
          if ((int)$day === 0) $day = date('d');
          $datetime = sprintf(
            '%s-%s-%s 00:00:00',
            $year,
            $month,
            $day
          );
        }
      }
    }
    if ($datetime !== false && !empty($datetime)) {
      try {
        $datetime = Carbon::parse($datetime)->toDateTimeString();
      } catch (\Exception $e) {
        $datetime = false;
      }
    }
    return $datetime;
  }
}
