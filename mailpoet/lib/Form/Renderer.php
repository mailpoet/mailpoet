<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form;

use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\Templates\FormTemplate;
use MailPoet\Form\Util\CustomFonts;
use MailPoet\Form\Util\Styles;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class Renderer {
  /** @var Styles */
  private $styleUtils;

  /** @var SettingsController */
  private $settings;

  /** @var BlocksRenderer */
  private $blocksRenderer;

  /** @var CustomFonts */
  private $customFonts;

  /** @var Bridge */
  private $bridge;

  public function __construct(
    Styles $styleUtils,
    SettingsController $settings,
    CustomFonts $customFonts,
    BlocksRenderer $blocksRenderer,
    Bridge $bridge
  ) {
    $this->styleUtils = $styleUtils;
    $this->settings = $settings;
    $this->blocksRenderer = $blocksRenderer;
    $this->customFonts = $customFonts;
    $this->bridge = $bridge;
  }

  public function renderStyles(FormEntity $form, string $prefix, string $displayType): string {
    $this->customFonts->enqueueStyle();
    $html = $this->styleUtils->prefixStyles($this->getCustomStyles($form), $prefix);
    $html .= strip_tags($this->styleUtils->renderFormSettingsStyles($form, $prefix, $displayType));
    return $html;
  }

  public function renderHTML(?FormEntity $form = null): string {
    if (($form instanceof FormEntity) && !empty($form->getBody()) && is_array($form->getSettings())) {
      return $this->renderBlocks($form->getBody(), $form->getSettings(), $form->getId());
    }
    return '';
  }

  public function getCustomStyles(?FormEntity $form = null): string {
    if (($form instanceof FormEntity) && (strlen(trim($form->getStyles() ?? '')) > 0)) {
      return strip_tags($form->getStyles() ?? '');
    } else {
      return FormTemplate::DEFAULT_STYLES;
    }
  }

  public function renderBlocks(
    array $blocks = [],
    array $formSettings = [],
    ?int $formId = null,
    bool $honeypotEnabled = true,
    bool $captchaEnabled = true
  ): string {
    // add honeypot for spambots
    $html = ($honeypotEnabled) ? $this->renderHoneypot() : '';
    foreach ($blocks as $key => $block) {
      if (
        $captchaEnabled
        && $block['type'] === FormEntity::SUBMIT_BLOCK_TYPE
      ) {
        $html .= $this->renderCaptcha();
      }
      if (in_array($block['type'], [FormEntity::COLUMN_BLOCK_TYPE, FormEntity::COLUMNS_BLOCK_TYPE])) {
        $blocks = $block['body'] ?? [];
        $html .= $this->blocksRenderer->renderContainerBlock($block, $this->renderBlocks($blocks, $formSettings, $formId, false)) . PHP_EOL;
      } else {
        $html .= $this->blocksRenderer->renderBlock($block, $formSettings, $formId) . PHP_EOL;
      }
    }
    return $html;
  }

  private function renderHoneypot(): string {
    return '<label class="mailpoet_hp_email_label" style="display: none !important;">' . __('Please leave this field empty', 'mailpoet') . '<input type="email" name="data[email]"/></label>';
  }

  private function renderCaptcha(): string {
    $type = $this->settings->get('captcha.type');
    if (CaptchaConstants::isReCaptcha($type)) {
      return $this->renderReCaptcha();
    }
    if (CaptchaConstants::isTurnstile($type)) {
      return $this->renderTurnstile();
    }
    if (
      (CaptchaConstants::isBuiltIn($type) || CaptchaConstants::isDisabled($type))
      && $this->bridge->isMailpoetSendingServiceEnabled()
    ) {
      return $this->renderBlackboxChallengeContainer();
    }
    return '';
  }

  /**
   * A container for Blackbox's own checkbox challenge, shown only when the JS client
   * decides mid-collect that the visitor needs it (see assets/js/src/public.tsx's
   * configure() call). Needed specifically for the built-in/disabled captcha cases,
   * which otherwise have no widget container in the form markup at all.
   */
  private function renderBlackboxChallengeContainer(): string {
    return '<div class="mailpoet_blackbox_challenge_container"></div>';
  }

  private function renderReCaptcha(): string {
    if ($this->settings->get('captcha.type') === CaptchaConstants::TYPE_RECAPTCHA) {
      $siteKey = (string)$this->settings->get('captcha.recaptcha_site_token');
      $size = '';
    } else {
      $siteKey = (string)$this->settings->get('captcha.recaptcha_invisible_site_token');
      $size = 'invisible';
    }
    $siteKeyAttr = esc_attr($siteKey);
    $fallbackUrl = esc_url('https://www.google.com/recaptcha/api/fallback?k=' . $siteKey);

    $html = '<div class="mailpoet_recaptcha" data-sitekey="' . $siteKeyAttr . '" ' . ($size === 'invisible' ? 'data-size="invisible"' : '') . '>
      <div class="mailpoet_recaptcha_container"></div>
      <noscript>
        <div>
          <div class="mailpoet_recaptcha_noscript_container">
            <div>
              <iframe src="' . $fallbackUrl . '" frameborder="0" scrolling="no">
              </iframe>
            </div>
          </div>
          <div class="mailpoet_recaptcha_noscript_input">
            <textarea id="g-recaptcha-response" name="data[recaptcha]" class="g-recaptcha-response">
            </textarea>
          </div>
        </div>
      </noscript>
      <input class="mailpoet_recaptcha_field" type="hidden" name="recaptchaWidgetId">
    </div>';
    if ($size !== 'invisible') {
      $html .= '<div class="parsley-errors-list parsley-required mailpoet_error_recaptcha">' . __('This field is required.', 'mailpoet') . '</div>';
    }

    return $html;
  }

  private function renderTurnstile(): string {
    $siteKey = esc_attr((string)$this->settings->get('captcha.turnstile_site_token'));

    return '<div class="mailpoet_turnstile" data-sitekey="' . $siteKey . '">
      <div class="mailpoet_turnstile_container"></div>
      <input class="mailpoet_turnstile_field" type="hidden" name="turnstileWidgetId">
    </div>';
  }
}
