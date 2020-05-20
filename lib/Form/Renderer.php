<?php

namespace MailPoet\Form;

use MailPoet\Form\Util\Styles;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;

class Renderer {
  /** @var Styles */
  private $styleUtils;

  /** @var SettingsController */
  private $settings;

  /** @var BlocksRenderer */
  private $blocksRenderer;

  public function __construct(
    Styles $styleUtils,
    SettingsController $settings,
    BlocksRenderer $blocksRenderer
  ) {
    $this->styleUtils = $styleUtils;
    $this->settings = $settings;
    $this->blocksRenderer = $blocksRenderer;
  }

  public function renderStyles(array $form = [], string $prefix = null): string {
    $html = '<style type="text/css">';
    $html .= '.mailpoet_hp_email_label{display:none!important;}'; // move honeypot field out of sight
    $html .= $this->styleUtils->prefixStyles($this->getCustomStyles($form), $prefix);
    $html .= $this->renderFormDivWrapperStyles($form, $prefix);
    $html .= '</style>';

    return $html;
  }

  public function renderHTML(array $form = []): string {
    if (isset($form['body']) && !empty($form['body'])) {
      return $this->renderBlocks($form['body'], $form['settings'] ?? []);
    }
    return '';
  }

  public function getCustomStyles(array $form = []): string {
    if (isset($form['styles'])
    && strlen(trim($form['styles'])) > 0) {
      return strip_tags($form['styles']);
    } else {
      return $this->styleUtils->getDefaultCustomStyles();
    }
  }

  public function renderBlocks(array $blocks = [], array $formSettings = [], bool $honeypotEnabled = true): string {
    // add honeypot for spambots
    $html = ($honeypotEnabled) ? $this->renderHoneypot() : '';
    foreach ($blocks as $key => $block) {
      if ($block['type'] == 'submit' && $this->settings->get('captcha.type') === Captcha::TYPE_RECAPTCHA) {
        $html .= $this->renderReCaptcha();
      }
      if (in_array($block['type'], ['column', 'columns'])) {
        $blocks = $block['body'] ?? [];
        $html .= $this->blocksRenderer->renderContainerBlock($block, $this->renderBlocks($blocks, $formSettings, false)) . PHP_EOL;
      } else {
        $html .= $this->blocksRenderer->renderBlock($block, $formSettings) . PHP_EOL;
      }
    }
    return $html;
  }

  private function renderHoneypot(): string {
    return '<label class="mailpoet_hp_email_label">' . __('Please leave this field empty', 'mailpoet') . '<input type="email" name="data[email]"/></label>';
  }

  private function renderReCaptcha(): string {
    $siteKey = $this->settings->get('captcha.recaptcha_site_token');
    return '<div class="mailpoet_recaptcha" data-sitekey="' . $siteKey . '">
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

  private function renderFormDivWrapperStyles(array $form, string $selector = null): string {
    if (is_null($selector)) return '';
    if (!isset($form['settings'])) return '';
    $formSettings = $form['settings'];
    $styles = [];

    if (isset($formSettings['backgroundColor'])) {
      $styles[] = 'background-color: ' . trim($formSettings['backgroundColor']);
    }

    if (isset($formSettings['border_size']) && isset($formSettings['border_color'])) {
      $styles[] = 'border: ' . $formSettings['border_size'] . 'px solid ' . $formSettings['border_color'];
    }

    if (isset($formSettings['border_radius'])) {
      $styles[] = 'border-radius: ' . $formSettings['border_radius'] . 'px';
    }

    if (isset($formSettings['background_image_url'])) {
      $styles[] = 'background-image: url(' . trim($formSettings['background_image_url']) . ')';
      $backgroundPosition = 'center';
      $backgroundRepeat = 'no-repeat';
      $backgroundSize = 'cover';
      if (isset($formSettings['background_image_display']) && $formSettings['background_image_display'] === 'fit') {
        $backgroundPosition = 'center top';
        $backgroundSize = 'contain';
      }
      if (isset($formSettings['background_image_display']) && $formSettings['background_image_display'] === 'tile') {
        $backgroundRepeat = 'repeat';
        $backgroundSize = 'auto';
      }
      $styles[] = 'background-position: ' . $backgroundPosition;
      $styles[] = 'background-repeat: ' . $backgroundRepeat;
      $styles[] = 'background-size: ' . $backgroundSize;
    }
    $media = "@media (max-width: 500px) {{$selector} {background-image: none;}}";

    return $selector . '{' . join(';', $styles) . '}' . $media;
  }

  public function renderFormElementStyles(array $form): string {
    if (!isset($form['settings'])) return '';
    $formSettings = $form['settings'];
    $styles = [];

    if (isset($formSettings['fontColor'])) {
      $styles[] = 'color: ' . trim($formSettings['fontColor']);
    }

    if (isset($formSettings['form_padding'])) {
      $styles[] = 'padding: ' . $formSettings['form_padding'] . 'px';
    }

    if (isset($formSettings['alignment'])) {
      $styles[] = 'text-align: ' . $formSettings['alignment'];
    }

    return join(';', $styles);
  }
}
