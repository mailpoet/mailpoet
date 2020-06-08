<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\Analytics\Analytics as AnalyticsHelper;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Installer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck;
use MailPoet\Mailer\MailerLog;
use MailPoet\Services\Bridge;
use MailPoet\Services\CongratulatoryMssEmailController;
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

  /** @var DateTime */
  public $dateTime;

  /** @var SendingServiceKeyCheck */
  private $mssWorker;

  /** @var PremiumKeyCheck */
  private $premiumWorker;

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var CongratulatoryMssEmailController */
  private $congratulatoryMssEmailController;

  /** @var WPFunctions */
  private $wp;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
  ];

  public function __construct(
    Bridge $bridge,
    SettingsController $settings,
    AnalyticsHelper $analytics,
    SendingServiceKeyCheck $mssWorker,
    PremiumKeyCheck $premiumWorker,
    ServicesChecker $servicesChecker,
    CongratulatoryMssEmailController $congratulatoryMssEmailController,
    WPFunctions $wp
  ) {
    $this->bridge = $bridge;
    $this->settings = $settings;
    $this->analytics = $analytics;
    $this->mssWorker = $mssWorker;
    $this->premiumWorker = $premiumWorker;
    $this->dateTime = new DateTime();
    $this->servicesChecker = $servicesChecker;
    $this->congratulatoryMssEmailController = $congratulatoryMssEmailController;
    $this->wp = $wp;
  }

  public function checkMSSKey($data = []) {
    $key = isset($data['key']) ? trim($data['key']) : null;

    if (!$key) {
      return $this->badRequest([
        APIError::BAD_REQUEST  => $this->wp->__('Please specify a key.', 'mailpoet'),
      ]);
    }

    $wasPendingApproval = $this->servicesChecker->isMailPoetAPIKeyPendingApproval();

    try {
      $result = $this->bridge->checkMSSKey($key);
      $this->bridge->storeMSSKeyAndState($key, $result);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }

    // pause sending when key is pending approval, resume when not pending anymore
    $isPendingApproval = $this->servicesChecker->isMailPoetAPIKeyPendingApproval();
    if (!$wasPendingApproval && $isPendingApproval) {
      MailerLog::pauseSending(MailerLog::getMailerLog());
    } elseif ($wasPendingApproval && !$isPendingApproval) {
      MailerLog::resumeSending();
    }

    $state = !empty($result['state']) ? $result['state'] : null;

    $successMessage = null;
    if ($state == Bridge::KEY_VALID) {
      $successMessage = $this->wp->__('Your MailPoet Sending Service key has been successfully validated', 'mailpoet');
    } elseif ($state == Bridge::KEY_EXPIRING) {
      $successMessage = sprintf(
        $this->wp->__('Your MailPoet Sending Service key expires on %s!', 'mailpoet'),
        $this->dateTime->formatDate(strtotime($result['data']['expire_at']))
      );
    }

    if (!empty($result['data']['public_id'])) {
      $this->analytics->setPublicId($result['data']['public_id']);
    }

    if ($successMessage) {
      return $this->successResponse(['message' => $successMessage]);
    }

    switch ($state) {
      case Bridge::KEY_INVALID:
        $error = $this->wp->__('Your key is not valid for the MailPoet Sending Service', 'mailpoet');
        break;
      case Bridge::KEY_ALREADY_USED:
        $error = $this->wp->__('Your MailPoet Sending Service key is already used on another site', 'mailpoet');
        break;
      default:
        $code = !empty($result['code']) ? $result['code'] : Bridge::CHECK_ERROR_UNKNOWN;
        $errorMessage = $this->wp->__('Error validating MailPoet Sending Service key, please try again later (%s).', 'mailpoet');
        // If site runs on localhost
        if ( 1 === preg_match("/^(http|https)\:\/\/(localhost|127\.0\.0\.1)/", $this->wp->siteUrl()) ) {
          $errorMessage .= ' ' . $this->wp->__("Note that it doesn't work on localhost.", 'mailpoet');
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
        APIError::BAD_REQUEST  => $this->wp->__('Please specify a key.', 'mailpoet'),
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

    $successMessage = null;
    if ($state == Bridge::KEY_VALID) {
      $successMessage = $this->wp->__('Your Premium key has been successfully validated', 'mailpoet');
    } elseif ($state == Bridge::KEY_EXPIRING) {
      $successMessage = sprintf(
        $this->wp->__('Your Premium key expires on %s', 'mailpoet'),
        $this->dateTime->formatDate(strtotime($result['data']['expire_at']))
      );
    }

    if (!empty($result['data']['public_id'])) {
      $this->analytics->setPublicId($result['data']['public_id']);
    }

    if ($successMessage) {
      return $this->successResponse(
        ['message' => $successMessage],
        Installer::getPremiumStatus()
      );
    }

    switch ($state) {
      case Bridge::KEY_INVALID:
        $error = $this->wp->__('Your key is not valid for MailPoet Premium', 'mailpoet');
        break;
      case Bridge::KEY_ALREADY_USED:
        $error = $this->wp->__('Your Premium key is already used on another site', 'mailpoet');
        break;
      default:
        $code = !empty($result['code']) ? $result['code'] : Bridge::CHECK_ERROR_UNKNOWN;
        $error = sprintf(
          $this->wp->__('Error validating Premium key, please try again later (%s)', 'mailpoet'),
          $this->getErrorDescriptionByCode($code)
        );
        break;
    }

    return $this->errorResponse(
      [APIError::BAD_REQUEST => $error],
      ['code' => $result['code'] ?? null]
    );
  }

  public function recheckKeys() {
    $this->mssWorker->init();
    $this->mssWorker->checkKey();
    $this->premiumWorker->init();
    $this->premiumWorker->checkKey();
    return $this->successResponse();
  }

  public function sendCongratulatoryMssEmail() {
    if (!Bridge::isMPSendingServiceEnabled()) {
      return $this->createBadRequest(__('MailPoet Sending Service is not active.', 'mailpoet'));
    }

    $authorizedEmails = $this->bridge->getAuthorizedEmailAddresses();
    if (!$authorizedEmails) {
      return $this->createBadRequest(__('No FROM email addresses are authorized.', 'mailpoet'));
    }

    $fromEmail = $this->settings->get('sender.address');
    if (!$fromEmail) {
      return $this->createBadRequest(__('Sender email address is not set.', 'mailpoet'));
    }
    if (!in_array($fromEmail, $authorizedEmails, true)) {
      return $this->createBadRequest(sprintf(__("Sender email address '%s' is not authorized.", 'mailpoet'), $fromEmail));
    }

    try {
      // congratulatory email is sent to the current FROM address (authorized at this point)
      $this->congratulatoryMssEmailController->sendCongratulatoryEmail($fromEmail);
    } catch (\Throwable $e) {
      return $this->errorResponse([
        APIError::UNKNOWN => __('Sending of congratulatory email failed.', 'mailpoet'),
      ], [], Response::STATUS_UNKNOWN);
    }
    return $this->successResponse([
      'email_address' => $fromEmail,
    ]);
  }

  private function getErrorDescriptionByCode($code) {
    switch ($code) {
      case Bridge::CHECK_ERROR_UNAVAILABLE:
        $text = $this->wp->__('Service unavailable', 'mailpoet');
        break;
      case Bridge::CHECK_ERROR_UNKNOWN:
        $text = $this->wp->__('Contact your hosting support to check the connection between your host and https://bridge.mailpoet.com', 'mailpoet');
        break;
      default:
        $text = sprintf(_x('code: %s', 'Error code (inside parentheses)', 'mailpoet'), $code);
        break;
    }

    return $text;
  }

  private function createBadRequest(string $message) {
    return $this->badRequest([
      APIError::BAD_REQUEST => $message,
    ]);
  }
}
