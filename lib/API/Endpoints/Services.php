<?php
namespace MailPoet\API\Endpoints;

use Carbon\Carbon;
use MailPoet\API\Endpoint as APIEndpoint;
use MailPoet\API\Error as APIError;
use MailPoet\Services\Bridge;

if(!defined('ABSPATH')) exit;

class Services extends APIEndpoint {
  public $bridge;

  function __construct() {
    $this->bridge = new Bridge();
  }

  function verifyMailPoetKey($data = array()) {
    $key = isset($data['key']) ? trim($data['key']) : null;

    if(!$key) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST  => __('Please specify a key.', 'mailpoet')
      ));
    }

    try {
      $result = $this->bridge->checkKey($key);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }

    $state = !empty($result['state']) ? $result['state'] : null;

    $success_message = null;
    if($state == Bridge::MAILPOET_KEY_VALID) {
      $success_message = __('Your MailPoet API key is valid!', 'mailpoet');
    } elseif($state == Bridge::MAILPOET_KEY_EXPIRING) {
      $success_message = sprintf(
        __('Your MailPoet key expires on %s!', 'mailpoet'),
        Carbon::createFromTimestamp(strtotime($result['data']['expire_at']))
          ->format('Y-m-d')
      );
    }

    if($success_message) {
      return $this->successResponse(array('message' => $success_message));
    }

    switch($state) {
      case Bridge::MAILPOET_KEY_INVALID:
        $error = __('Your MailPoet key is invalid!', 'mailpoet');
        break;
      default:
        $code = !empty($result['code']) ? $result['code'] : Bridge::CHECK_ERROR_UNKNOWN;
        $error = sprintf(
          __('Error validating API key, please try again later (code: %s)', 'mailpoet'),
          $code
        );
        break;
    }

    return $this->errorResponse(array(APIError::BAD_REQUEST => $error));
  }
}