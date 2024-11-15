<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Captcha\Validator\CaptchaValidator;
use MailPoet\Captcha\Validator\ValidationError;
use MailPoet\Settings\SettingsController;

class CaptchaHooks {

  private SettingsController $settings;
  private CaptchaValidator $captchaValidator;

  public function __construct(
    SettingsController $settings,
    CaptchaValidator $captchaValidator
  ) {
    $this->settings = $settings;
    $this->captchaValidator = $captchaValidator;
  }

  public function isEnabled(): bool {
    // A transient code to enable incremental development of the feature.
    // Later when a setting is introduced, this function will be adjusted.
    if (!in_array(getenv('MP_ENV'), ['development', 'test'])) {
      return false;
    }

    return CaptchaConstants::isBuiltIn(
      $this->settings->get('captcha.type')
    );
  }

  public function render() {
    $referer = CaptchaUrlFactory::REFERER_WP_FORM;

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '<input type="hidden" name="referrer_form" value="' . $referer . '">';

    echo <<<HTML
      <input class="mailpoet_hidden_field" type="hidden" name="action" value="mailpoet">
      <input class="mailpoet_hidden_field" type="hidden" name="endpoint" value="captcha">
      <input class="mailpoet_hidden_field" type="hidden" name="method" value="render">
      <input class="mailpoet_hidden_field" type="hidden" name="api_version" value="v1">

      <script async defer>
        document.addEventListener('DOMContentLoaded', function () {
          let element = document.querySelector('form#registerform');

          // Submit the form to MP's AJAX endpoint
          element.setAttribute('action', '/wp-admin/admin-ajax.php');

          // Transform 'name' attr to 'data[name]' format
          element.querySelectorAll('input,select,textarea').forEach(function (field) {
            if (!field.classList.contains('mailpoet_hidden_field')) {
              field.setAttribute('name', 'data[' + field.getAttribute('name') + ']');
            }
          });
        });
      </script>
    HTML;
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
