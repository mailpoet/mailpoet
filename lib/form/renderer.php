<?php
namespace MailPoet\Form;

if(!defined('ABSPATH')) exit;

class Renderer {

    // public: rendering method
  public static function render($form = array()) {
    $html = '';

    $html .= static::renderStyles($form);

    $html .= static::renderHTML($form);

    return $html;
  }

  public static function renderStyles($form = array()) {
    $html = '';

    // styles
    $html .= '<style type="text/css">';
    $html .= static::getStyles($form);
    $html .= '</style>';

    return $html;
  }

  public static function renderHTML($form = array()) {
    if(isset($form['data']['body']) && !empty($form['data']['body'])) {
      // render blocks
      return static::renderBlocks($form['data']['body']);
    }
    return '';
  }

  public static function getStyles($form = array()) {

    $default = <<<EOL
/* form */
.mailpoet_form {

}

/* paragraphs (label + input) */
.mailpoet_paragraph {

}

/* labels */
.mailpoet_input_label,
.mailpoet_textarea_label,
.mailpoet_select_label,
.mailpoet_radio_label,
.mailpoet_list_label,
.mailpoet_checkbox_label,
.mailpoet_date_label {
  display:block;
}

/* inputs */
.mailpoet_input,
.mailpoet_textarea,
.mailpoet_select,
.mailpoet_radio,
.mailpoet_checkbox,
.mailpoet_date {
  display:block;
}

.mailpoet_validate_success {
  color:#468847;
}

.mailpoet_validate_error {
  color:#B94A48;
}
EOL;

    if(isset($form['data']['styles']) && strlen(trim($form['data']['styles'])) > 0) {
      return strip_tags($form['data']['styles']);
    } else {
      return $default;
    }
  }

    // public: date related methods
  public static function getDateTypes() {
    return array(
      'year_month_day' => __('Year, month, day'),
      'year_month' => __('Year, month'),
      'month' => __('Month (January, February,...)'),
      'year' => __('Year')
    );
  }

  public static function getDateFormats() {
    return array(
      'year_month_day' => array('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy/mm/dd'),
      'year_month' => array('mm/yyyy', 'yyyy/mm'),
      'year' => array('yyyy'),
      'month' => array('mm')
    );
  }
  public static function getMonthNames() {
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

  // private: rendering methods
  private static function renderBlocks($blocks = array()) {
    $html = '';

          // generate block
    foreach ($blocks as $key => $block) {
      $html .= static::renderBlock($block)."\n";
    }

    return $html;
  }

  private static function renderBlock($block = array()) {
          // get field name
    $field_name = static::getFieldName($block);

          // set label
    $field_label = (isset($block['params']['label'])) ? $block['params']['label'] : '';

    $html = '';

          // render block template depending on its type
    switch ($block['type']) {
      case 'html':
        if(isset($block['params']['text']) && $block['params']['text']) {
          $text = html_entity_decode($block['params']['text'], ENT_QUOTES);
        }
        if(isset($block['params']['nl2br']) && $block['params']['nl2br']) {
          $text = nl2br($text);
        }
        $html .= '<p class="mailpoet_paragraph">';
        $html .= $text;
        $html .= '</p>';
      break;

      case 'divider':
        $html .= '<hr class="mailpoet_divider" />';
      break;

      case 'checkbox':
      case 'radio':
        // BEGIN: container
        $html .= '<p class="mailpoet_paragraph">';

        // render label
        $html .= static::renderLabel($block);

        // create hidden default value
        // $html .= '<input type="hidden" name="'.$field_name.'" value="0" '.static::getInputValidation($block).'/>';

        // display values
        foreach($block['params']['values'] as $value) {
          $is_checked = (isset($value['is_checked']) && $value['is_checked']) ? 'checked="checked"' : '';

          if($block['type'] === 'radio') {
            $html .= '<label class="mailpoet_radio_label">';
            $html .= '<input type="radio" class="mailpoet_radio" name="'.$field_name.'" value="'.$value['value'].'" '.$is_checked;
          } else {
            $html .= '<label class="mailpoet_checkbox_label">';
            $html .= '<input type="checkbox" class="mailpoet_checkbox" name="'.$field_name.'" value="1" '.$is_checked;
          }
          $html .= static::getInputValidation($block);
          $html .= ' />'.$value['value'];
          $html .= '</label>';
        }

        // END: container
        $html .= '</p>';
      break;

      case 'list':
        // BEGIN: container
        $html .= '<p class="mailpoet_paragraph">';

        // render label
        $html .= static::renderLabel($block);

        if(!empty($block['params']['values'])) {
          // display values
          foreach($block['params']['values'] as $value) {
            if(!isset($value['list']) || !isset($value['list_name'])) continue;

            $is_checked = (isset($value['is_checked']) && $value['is_checked']) ? 'checked="checked"' : '';

            $html .= '<label class="mailpoet_checkbox_label">';
            $html .= '<input type="checkbox" class="mailpoet_checkbox" name="'.$field_name.'" value="'.$value['list'].'" '.$is_checked;
            $html .= static::getInputValidation($block);
            $html .= ' />'.$value['list_name'];
            $html .= '</label>';
          }
        }

        // END: container
        $html .= '</p>';
      break;

      case 'date':
        // BEGIN: container
        $html .= '<p class="mailpoet_paragraph">';

        // render label
        $html .= static::renderLabel($block);

        // render date picker
        $html .= static::renderDateSelect($field_name, $block);

        // END: container
        $html .= '</p>';
      break;

      case 'select':
        // BEGIN: container
        $html .= '<p class="mailpoet_paragraph">';

        // render label
        $html .= static::renderLabel($block);

        $html .= '<select class="mailpoet_select" name="'.$field_name.'">';

        if(isset($block['params']['label_within']) && $block['params']['label_within']) {
          $html .= '<option value="">'.$field_label.'</option>';
        }

        foreach($block['params']['values'] as $value) {
          $is_selected = (isset($value['is_checked']) && $value['is_checked']) ? 'selected="selected"' : '';
          $html .= '<option value="'.$value['value'].'" '.$is_selected.'>'.$value['value'].'</option>';
        }
        $html .= '</select>';

        // END: container
        $html .= '</p>';
      break;

      case 'input':
        // BEGIN: container
        $html .= '<p class="mailpoet_paragraph">';

        // render label
        $html .= static::renderLabel($block);

        $html .= '<input type="text" class="mailpoet_input" name="'.$field_name.'" title="'.$field_label.'" ';
        if(isset($block['params']['value'])) {
          $html .= 'value="'.$block['params']['value'].'"';
        }

        // set placeholder if label has to be displayed within the input
        $html .= static::renderInputPlaceholder($block);
        // get input validation
        $html .= static::getInputValidation($block);
        $html .= '/>';

        // END: container
        $html .= '</p>';
      break;

      case 'textarea':
        // BEGIN: container
        $html .= '<p class="mailpoet_paragraph">';

        // render label
        $html .= static::renderLabel($block);
        $lines = (isset($block['params']['lines']) ? (int)$block['params']['lines'] : 1);
        $html .= '<textarea name="'.$field_name.'" class="mailpoet_textarea" rows="'.$lines.'"';
        // set placeholder if label has to be displayed within the input
        $html .= static::renderInputPlaceholder($block);
        // get input validation
        $html .= static::getInputValidation($block);
        $html .= '></textarea>';

        // END: container
        $html .= '</p>';
      break;

      case 'submit':
        $html .= '<input class="mailpoet_submit" type="submit" value="'.$field_label.'" />';
      break;

      default:
        $html .= $block['type'];
      break;
    }

    return $html;
  }

  private static function getInputValidation($block) {
    return 'data-validation-engine="'.static::getInputValidationRules($block).'"';
  }

  private static function getInputValidationRules($block) {
    $rules = array();

    // if it's the email field, it's mandatory and needs to be valid
    if($block['field'] === 'email') {
      $rules[] = 'required';
      $rules[] = 'custom[email]';
    }

    // if it's the list field, at least one option needs to be selected
    if($block['field'] === 'list') {
      $rules[] = 'required';
    }

    // check if the field is required
    if(isset($block['params']['required']) && (bool)$block['params']['required'] === true) {
      $rules[] = 'required';
    }

    // check for validation rules
    if(isset($block['params']['validate'])) {
      if(is_array($block['params']['validate'])) {
        // handle multiple validation rules
        foreach($block['params']['validate'] as $rule) {
          $rules[] = 'custom['.$rule.']';
        }
      } else if(strlen(trim($block['params']['validate'])) > 0) {
        // handle single validation rule
        $rules[] = 'custom['.$block['params']['validate'].']';
      }
    }

    // generate string if there is at least one rule to validate against
    if(empty($rules)) {
      return '';
    } else {
      // make sure rules are not duplicated
      $rules = array_unique($rules);
      return 'validate['.join(',', $rules).']';
    }
  }


  private static function renderLabel($block) {
    $html = '';
          // if the label is displayed as a placeholder, we don't display a label outside
    if(isset($block['params']['label_within']) && $block['params']['label_within']) {
      return $html;
    }
    if(isset($block['params']['label']) && strlen(trim($block['params']['label'])) > 0) {
      $html .= '<label class="mailpoet_'.$block['type'].'_label">'.$block['params']['label'];

      if(isset($block['params']['required']) && $block['params']['required']) {
        $html .= ' <span class="mailpoet_required">*</span>';
      }

      $html .= '</label>';
    }
    return $html;
  }

  private static function renderInputPlaceholder($block) {
    $html = '';
    // if the label is displayed as a placeholder,
    if(isset($block['params']['label_within']) && $block['params']['label_within']) {
      // display only label
      if(isset($block['params']['label']) && strlen(trim($block['params']['label'])) > 0) {
        $html .= ' placeholder="';
        $html .= $block['params']['label'];
        // add an asterisk if it's a required field
        if(isset($block['params']['required']) && $block['params']['required']) {
          $html .= ' *';
        }
        $html .= '" ';
      }
    }
    return $html;
  }

  // return field name depending on block data
  private static function getFieldName($block = array()) {
    return $block['field'];
  }

  // render block wrapper's header
  private static function renderBlockHeader() {
    $html = '';

    $html .= '<p class="mailpoet_paragraph">';

    return $html;
  }

  // render block wrapper's footer
  private static function renderBlockFooter() {
    $html = '';
    // END: container
    $html .= '</p>';

    return $html;
  }

  // css inlining
  private static function getMonths($block = array()) {

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

  private static function getYears($block = array()) {
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

  private static function getDays($block = array()) {
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

  private static function renderDateSelect($field_name, $block = array()) {
    $html = '';

    // get date format
    $date_formats = static::getDateFormats();

    // automatically select first date format
    $date_format = $date_formats[$block['params']['date_type']][0];

    // set date format if specified
    if(isset($block['params']['date_format']) && strlen(trim($block['params']['date_format'])) > 0) {
      $date_format = $block['params']['date_format'];
    }

    // generate an array of selectors based on date format
    $date_selectors = explode('/', $date_format);

    // render each date selector
    foreach($date_selectors as $date_selector) {
      if($date_selector === 'dd') {
        // render days
        $html .= '<select class="mailpoet_date_day" name="'.$field_name.'[day]" placeholder="'.__('Day').'">';
        $html .= static::getDays($block);
        $html .= '</select>';
      } else if($date_selector === 'mm') {
        // render months
        $html .= '<select class="mailpoet_date_month" name="'.$field_name.'[month]" placeholder="'.__('Month').'">';
        $html .= static::getMonths($block);
        $html .= '</select>';
      } else if($date_selector === 'yyyy') {
        // render years
        $html .= '<select class="mailpoet_date_year" name="'.$field_name.'[year]" placeholder="'.__('Year').'">';
        $html .= static::getYears($block);
        $html .= '</select>';
      }
    }

    return $html;
  }

  public static function getExports($form = null) {
    return array(
      'html'      => static::getExport('html', $form),
      'php'       => static::getExport('php', $form),
      'iframe'    => static::getExport('iframe', $form),
      'shortcode' => static::getExport('shortcode', $form),
    );
  }

  public static function getExport($type = 'html', $form = null) {
    switch($type) {
      case 'iframe':
        // generate url to load iframe's content
        $iframe_url = add_query_arg(array(
          'mailpoet_page' => 'mailpoet_form_iframe',
          'mailpoet_form' => $form['form']
        ), site_url());

        // generate iframe
        return '<iframe '.
        'width="100%" '.
        'scrolling="no" '.
        'frameborder="0" '.
        'src="'.$iframe_url.'" '.
        'class="mailpoet_form_iframe" '.
        'vspace="0" '.
        'tabindex="0" '.
        //'style="position: static; top: 0pt; margin: 0px; border-style: none; height: 330px; left: 0pt; visibility: visible;" '. // TODO: need to find a solution for Height.
        'marginwidth="0" '.
        'marginheight="0" '.
        'hspace="0" '.
        'allowtransparency="true"></iframe>';
      break;

      case 'php':
        $output = array(
          '$form_widget = new \MailPoet\Form\Widget();',
          'echo $form_widget->widget(array(\'form\' => '.(int)$form['form'].', \'form_type\' => \'php\'));'
          );
        return join("\n", $output);
      break;

      case 'html':
        // TODO: get locale setting in order to load translations
        $wp_locale = \get_locale();

        $output = array();

        $output[] = '<!-- BEGIN Scripts : you should place them in the header of your theme -->';

                        // jQuery
        $output[] = '<script type="text/javascript" src="'.includes_url().'js/jquery/jquery.js'.'?mpv='.MAILPOET_VERSION.'"></script>';

                        // (JS) form validation
        $output[] = '<script type="text/javascript" src="'.plugins_url('wysija-newsletters/'.'lib/jquery.validationEngine.js?mpv='.MAILPOET_VERSION).'"></script>';
        $output[] = '<script type="text/javascript" src="'.plugins_url('wysija-newsletters/'.'lib/jquery.validationEngine-en.js?mpv='.MAILPOET_VERSION).'"></script>';

                        // (CSS) form validation styles
        $output[] = '<link rel="stylesheet" type="text/css" href="'.plugins_url('wysija-newsletters/'.'lib/validationEngine.jquery.css?mpv='.MAILPOET_VERSION).'">';

                        // (JS) form submission
        $output[] = '<script type="text/javascript" src="'.plugins_url('wysija-newsletters/'.'www/mailpoet_form_subscribe.js?mpv='.MAILPOET_VERSION).'"></script>';

                        // (JS) variables...
        $output[] = '<script type="text/javascript">';
        $output[] = '   var MailPoetData = MailPoetData || {';
        $output[] = '       is_rtl: '.((int)is_rtl()).",";
        $output[] = '       ajax_url: "'.admin_url('admin-ajax.php').'"';
        $output[] = '   };';
        $output[] = '</script>';
        $output[] = '<!--END Scripts-->';

        $form_widget = new Widget();
        $output[] = $form_widget->widget(array(
          'form' => (int)$form['form'],
          'form_type' => 'php'
        ));
        return join("\n", $output);
      break;

      case 'shortcode':
        return '[mailpoet_form id="'.(int)$form['form'].'"]';
      break;
    }
  }
}