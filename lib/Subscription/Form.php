<?php
namespace MailPoet\Subscription;

use MailPoet\API\JSON\API;
use MailPoet\API\JSON\Endpoints\Subscribers;
use MailPoet\API\Response as APIResponse;
use MailPoet\Util\Url;

class Form {
  static function onSubmit() {
    $api = new API();
    $api->setRequestData($_REQUEST);
    $form_id = (!empty($_REQUEST['data']['form_id'])) ? (int)$_REQUEST['data']['form_id'] : false;
    $response = $api->processRoute();
    if($response->status !== APIResponse::STATUS_OK) {
      Url::redirectBack(
        array(
          'mailpoet_error' => ($form_id) ? $form_id : true,
          'mailpoet_success' => null
        )
      );
    } else {
      (isset($response->meta['redirect_url'])) ?
        Url::redirectTo($response->meta['redirect_url']) :
        Url::redirectBack(
          array(
            'mailpoet_success' => $form_id,
            'mailpoet_error' => null
          )
        );
    }
  }
}