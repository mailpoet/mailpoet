<?php

namespace MailPoet\Subscription;

use MailPoet\API\JSON\API;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Util\Url as UrlHelper;

class Form {

  /** @var API */
  private $api;

  function __construct(API $api) {
    $this->api = $api;
  }

  function onSubmit($request_data = false) {
    $request_data = ($request_data) ? $request_data : $_REQUEST;
    $this->api->setRequestData($request_data);
    $form_id = (!empty($request_data['data']['form_id'])) ? (int)$request_data['data']['form_id'] : false;
    $response = $this->api->processRoute();
    if($response->status !== APIResponse::STATUS_OK) {
      return UrlHelper::redirectBack(
        array(
          'mailpoet_error' => ($form_id) ? $form_id : true,
          'mailpoet_success' => null
        )
      );
    } else {
      return (isset($response->meta['redirect_url'])) ?
        UrlHelper::redirectTo($response->meta['redirect_url']) :
        UrlHelper::redirectBack(
          array(
            'mailpoet_success' => $form_id,
            'mailpoet_error' => null
          )
        );
    }
  }
}
