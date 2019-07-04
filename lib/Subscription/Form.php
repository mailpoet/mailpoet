<?php

namespace MailPoet\Subscription;

use MailPoet\API\JSON\API;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Util\Url as UrlHelper;

class Form {

  /** @var API */
  private $api;

  /** @var UrlHelper */
  private $url_helper;

  function __construct(API $api, UrlHelper $url_helper) {
    $this->api = $api;
    $this->url_helper = $url_helper;
  }

  function onSubmit($request_data = false) {
    $request_data = ($request_data) ? $request_data : $_REQUEST;
    $this->api->setRequestData($request_data);
    $form_id = (!empty($request_data['data']['form_id'])) ? (int)$request_data['data']['form_id'] : false;
    $response = $this->api->processRoute();
    if ($response->status !== APIResponse::STATUS_OK) {
      return (isset($response->meta['redirect_url'])) ?
      $this->url_helper->redirectTo($response->meta['redirect_url']) :
      $this->url_helper->redirectBack(
        [
          'mailpoet_error' => ($form_id) ? $form_id : true,
          'mailpoet_success' => null,
        ]
      );
    } else {
      return (isset($response->meta['redirect_url'])) ?
        $this->url_helper->redirectTo($response->meta['redirect_url']) :
        $this->url_helper->redirectBack(
          [
            'mailpoet_success' => $form_id,
            'mailpoet_error' => null,
          ]
        );
    }
  }
}
