<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\Analytics\Analytics as AnalyticsHelper;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Installer;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck;
use MailPoet\Services\Bridge;
use MailPoet\Services\SPFCheck;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\DateTime;
use MailPoet\WP\Functions as WPFunctions;

class Services extends APIEndpoint {
  /** @var Bridge */
  private $bridge;

  /** @var SettingsController */
  private $settings;

  /** @var AnalyticsHelper */
  private $analytics;

  /** @var SPFCheck */
  private $spf_check;

  /** @var DateTime */
  public $date_time;

  /** @var SendingServiceKeyCheck */
  private $mss_worker;

  /** @var PremiumKeyCheck */
  private $premium_worker;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
  ];

  public function __construct(Bridge $bridge, SettingsController $settings, AnalyticsHelper $analytics, SPFCheck $spf_check, SendingServiceKeyCheck $mss_worker, PremiumKeyCheck $premium_worker) {
    $this->bridge = $bridge;
    $this->settings = $settings;
    $this->analytics = $analytics;
    $this->spf_check = $spf_check;
    $this->mss_worker = $mss_worker;
    $this->premium_worker = $premium_worker;
    $this->date_time = new DateTime();
  }

  public function checkSPFRecord($data = []) {
    $sender_address = $this->settings->get('sender.address');
    $domain_name = mb_substr($sender_address, mb_strpos($sender_address, '@') + 1);

    $result = $this->spf_check->checkSPFRecord($domain_name);

    if (!$result) {
      return $this->errorResponse(
        [APIError::BAD_REQUEST => WPFunctions::get()->__('SPF check has failed.', 'mailpoet')],
        ['sender_address' => $sender_address]
      );
    }

    return $this->successResponse();
  }

  public function checkMSSKey($data = []) {
    $key = isset($data['key']) ? trim($data['key']) : null;

    if (!$key) {
      return $this->badRequest([
        APIError::BAD_REQUEST  => WPFunctions::get()->__('Please specify a key.', 'mailpoet'),
      ]);
    }

    try {
      $result = $this->bridge->checkMSSKey($key);
      $this->bridge->storeMSSKeyAndState($key, $result);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }

    $state = !empty($result['state']) ? $result['state'] : null;

    $success_message = null;
    if ($state == Bridge::KEY_VALID) {
      $success_message = WPFunctions::get()->__('Your MailPoet Sending Service key has been successfully validated.', 'mailpoet');
    } elseif ($state == Bridge::KEY_EXPIRING) {
      $success_message = sprintf(
        WPFunctions::get()->__('Your MailPoet Sending Service key expires on %s!', 'mailpoet'),
        $this->date_time->formatDate(strtotime($result['data']['expire_at']))
      );
    }

    if (!empty($result['data']['public_id'])) {
      $this->analytics->setPublicId($result['data']['public_id']);
    }

    if ($success_message) {
      return $this->successResponse(['message' => $success_message]);
    }

    switch ($state) {
      case Bridge::KEY_INVALID:
        $error = WPFunctions::get()->__('Your MailPoet Sending Service key is invalid.', 'mailpoet');
        break;
      case Bridge::KEY_ALREADY_USED:
        $error = WPFunctions::get()->__('Your MailPoet Sending Service key is already used on another site.', 'mailpoet');
        break;
      default:
        $code = !empty($result['code']) ? $result['code'] : Bridge::CHECK_ERROR_UNKNOWN;
        $errorMessage = WPFunctions::get()->__('Error validating MailPoet Sending Service key, please try again later (%s).', 'mailpoet');
        // If site runs on localhost
        if ( 1 === preg_match("/^(http|https)\:\/\/(localhost|127\.0\.0\.1)/", WPFunctions::get()->siteUrl()) ) {
          $errorMessage .= ' ' . WPFunctions::get()->__("Note that it doesn't work on localhost.", 'mailpoet');
        }
        $error = sprintf(
          $errorMessage,
          $this->getErrorDescriptionByCode($code)
        );
        break;
    }

    return $this->errorResponse([APIError::BAD_REQUEST => $error]);
  }

  public function checkPremiumKey($data = []) {
    $key = isset($data['key']) ? trim($data['key']) : null;

    if (!$key) {
      return $this->badRequest([
        APIError::BAD_REQUEST  => WPFunctions::get()->__('Please specify a key.', 'mailpoet'),
      ]);
    }

    try {
      $result = $this->bridge->checkPremiumKey($key);
      $this->bridge->storePremiumKeyAndState($key, $result);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }

    $state = !empty($result['state']) ? $result['state'] : null;

    $success_message = null;
    if ($state == Bridge::KEY_VALID) {
      $success_message = WPFunctions::get()->__('Your Premium key has been successfully validated.', 'mailpoet');
    } elseif ($state == Bridge::KEY_EXPIRING) {
      $success_message = sprintf(
        WPFunctions::get()->__('Your Premium key expires on %s.', 'mailpoet'),
        $this->date_time->formatDate(strtotime($result['data']['expire_at']))
      );
    }

    if (!empty($result['data']['public_id'])) {
      $this->analytics->setPublicId($result['data']['public_id']);
    }

    if ($success_message) {
      return $this->successResponse(
        ['message' => $success_message],
        Installer::getPremiumStatus()
      );
    }

    switch ($state) {
      case Bridge::KEY_INVALID:
        $error = WPFunctions::get()->__('Your Premium key is invalid.', 'mailpoet');
        break;
      case Bridge::KEY_ALREADY_USED:
        $error = WPFunctions::get()->__('Your Premium key is already used on another site.', 'mailpoet');
        break;
      default:
        $code = !empty($result['code']) ? $result['code'] : Bridge::CHECK_ERROR_UNKNOWN;
        $error = sprintf(
          WPFunctions::get()->__('Error validating Premium key, please try again later (%s)', 'mailpoet'),
          $this->getErrorDescriptionByCode($code)
        );
        break;
    }

    return $this->errorResponse([APIError::BAD_REQUEST => $error]);
  }

  public function recheckKeys() {
    $this->mss_worker->init();
    $this->mss_worker->checkKey();
    $this->premium_worker->init();
    $this->premium_worker->checkKey();
    return $this->successResponse();
  }

  private function getErrorDescriptionByCode($code) {
    switch ($code) {
      case Bridge::CHECK_ERROR_UNAVAILABLE:
        $text = WPFunctions::get()->__('Service unavailable', 'mailpoet');
        break;
      case Bridge::CHECK_ERROR_UNKNOWN:
        $text = WPFunctions::get()->__('Contact your hosting support to check the connection between your host and https://bridge.mailpoet.com', 'mailpoet');
        break;
      default:
        $text = sprintf(_x('code: %s', 'Error code (inside parentheses)', 'mailpoet'), $code);
        break;
    }

    return $text;
  }
}
