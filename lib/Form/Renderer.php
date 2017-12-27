<?php
namespace MailPoet\Form;

use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Renderer {
  // public: rendering method
  static function render($form = array()) {
    $html = static::renderStyles($form);
    $html .= static::renderHTML($form);
    return $html;
  }

  static function renderStyles($form = array(), $prefix = null) {
    $styles = new Util\Styles(static::getStyles($form));

    $html = '<style type="text/css">';
    $html .= '.mailpoet_hp_email_label{display:none;}'; // move honeypot field out of sight
    $html .= $styles->render($prefix);
    $html .= '</style>';

    return $html;
  }

  static function renderHTML($form = array()) {
    if(isset($form['body']) && !empty($form['body'])) {
      return static::renderBlocks($form['body']);
    }
    return '';
  }

  static function getStyles($form = array()) {
    if(isset($form['styles'])
    && strlen(trim($form['styles'])) > 0) {
      return strip_tags($form['styles']);
    } else {
      return Util\Styles::$default_styles;
    }
  }

  static function renderBlocks($blocks = array(), $honeypot_enabled = true) {
    // add honeypot for spambots
    $html = ($honeypot_enabled) ?
      '<label class="mailpoet_hp_email_label">' . __('Please leave this field empty', 'mailpoet') . '<input type="email" name="data[email]"></label>' :
      '';
    foreach($blocks as $key => $block) {
      if($block['type'] == 'submit' && Setting::getValue('re_captcha.enabled')) {
        $site_key = Setting::getValue('re_captcha.site_token');
        $html .= '<div class="mailpoet_recaptcha" data-sitekey="'. $site_key .'">
          <div class="mailpoet_recaptcha_container"></div>
          <input class="mailpoet_recaptcha_field" type="hidden" name="recaptcha">
        </div>';
      }
      $html .= static::renderBlock($block) . PHP_EOL;
    }
    
    return $html;
  }

  static function renderBlock($block = array()) {
    $html = '';
    switch($block['type']) {
      case 'html':
        $html .= Block\Html::render($block);
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

      case 'segment':
        $html .= Block\Segment::render($block);
        break;

      case 'date':
        $html .= Block\Date::render($block);
        break;

      case 'select':
        $html .= Block\Select::render($block);
        break;

      case 'text':
        $html .= Block\Text::render($block);
        break;

      case 'textarea':
        $html .= Block\Textarea::render($block);
        break;

      case 'submit':
        $html .= Block\Submit::render($block);
        break;
    }
    return $html;
  }
}
