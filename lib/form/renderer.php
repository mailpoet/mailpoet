<?php
namespace MailPoet\Form;
use MailPoet\Form\Block;
use MailPoet\Form\Util;

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
    if(isset($form['data']['styles'])
    && strlen(trim($form['data']['styles'])) > 0) {
      return strip_tags($form['data']['styles']);
    } else {
      return Util\Styles::getDefaults();
    }
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
    $html = '';

          // render block template depending on its type
    switch ($block['type']) {
      case 'html':
        $html .= Block\HTML::render($block);
      break;

      case 'divider':
        $html .= Block\Divider::render();
      break;

      case 'checkbox':
        $html .= Block\Checkbox::render($block);
      break;

      case 'radio':
        $html .= Block\Radio::render($block);
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
        $html .= Block\Input::render($block);
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
        $html .= Block\Submit::render($block);
      break;

      default:
        $html .= $block['type'];
      break;
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