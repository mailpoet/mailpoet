<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Captcha\Validator\CaptchaValidator;
use MailPoet\Captcha\Validator\ValidationError;
use MailPoet\Settings\SettingsController;

class CaptchaHooks {

  private SettingsController $settings;
  private CaptchaValidator $captchaValidator;
  private CaptchaRenderer $captchaRenderer;

  public function __construct(
    SettingsController $settings,
    CaptchaValidator $captchaValidator,
    CaptchaRenderer $captchaRenderer
  ) {
    $this->settings = $settings;
    $this->captchaValidator = $captchaValidator;
    $this->captchaRenderer = $captchaRenderer;
  }

  public function isEnabled(): bool {
    if (!$this->settings->get(CaptchaConstants::ON_REGISTER_FORMS_SETTING_NAME, false)) {
      return false;
    }

    $type = $this->settings->get('captcha.type');
    return CaptchaConstants::isBuiltIn($type)
      || (CaptchaConstants::isDisabled($type) && $this->captchaRenderer->isSupported());
  }

  public function renderInWPRegisterForm() {
    $this->render('form#registerform', CaptchaUrlFactory::REFERER_WP_FORM);
  }

  public function renderInWCRegisterForm() {
    $this->render('form.woocommerce-form-register', CaptchaUrlFactory::REFERER_WC_FORM);
  }

  private function render($formSelector, $referrer) {
    // phpcs:disable WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
    echo <<<HTML
      <input class="mailpoet_hidden_field" type="hidden" name="action" value="mailpoet">
      <input class="mailpoet_hidden_field" type="hidden" name="endpoint" value="captcha">
      <input class="mailpoet_hidden_field" type="hidden" name="method" value="render">
      <input class="mailpoet_hidden_field" type="hidden" name="api_version" value="v1">

      <input type="hidden" name="referrer_form" value="$referrer">

      <script async defer>
        document.addEventListener('DOMContentLoaded', function () {
          let form = document.querySelector('$formSelector');

          // Forward the original form action URL
          let actionUrl = form.getAttribute('action') ?? window.location.href;
          form.insertAdjacentHTML('beforeend', '<input type="hidden" name="referrer_form_url" value="' + actionUrl + '">');

          // Submit the form to MP's AJAX endpoint
          form.setAttribute('action', '/wp-admin/admin-ajax.php');

          // Transform 'name' attr to 'data[name]' format
          form.querySelectorAll('input,select,textarea,button[name][value]').forEach(function (field) {
            if (!field.classList.contains('mailpoet_hidden_field')) {
              field.setAttribute('name', 'data[' + field.getAttribute('name') + ']');
            }
          });
        });
      </script>
    HTML;
    // phpcs:enable WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
  }

  public function validate(\WP_Error $errors) {
    try {
      // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
      $this->captchaValidator->validate($_POST['data'] ?? []);
    } catch (ValidationError $e) {
      $errors->add('captcha_failed', $e->getMessage());
    }

    return $errors;
  }
}
