<?php

namespace MailPoet\Form\Block;

use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Date extends Base {

  public function render($block) {
    $html = '';
    $html .= '<p class="mailpoet_paragraph">';
    $html .= $this->renderLabel($block);
    $html .= $this->renderDateSelect($block);
    $html .= '</p>';

    return $html;
  }

  private function renderDateSelect($block = []) {
    $html = '';

    $fieldName = 'data[' . $this->getFieldName($block) . ']';

    $dateFormats = $this->getDateFormats();

    // automatically select first date format
    $dateFormat = $dateFormats[$block['params']['date_type']][0];

    // set date format if specified
    if (isset($block['params']['date_format'])
    && strlen(trim($block['params']['date_format'])) > 0) {
      $dateFormat = $block['params']['date_format'];
    }

    // generate an array of selectors based on date format
    $dateSelectors = explode('/', $dateFormat);

    foreach ($dateSelectors as $dateSelector) {
      if ($dateSelector === 'DD') {
        $html .= '<select class="mailpoet_date_day" ';
        $html .= $this->getInputValidation($block, [
          'required-message' => WPFunctions::get()->__('Please select a day', 'mailpoet'),
        ]);
        $html .= 'name="' . $fieldName . '[day]" placeholder="' . __('Day', 'mailpoet') . '">';
        $html .= $this->getDays($block);
        $html .= '</select>';
      } else if ($dateSelector === 'MM') {
        $html .= '<select class="mailpoet_select mailpoet_date_month" ';
        $html .= $this->getInputValidation($block, [
          'required-message' => WPFunctions::get()->__('Please select a month', 'mailpoet'),
        ]);
        $html .= 'name="' . $fieldName . '[month]" placeholder="' . __('Month', 'mailpoet') . '">';
        $html .= $this->getMonths($block);
        $html .= '</select>';
      } else if ($dateSelector === 'YYYY') {
        $html .= '<select class="mailpoet_date_year" ';
        $html .= $this->getInputValidation($block, [
          'required-message' => WPFunctions::get()->__('Please select a year', 'mailpoet'),
        ]);
        $html .= 'name="' . $fieldName . '[year]" placeholder="' . __('Year', 'mailpoet') . '">';
        $html .= $this->getYears($block);
        $html .= '</select>';
      }
    }

    $html .= '<span class="mailpoet_error_' . $block['id'] . '"></span>';

    return $html;
  }

  public function getDateTypes() {
    return [
      'year_month_day' => WPFunctions::get()->__('Year, month, day', 'mailpoet'),
      'year_month' => WPFunctions::get()->__('Year, month', 'mailpoet'),
      'month' => WPFunctions::get()->__('Month (January, February,...)', 'mailpoet'),
      'year' => WPFunctions::get()->__('Year', 'mailpoet'),
    ];
  }

  public function getDateFormats() {
    return [
      'year_month_day' => ['MM/DD/YYYY', 'DD/MM/YYYY', 'YYYY/MM/DD'],
      'year_month' => ['MM/YYYY', 'YYYY/MM'],
      'year' => ['YYYY'],
      'month' => ['MM'],
    ];
  }
  public function getMonthNames() {
    return [__('January', 'mailpoet'), WPFunctions::get()->__('February', 'mailpoet'), WPFunctions::get()->__('March', 'mailpoet'), WPFunctions::get()->__('April', 'mailpoet'),
      WPFunctions::get()->__('May', 'mailpoet'), WPFunctions::get()->__('June', 'mailpoet'), WPFunctions::get()->__('July', 'mailpoet'), WPFunctions::get()->__('August', 'mailpoet'), WPFunctions::get()->__('September', 'mailpoet'),
      WPFunctions::get()->__('October', 'mailpoet'), WPFunctions::get()->__('November', 'mailpoet'), WPFunctions::get()->__('December', 'mailpoet'),
    ];
  }

  private function getMonths($block = []) {
    $defaults = [
      'selected' => null,
    ];

    // is default today
    if (!empty($block['params']['is_default_today'])) {
      $defaults['selected'] = (int)strftime('%m');
    }
    // merge block with defaults
    $block = array_merge($defaults, $block);

    $monthNames = $this->getMonthNames();

    $html = '';

    // empty value label
    $html .= '<option value="">' . __('Month', 'mailpoet') . '</option>';

    for ($i = 1; $i < 13; $i++) {
      $isSelected = ($i === $block['selected']) ? 'selected="selected"' : '';
      $html .= '<option value="' . $i . '" ' . $isSelected . '>';
      $html .= $monthNames[$i - 1];
      $html .= '</option>';
    }

    return $html;
  }

  private function getYears($block = []) {
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
      $isSelected = ($i === $block['selected']) ? 'selected="selected"' : '';
      $html .= '<option value="' . $i . '" ' . $isSelected . '>' . $i . '</option>';
    }

    return $html;
  }

  private function getDays($block = []) {
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
      $isSelected = ($i === $block['selected']) ? 'selected="selected"' : '';
      $html .= '<option value="' . $i . '" ' . $isSelected . '>' . $i . '</option>';
    }

    return $html;
  }

  public function convertDateToDatetime($date, $dateFormat) {
    $datetime = false;
    if ($dateFormat === 'datetime') {
      $datetime = $date;
    } else {
      $parsedDate = explode('/', $date);
      $parsedDateFormat = explode('/', $dateFormat);
      $yearPosition = array_search('YYYY', $parsedDateFormat);
      $monthPosition = array_search('MM', $parsedDateFormat);
      $dayPosition = array_search('DD', $parsedDateFormat);
      if (count($parsedDate) === 3) {
        // create date from any combination of month, day and year
        $parsedDate = [
          'year' => $parsedDate[$yearPosition],
          'month' => $parsedDate[$monthPosition],
          'day' => $parsedDate[$dayPosition],
        ];
      } else if (count($parsedDate) === 2) {
        // create date from any combination of month and year
        $parsedDate = [
          'year' => $parsedDate[$yearPosition],
          'month' => $parsedDate[$monthPosition],
          'day' => '01',
        ];
      } else if ($dateFormat === 'MM' && count($parsedDate) === 1) {
        // create date from month
        if ((int)$parsedDate[$monthPosition] === 0) {
          $datetime = '';
          $parsedDate = false;
        } else {
          $parsedDate = [
            'month' => $parsedDate[$monthPosition],
            'day' => '01',
            'year' => date('Y'),
          ];
        }
      } else if ($dateFormat === 'YYYY' && count($parsedDate) === 1) {
        // create date from year
        if ((int)$parsedDate[$yearPosition] === 0) {
          $datetime = '';
          $parsedDate = false;
        } else {
          $parsedDate = [
            'year' => $parsedDate[$yearPosition],
            'month' => '01',
            'day' => '01',
          ];
        }
      } else {
        $parsedDate = false;
      }
      if ($parsedDate) {
        $year = $parsedDate['year'];
        $month = $parsedDate['month'];
        $day = $parsedDate['day'];
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
