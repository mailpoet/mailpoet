<?php declare(strict_types = 1);

namespace MailPoet\API\JSON;

/**
 * Messages MailPoet.Ajax (assets/js/src/ajax.ts) shows when a request fails.
 * Every admin page that loads MailPoet.Ajax needs them.
 */
class AjaxErrorMessages {
  /** @return array<string, string> */
  public function getTranslations(): array {
    return [
      // translators: %d is an HTTP response code, such as 500.
      'ajaxFailedErrorMessage' => __('An error has happened while performing a request, the server has responded with response code %d', 'mailpoet'),
      // translators: %d is a number of seconds.
      'ajaxTimeoutErrorMessage' => __('An error has happened while performing a request, the server request has timed out after %d seconds', 'mailpoet'),
    ];
  }
}
