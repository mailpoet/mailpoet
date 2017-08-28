<?php

namespace MailPoet\Subscription;

use MailPoet\API\API as API;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Config\AccessControl;
use MailPoet\Util\Url as UrlHelper;

class Form {
  static function onSubmit($request_data = false) {
    $request_data = ($request_data) ? $request_data : $_REQUEST;
    $api = API::JSON(new AccessControl());
    $api->setRequestData($request_data);
    $form_id = (!empty($request_data['data']['form_id'])) ? (int)$request_data['data']['form_id'] : false;
    $response = $api->processRoute();
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