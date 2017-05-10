<?php
namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Services\Bridge;
use MailPoet\Util\License\License;
use MailPoet\WP\DateTime;

if(!defined('ABSPATH')) exit;

class Services extends APIEndpoint {
  public $bridge;
  public $date_time;

  function __construct() {
    $this->bridge = new Bridge();
    $this->date_time = new DateTime();
  }

  function checkMSSKey($data = array()) {
    $key = isset($data['key']) ? trim($data['key']) : null;

    if(!$key) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST  => __('Please specify a key.', 'mailpoet')
      ));
    }

    try {
      $result = $this->bridge->checkMSSKey($key);
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
        $this->date_time->formatDate(strtotime($result['data']['expire_at']))
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

  function checkPremiumKey($data = array()) {
    $key = isset($data['key']) ? trim($data['key']) : null;

    if(!$key) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST  => __('Please specify a key.', 'mailpoet')
      ));
    }

    try {
      $result = $this->bridge->checkPremiumKey($key);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }

    $state = !empty($result['state']) ? $result['state'] : null;

    $success_message = null;
    if($state == Bridge::PREMIUM_KEY_VALID) {
      $success_message = __('Your license key has been successfully validated.', 'mailpoet');
    } elseif($state == Bridge::PREMIUM_KEY_EXPIRING) {
      $success_message = sprintf(
        __('Your license key expires on %s.', 'mailpoet'),
        $this->date_time->formatDate(strtotime($result['data']['expire_at']))
      );
    }

    if($success_message) {
      $premium_plugin_active = License::getLicense();
      return $this->successResponse(
        array('message' => $success_message),
        array('premium_plugin_active' => $premium_plugin_active)
      );
    }

    switch($state) {
      case Bridge::PREMIUM_KEY_INVALID:
        $error = __('Your license key is invalid.', 'mailpoet');
        break;
      case Bridge::PREMIUM_KEY_ALREADY_USED:
        $error = __('Your license key is already used on another site.', 'mailpoet');
        break;
      default:
        $code = !empty($result['code']) ? $result['code'] : Bridge::CHECK_ERROR_UNKNOWN;
        $error = sprintf(
          __('Error validating license key, please try again later (code: %s)', 'mailpoet'),
          $code
        );
        break;
    }

    return $this->errorResponse(array(APIError::BAD_REQUEST => $error));
  }
}