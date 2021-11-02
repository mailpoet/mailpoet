<?php

namespace MailPoet\Form;

use MailPoet\Entities\FormEntity;
use MailPoet\Form\Templates\FormTemplate;
use MailPoet\Form\Util\CustomFonts;
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

  /** @var CustomFonts */
  private $customFonts;

  public function __construct(
    Styles $styleUtils,
    SettingsController $settings,
    CustomFonts $customFonts,
    BlocksRenderer $blocksRenderer
  ) {
    $this->styleUtils = $styleUtils;
    $this->settings = $settings;
    $this->blocksRenderer = $blocksRenderer;
    $this->customFonts = $customFonts;
  }

  public function renderStyles(FormEntity $form, string $prefix, string $displayType): string {
    $this->customFonts->enqueueStyle();
    $html = '.mailpoet_hp_email_label{display:none!important;}'; // move honeypot field out of sight
    $html .= $this->styleUtils->prefixStyles($this->getCustomStyles($form), $prefix);
    $html .= strip_tags($this->styleUtils->renderFormSettingsStyles($form, $prefix, $displayType));
    return $html;
  }

  public function renderHTML(FormEntity $form = null): string {
    if (($form instanceof FormEntity) && !empty($form->getBody()) && is_array($form->getSettings())) {
      return $this->renderBlocks($form->getBody(), $form->getSettings() ?? []);
    }
    return '';
  }

  public function getCustomStyles(FormEntity $form = null): string {
    if (($form instanceof FormEntity) && (strlen(trim($form->getStyles() ?? '')) > 0)) {
      return strip_tags($form->getStyles() ?? '');
    } else {
      return FormTemplate::DEFAULT_STYLES;
    }
  }

  public function renderBlocks(array $blocks = [], array $formSettings = [], bool $honeypotEnabled = true, bool $captchaEnabled = true): string {
    // add honeypot for spambots
    $html = ($honeypotEnabled) ? $this->renderHoneypot() : '';
    foreach ($blocks as $key => $block) {
      if ($captchaEnabled
        && $block['type'] === FormEntity::SUBMIT_BLOCK_TYPE
        && $this->settings->get('captcha.type') === Captcha::TYPE_RECAPTCHA
      ) {
        $html .= $this->renderReCaptcha();
      }
      if (in_array($block['type'], [FormEntity::COLUMN_BLOCK_TYPE, FormEntity::COLUMNS_BLOCK_TYPE])) {
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
          <div class="mailpoet_recaptcha_noscript_container">
            <div>
              <iframe src="https://www.google.com/recaptcha/api/fallback?k=' . $siteKey . '" frameborder="0" scrolling="no">
              </iframe>
            </div>
          </div>
          <div class="mailpoet_recaptcha_noscript_input">
            <textarea id="g-recaptcha-response" name="data[recaptcha]" class="g-recaptcha-response">
            </textarea>
          </div>
        </div>
      </noscript>
      <input class="mailpoet_recaptcha_field" type="hidden" name="recaptcha">
    </div>';
  }
}
