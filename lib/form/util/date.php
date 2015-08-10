<?php
namespace MailPoet\Form\Util;

class Date {
  static function getTypes() {
    return array(
      'year_month_day' => __('Year, month, day'),
      'year_month' => __('Year, month'),
      'month' => __('Month (January, February,...)'),
      'year' => __('Year')
    );
  }

  static function getFormats() {
    return array(
      'year_month_day' => array('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy/mm/dd'),
      'year_month' => array('mm/yyyy', 'yyyy/mm'),
      'year' => array('yyyy'),
      'month' => array('mm')
    );
  }
  static function getMonthNames() {
    return array(
      __('January'),
      __('February'),
      __('March'),
      __('April'),
      __('May'),
      __('June'),
      __('July'),
      __('August'),
      __('September'),
      __('October'),
      __('November'),
      __('December')
    );
  }

  static function getMonths($block = array()) {

    $defaults = array(
      'selected' => null
    );

    // is default today
    if(isset($block['params']['is_default_today']) && (bool)$block['params']['is_default_today'] === true) {
      $defaults['selected'] = (int)strftime('%m');
    }

    // merge block with defaults
    $block = array_merge($defaults, $block);

    $month_names = static::getMonthNames();

    $html = '';
    for($i = 1; $i < 13; $i++) {
      $is_selected = ($i === $block['selected']) ? 'selected="selected"' : '';
      $html .= '<option value="'.$i.'" '.$is_selected.'>'.$month_names[$i - 1].'</option>';
    }

    return $html;
  }

  static function getYears($block = array()) {
    $defaults = array(
      'selected' => null,
      'from' => (int)strftime('%Y') - 100,
      'to' => (int)strftime('%Y')
    );
    // is default today
    if(isset($block['params']['is_default_today']) && (bool)$block['params']['is_default_today'] === true) {
      $defaults['selected'] = (int)strftime('%Y');
    }

    // merge block with defaults
    $block = array_merge($defaults, $block);

    $html = '';

    // return years as an array
    for($i = (int)$block['to']; $i > (int)($block['from'] - 1); $i--) {
      $is_selected = ($i === $block['selected']) ? 'selected="selected"' : '';
      $html .= '<option value="'.$i.'" '.$is_selected.'>'.$i.'</option>';
    }

    return $html;
  }

  static function getDays($block = array()) {
    $defaults = array(
      'selected' => null
    );
    // is default today
    if(isset($block['params']['is_default_today']) && (bool)$block['params']['is_default_today'] === true) {
      $defaults['selected'] = (int)strftime('%d');
    }

    // merge block with defaults
    $block = array_merge($defaults, $block);

    $html = '';

    // return days as an array
    for($i = 1; $i < 32; $i++) {
      $is_selected = ($i === $block['selected']) ? 'selected="selected"' : '';
      $html .= '<option value="'.$i.'" '.$is_selected.'>'.$i.'</option>';
    }

    return $html;
  }
}