<?php

namespace MailPoet\Form;

use MailPoet\Form\Util\Styles;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;
use MailPoet\WP\Functions as WPFunctions;

class Renderer {
  /** @var Styles */
  private $styleUtils;

  /** @var SettingsController */
  private $settings;

  public function __construct(Styles $styleUtils, SettingsController $settings) {
    $this->styleUtils = $styleUtils;
    $this->settings = $settings;
  }

  public function renderStyles(array $form = [], string $prefix = null): string {
    $html = '<style type="text/css">';
    $html .= '.mailpoet_hp_email_label{display:none;}'; // move honeypot field out of sight
    $html .= $this->styleUtils->render($this->getStyles($form), $prefix);
    $html .= '</style>';

    return $html;
  }

  public function renderHTML(array $form = []): string {
    if (isset($form['body']) && !empty($form['body'])) {
      return $this->renderBlocks($form['body']);
    }
    return '';
  }

  public function getStyles(array $form = []): string {
    if (isset($form['styles'])
    && strlen(trim($form['styles'])) > 0) {
      return strip_tags($form['styles']);
    } else {
      return $this->styleUtils->getDefaultStyles();
    }
  }

  public function renderBlocks(array $blocks = [], bool $honeypotEnabled = true): string {
    // add honeypot for spambots
    $html = ($honeypotEnabled) ?
      '<label class="mailpoet_hp_email_label">' . WPFunctions::get()->__('Please leave this field empty', 'mailpoet') . '<input type="email" name="data[email]"></label>' :
      '';
    foreach ($blocks as $key => $block) {
      if ($block['type'] == 'submit' && $this->settings->get('captcha.type') === Captcha::TYPE_RECAPTCHA) {
        $siteKey = $this->settings->get('captcha.recaptcha_site_token');
        $html .= '<div class="mailpoet_recaptcha" data-sitekey="' . $siteKey . '">
          <div class="mailpoet_recaptcha_container"></div>
          <noscript>
            <div>
              <div style="width: 302px; height: 422px; position: relative;">
                <div style="width: 302px; height: 422px; position: absolute;">
                  <iframe src="https://www.google.com/recaptcha/api/fallback?k=' . $siteKey . '" frameborder="0" scrolling="no" style="width: 302px; height:422px; border-style: none;">
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
      $html .= $this->renderBlock($block) . PHP_EOL;
    }

    return $html;
  }

  private function renderBlock(array $block = []): string {
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
