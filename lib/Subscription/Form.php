<?php
namespace MailPoet\Subscription;
use MailPoet\API\Endpoints\Subscribers;
use MailPoet\API\Response as APIResponse;
use MailPoet\Util\Url;

class Form {
  static function onSubmit() {
    $reserved_keywords = array(
      'token',
      'endpoint',
      'method',
      'mailpoet_redirect'
    );

    $data = array_diff_key($_POST, array_flip($reserved_keywords));
    $form_id = isset($data['form_id']) ? $data['form_id'] : 0;

    $endpoint = new Subscribers();

    $response = $endpoint->subscribe($data);

    if($response->status !== APIResponse::STATUS_OK) {
      Url::redirectBack(array(
        'mailpoet_error' => isset($data['form_id']) ? $data['form_id'] : true,
        'mailpoet_success' => null
      ));
    } else {
      if(isset($response->meta['redirect_url'])) {
        Url::redirectTo($response->meta['redirect_url']);
      } else {
        Url::redirectBack(array(
          'mailpoet_success' => $form_id,
          'mailpoet_error' => null
        ));
      }
    }
  }
}