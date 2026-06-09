<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscription;

use MailPoet\API\JSON\API;
use MailPoet\API\JSON\Endpoint;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Util\Url as UrlHelper;

class Form {

  /** @var API */
  private $api;

  /** @var UrlHelper */
  private $urlHelper;

  public function __construct(
    API $api,
    UrlHelper $urlHelper
  ) {
    $this->api = $api;
    $this->urlHelper = $urlHelper;
  }

  public function onSubmit($requestData = false) {
    $requestData = ($requestData) ? $requestData : $_REQUEST;

    // When the admin-post action URL is hit directly without a form payload
    // (e.g. a crawler GETting the URL), redirect away before reaching the JSON
    // API. Otherwise processRoute() throws "Invalid API endpoint." and the
    // exception ends up in the WordPress debug log. Mirrors Manage::onSave().
    $action = (isset($requestData['action']) && is_string($requestData['action']))
      ? sanitize_text_field(wp_unslash($requestData['action']))
      : '';
    $apiVersion = (isset($requestData['api_version']) && is_string($requestData['api_version']))
      ? trim($requestData['api_version'])
      : '';
    $endpoint = (isset($requestData['endpoint']) && is_string($requestData['endpoint']))
      ? trim($requestData['endpoint'])
      : '';
    $methodParamName = isset($requestData['mailpoet_method']) ? 'mailpoet_method' : 'method';
    $method = (isset($requestData[$methodParamName]) && is_string($requestData[$methodParamName]))
      ? trim($requestData[$methodParamName])
      : '';
    if (
      $action !== 'mailpoet_subscription_form'
      || empty($requestData['data'])
      || !is_array($requestData['data'])
      || $apiVersion === ''
      || $endpoint === ''
      || $method === ''
    ) {
      return $this->urlHelper->redirectBack();
    }

    $this->api->setRequestData($requestData, Endpoint::TYPE_POST);
    $formId = (!empty($requestData['data']['form_id'])) ? (int)$requestData['data']['form_id'] : false;
    $response = $this->api->processRoute();
    if ($response->status !== APIResponse::STATUS_OK) {
      return (isset($response->meta['redirect_url'])) ?
        $this->urlHelper->redirectTo($response->meta['redirect_url']) :
        $this->urlHelper->redirectBack(
          [
            'mailpoet_error' => ($formId) ? $formId : true,
            'mailpoet_success' => null,
          ]
        );
    } else {
      return (isset($response->meta['redirect_url'])) ?
        $this->urlHelper->redirectTo($response->meta['redirect_url']) :
        $this->urlHelper->redirectBack(
          [
            'mailpoet_success' => $formId,
            'mailpoet_error' => null,
          ]
        );
    }
  }
}
