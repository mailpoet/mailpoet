<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Subscription as UserSubscription;

class Subscription {
  const ENDPOINT = 'subscription';
  const ACTION_CAPTCHA = 'captcha';
  const ACTION_CAPTCHA_IMAGE = 'captchaImage';
  const ACTION_CONFIRM = 'confirm';
  const ACTION_MANAGE = 'manage';
  const ACTION_UNSUBSCRIBE = 'unsubscribe';
  public $allowed_actions = [
    self::ACTION_CAPTCHA,
    self::ACTION_CAPTCHA_IMAGE,
    self::ACTION_CONFIRM,
    self::ACTION_MANAGE,
    self::ACTION_UNSUBSCRIBE,
  ];
  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  /** @var UserSubscription\Pages */
  private $subscription_pages;

  public function __construct(UserSubscription\Pages $subscriptionPages) {
    $this->subscriptionPages = $subscriptionPages;
  }

  public function captcha($data) {
    $this->initSubscriptionPage(UserSubscription\Pages::ACTION_CAPTCHA, $data);
  }

  public function captchaImage($data) {
    $captcha = new UserSubscription\Captcha;
    $width = !empty($data['width']) ? (int)$data['width'] : null;
    $height = !empty($data['height']) ? (int)$data['height'] : null;
    $sessionId = !empty($data['captcha_session_id']) ? $data['captcha_session_id'] : null;
    return $captcha->renderImage($width, $height, $sessionId);
  }

  public function confirm($data) {
    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_CONFIRM, $data);
    $subscription->confirm();
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
