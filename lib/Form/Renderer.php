<?php
namespace MailPoet\Form;

use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;


class Renderer {
  // public: rendering method
  static function render($form = []) {
    $html = static::renderStyles($form);
    $html .= static::renderHTML($form);
    return $html;
  }

  static function renderStyles($form = [], $prefix = null) {
    $styles = new Util\Styles(static::getStyles($form));

    $html = '<style type="text/css">';
    $html .= '.mailpoet_hp_email_label{display:none;}'; // move honeypot field out of sight
    $html .= $styles->render($prefix);
    $html .= '</style>';

    return $html;
  }

  static function renderHTML($form = []) {
    if (isset($form['body']) && !empty($form['body'])) {
      return static::renderBlocks($form['body']);
    }
    return '';
  }

  static function getStyles($form = []) {
    if (isset($form['styles'])
    && strlen(trim($form['styles'])) > 0) {
      return strip_tags($form['styles']);
    } else {
      return Util\Styles::$default_styles;
    }
  }

  static function renderBlocks($blocks = [], $honeypot_enabled = true) {
    $settings = new SettingsController();
    // add honeypot for spambots
    $html = ($honeypot_enabled) ?
      '<label class="mailpoet_hp_email_label">' . WPFunctions::get()->__('Please leave this field empty', 'mailpoet') . '<input type="email" name="data[email]"></label>' :
      '';
    foreach ($blocks as $key => $block) {
      if ($block['type'] == 'submit' && $settings->get('captcha.type') === Captcha::TYPE_RECAPTCHA) {
        $site_key = $settings->get('captcha.recaptcha_site_token');
        $html .= '<div class="mailpoet_recaptcha" data-sitekey="' . $site_key . '">
          <div class="mailpoet_recaptcha_container"></div>
          <noscript>
            <div>
              <div style="width: 302px; height: 422px; position: relative;">
                <div style="width: 302px; height: 422px; position: absolute;">
                  <iframe src="https://www.google.com/recaptcha/api/fallback?k=' . $site_key . '" frameborder="0" scrolling="no" style="width: 302px; height:422px; border-style: none;">
                  </iframe>
                </div>
              </div>
              <div style="width: 300px; height: 60px; border-style: none; bottom: 12px; left: 25px; margin: 0px; padding: 0px; right: 25px; background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px;">
                <textarea id="g-recaptcha-response" name="data[recaptcha]" class="g-recaptcha-response" style="width: 250px; height: 40px; border: 1px solid #c1c1c1; margin: 10px 25px; padding: 0px; resize: none;" >
                </textarea>
              </div>
            </div>
          </noscript>
          <input class="mailpoet_recaptcha_field" type="hidden" name="recaptcha">
        </div>';
      }
      $html .= static::renderBlock($block) . PHP_EOL;
    }

    return $html;
  }

  static function renderBlock($block = []) {
    $html = '';
    switch ($block['type']) {
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
