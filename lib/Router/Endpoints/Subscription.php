<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Subscription as UserSubscription;
use MailPoet\WP\Functions as WPFunctions;

class Subscription {
  const ENDPOINT = 'subscription';
  const ACTION_CAPTCHA = 'captcha';
  const ACTION_CAPTCHA_IMAGE = 'captchaImage';
  const ACTION_CONFIRM = 'confirm';
  const ACTION_MANAGE = 'manage';
  const ACTION_UNSUBSCRIBE = 'unsubscribe';
  const ACTION_CONFIRM_UNSUBSCRIBE = 'confirmUnsubscribe';
  public $allowedActions = [
    self::ACTION_CAPTCHA,
    self::ACTION_CAPTCHA_IMAGE,
    self::ACTION_CONFIRM,
    self::ACTION_MANAGE,
    self::ACTION_UNSUBSCRIBE,
    self::ACTION_CONFIRM_UNSUBSCRIBE,
  ];
  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  /** @var UserSubscription\Pages */
  private $subscriptionPages;

  /** @var WPFunctions */
  private $wp;

  /** @var UserSubscription\Captcha */
  private $captcha;

  public function __construct(UserSubscription\Pages $subscriptionPages, WPFunctions $wp, UserSubscription\Captcha $captcha) {
    $this->subscriptionPages = $subscriptionPages;
    $this->wp = $wp;
    $this->captcha = $captcha;
  }

  public function captcha($data) {
    $this->initSubscriptionPage(UserSubscription\Pages::ACTION_CAPTCHA, $data);
  }

  public function captchaImage($data) {
    $width = !empty($data['width']) ? (int)$data['width'] : null;
    $height = !empty($data['height']) ? (int)$data['height'] : null;
    $sessionId = !empty($data['captcha_session_id']) ? $data['captcha_session_id'] : null;
    return $this->captcha->renderImage($width, $height, $sessionId);
  }

  public function confirm($data) {
    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_CONFIRM, $data);
    $subscription->confirm();
  }

  public function confirmUnsubscribe($data) {
    $enableUnsubscribeConfirmation = $this->wp->applyFilters('mailpoet_unsubscribe_confirmation_enabled', true);
    if ($enableUnsubscribeConfirmation) {
      $this->initSubscriptionPage(UserSubscription\Pages::ACTION_CONFIRM_UNSUBSCRIBE, $data);
    } else {
      $this->unsubscribe($data);
    }
  }

  public function manage($data) {
    $this->initSubscriptionPage(UserSubscription\Pages::ACTION_MANAGE, $data);
  }

  public function unsubscribe($data) {
    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_UNSUBSCRIBE, $data);
    $subscription->unsubscribe();
  }

  private function initSubscriptionPage($action, $data) {
    return $this->subscriptionPages->init($action, $data, true, true);
  }
}
