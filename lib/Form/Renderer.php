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

  public function renderStyles(array $form, string $prefix, string $displayType): string {
    wp_enqueue_style('mailpoet_custom_fonts_css', 'https://fonts.googleapis.com/css?family=Abril+FatFace:400,400i,700,700|Alegreya:400,400i,700,700|Alegreya+Sans:400,400i,700,700|Amatic+SC:400,400i,700,700|Anonymous+Pro:400,400i,700,700|Architects+Daughter:400,400i,700,700|Archivo:400,400i,700,700|Archivo+Narrow:400,400i,700,700|Asap:400,400i,700,700|Barlow:400,400i,700,700|BioRhyme:400,400i,700,700|Bonbon:400,400i,700,700|Cabin:400,400i,700,700|Cairo:400,400i,700,700|Cardo:400,400i,700,700|Chivo:400,400i,700,700|Concert+One:400,400i,700,700|Cormorant:400,400i,700,700|Crimson+Text:400,400i,700,700|Eczar:400,400i,700,700|Exo+2:400,400i,700,700|Fira+Sans:400,400i,700,700|Fjalla+One:400,400i,700,700|Frank+Ruhl%20Libre:400,400i,700,700|Great+Vibes:400,400i,700,700|Heebo:400,400i,700,700|IBM+Plex:400,400i,700,700|Inconsolata:400,400i,700,700|Indie+Flower:400,400i,700,700|Inknut+Antiqua:400,400i,700,700|Inter:400,400i,700,700|Karla:400,400i,700,700|Libre+Baskerville:400,400i,700,700|Libre+Franklin:400,400i,700,700|Montserrat:400,400i,700,700|Neuton:400,400i,700,700|Notable:400,400i,700,700|Nothing+You%20Could%20Do:400,400i,700,700|Noto+Sans:400,400i,700,700|Nunito:400,400i,700,700|Old+Standard%20TT:400,400i,700,700|Oxygen:400,400i,700,700|Pacifico:400,400i,700,700|Poppins:400,400i,700,700|Proza+Libre:400,400i,700,700|PT+Sans:400,400i,700,700|PT+Serif:400,400i,700,700|Rakkas:400,400i,700,700|Reenie+Beanie:400,400i,700,700|Roboto+Slab:400,400i,700,700|Ropa+Sans:400,400i,700,700|Rubik:400,400i,700,700|Shadows+Into%20Light:400,400i,700,700|Space+Mono:400,400i,700,700|Spectral:400,400i,700,700|Sue+Ellen%20Francisco:400,400i,700,700|Titillium+Web:400,400i,700,700|Ubuntu:400,400i,700,700|Varela:400,400i,700,700|Vollkorn:400,400i,700,700|Work+Sans:400,400i,700,700|Yatra+One:400,400i,700,700');
    $html = '<style type="text/css">';
    $html .= '.mailpoet_hp_email_label{display:none!important;}'; // move honeypot field out of sight
    $html .= $this->styleUtils->prefixStyles($this->getCustomStyles($form), $prefix);
    $html .= $this->styleUtils->renderFormSettingsStyles($form, $prefix, $displayType);
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
}
