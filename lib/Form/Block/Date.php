<?php
namespace MailPoet\Form\Block;

class Date extends Base {

  static function render($block) {
    $html = '';
    $html .= '<p class="mailpoet_paragraph">';
    $html .= static::renderLabel($block);
    $html .= static::renderDateSelect($block);
    $html .= '</p>';

    return $html;
  }

  private static function renderDateSelect($block = array()) {
    $html = '';

    $field_name = static::getFieldName($block);
    $field_validation = static::getInputValidation($block);

    $date_formats = static::getDateFormats();

    // automatically select first date format
    $date_format = $date_formats[$block['params']['date_type']][0];

    // set date format if specified
    if(isset($block['params']['date_format'])
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

    if(strlen(trim($value)) > 0) {
      $value = explode('-', $value);

      switch($block['params']['date_type']) {
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

    foreach($date_selectors as $date_selector) {
      if($date_selector === 'dd') {
        $block['selected'] = $day;
        $html .= '<select class="mailpoet_date_day" ';
        $html .= 'name="'.$field_name.'[day]" placeholder="'.__('Day').'">';
        $html .= static::getDays($block);
        $html .= '</select>';
      } else if($date_selector === 'mm') {
        $block['selected'] = $month;
        $html .= '<select class="mailpoet_date_month" ';
        $html .= 'name="'.$field_name.'[month]" placeholder="'.__('Month').'">';
        $html .= static::getMonths($block);
        $html .= '</select>';
      } else if($date_selector === 'yyyy') {
        $block['selected'] = $year;
        $html .= '<select class="mailpoet_date_year" ';
        $html .= 'name="'.$field_name.'[year]" placeholder="'.__('Year').'">';
        $html .= static::getYears($block);
        $html .= '</select>';
      }
    }

    return $html;
  }

  static function getDateTypes() {
    return array(
      'year_month_day' => __('Year, month, day'),
      'year_month' => __('Year, month'),
      'month' => __('Month (January, February,...)'),
      'year' => __('Year')
    );
  }

  static function getDateFormats() {
    return array(
      'year_month_day' => array('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy/mm/dd'),
      'year_month' => array('mm/yyyy', 'yyyy/mm'),
      'year' => array('yyyy'),
      'month' => array('mm')
    );
  }
  static function getMonthNames() {
    return array(__('January'), __('February'), __('March'), __('April'),
      __('May'), __('June'), __('July'), __('August'), __('September'),
      __('October'), __('November'), __('December')
    );
  }

  static function getMonths($block = array()) {
    $defaults = array(
      'selected' => null
    );

    // merge block with defaults
    $block = array_merge($defaults, $block);

    $month_names = static::getMonthNames();

    $html = '';

    // empty value label
    $html .= '<option value="">'.__('Month').'</option>';

    for($i = 1; $i < 13; $i++) {
      $is_selected = ($i === $block['selected']) ? 'selected="selected"' : '';
      $html .= '<option value="'.$i.'" '.$is_selected.'>';
      $html .= $month_names[$i - 1];
      $html .= '</option>';
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
    if(!empty($block['params']['is_default_today'])) {
      $defaults['selected'] = (int)strftime('%Y');
    }

    // merge block with defaults
    $block = array_merge($defaults, $block);

    $html = '';

    // empty value label
    $html .= '<option value="">'.__('Year').'</option>';

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
    if(!empty($block['params']['is_default_today'])) {
      $defaults['selected'] = (int)strftime('%d');
    }

    // merge block with defaults
    $block = array_merge($defaults, $block);

    $html = '';

    // empty value label
    $html .= '<option value="">'.__('Day').'</option>';

    // return days as an array
    for($i = 1; $i < 32; $i++) {
      $is_selected = ($i === $block['selected']) ? 'selected="selected"' : '';
      $html .= '<option value="'.$i.'" '.$is_selected.'>'.$i.'</option>';
    }

    return $html;
  }
}