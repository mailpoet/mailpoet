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
use MailPoet\Services\AuthorizedSenderDomainController;
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

  /** @var AuthorizedSenderDomainController */
  private $senderDomainController;

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
    WPFunctions $wp,
    AuthorizedSenderDomainController $senderDomainController
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
    $this->senderDomainController = $senderDomainController;
  }

  public function checkMSSKey($data = []) {
    $key = isset($data['key']) ? trim($data['key']) : null;

    if (!$key) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Please specify a key.', 'mailpoet'),
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
      $successMessage = __('Your MailPoet Sending Service key has been successfully validated', 'mailpoet');
    } elseif ($state == Bridge::KEY_EXPIRING) {
      $successMessage = sprintf(
        // translators: %s is the expiration date.
        __('Your MailPoet Sending Service key expires on %s!', 'mailpoet'),
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
        $error = __('Your key is not valid for the MailPoet Sending Service', 'mailpoet');
        break;
      case Bridge::KEY_ALREADY_USED:
        $error = __('Your MailPoet Sending Service key is already used on another site', 'mailpoet');
        break;
      default:
        $code = !empty($result['code']) ? $result['code'] : Bridge::CHECK_ERROR_UNKNOWN;
        // translators: %s is the error message.
        $errorMessage = __('Error validating MailPoet Sending Service key, please try again later (%s).', 'mailpoet');
        // If site runs on localhost
        if (1 === preg_match("/^(http|https)\:\/\/(localhost|127\.0\.0\.1)/", $this->wp->siteUrl())) {
          $errorMessage .= ' ' . __("Note that it doesn't work on localhost.", 'mailpoet');
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
        APIError::BAD_REQUEST => __('Please specify a key.', 'mailpoet'),
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
      $successMessage = __('Your Premium key has been successfully validated', 'mailpoet');
    } elseif ($state == Bridge::KEY_EXPIRING) {
      $successMessage = sprintf(
        // translators: %s is the expiration date.
        __('Your Premium key expires on %s', 'mailpoet'),
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
        $error = __('Your key is not valid for MailPoet Premium', 'mailpoet');
        break;
      case Bridge::KEY_ALREADY_USED:
        $error = __('Your Premium key is already used on another site', 'mailpoet');
        break;
      default:
        $code = !empty($result['code']) ? $result['code'] : Bridge::CHECK_ERROR_UNKNOWN;
        $error = sprintf(
          // translators: %s is the error message.
          __('Error validating Premium key, please try again later (%s)', 'mailpoet'),
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
    $mssCheck = $this->mssWorker->checkKey();
    $this->premiumWorker->init();
    $premiumCheck = $this->premiumWorker->checkKey();
    // continue sending when it is paused and states are valid
    $mailerLog = MailerLog::getMailerLog();
    if (
      (isset($mailerLog['status']) && $mailerLog['status'] === MailerLog::STATUS_PAUSED)
      && (isset($mssCheck['state']) && $mssCheck['state'] === Bridge::KEY_VALID)
      && (isset($premiumCheck['state']) && $premiumCheck['state'] === Bridge::PREMIUM_KEY_VALID)
    ) {
      MailerLog::resumeSending();
    }
    return $this->successResponse();
  }

  public function sendCongratulatoryMssEmail() {
    if (!Bridge::isMPSendingServiceEnabled()) {
      return $this->createBadRequest(__('MailPoet Sending Service is not active.', 'mailpoet'));
    }

    $authorizedEmails = $this->bridge->getAuthorizedEmailAddresses();

    $verifiedDomains = $this->senderDomainController->getVerifiedSenderDomains(true);

    if (!$authorizedEmails && !$verifiedDomains) {
      return $this->createBadRequest(__('No FROM email addresses are authorized.', 'mailpoet'));
    }

    $fromEmail = $this->settings->get('sender.address');
    if (!$fromEmail) {
      return $this->createBadRequest(__('Sender email address is not set.', 'mailpoet'));
    }

    $arrayOfItems = explode('@', $fromEmail);
    $emailDomain = array_pop($arrayOfItems);

    if (!$this->isItemInArray($fromEmail, $authorizedEmails) && !$this->isItemInArray($emailDomain, $verifiedDomains)) {
      // translators: %s is the email address, which is not authorized.
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

  private function isItemInArray($item, $array): bool {
    return in_array($item, $array, true);
  }

  private function getErrorDescriptionByCode($code) {
    switch ($code) {
      case Bridge::CHECK_ERROR_UNAVAILABLE:
        $text = __('Service unavailable', 'mailpoet');
        break;
      case Bridge::CHECK_ERROR_UNKNOWN:
        $text = __('Contact your hosting support to check the connection between your host and https://bridge.mailpoet.com', 'mailpoet');
        break;
      default:
        // translators: %s is the code.
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
