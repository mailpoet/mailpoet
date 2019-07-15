<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Subscription as UserSubscription;

if (!defined('ABSPATH')) exit;

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

  function __construct(UserSubscription\Pages $subscription_pages) {
    $this->subscription_pages = $subscription_pages;
  }

  function captcha($data) {
    $this->initSubscriptionPage(UserSubscription\Pages::ACTION_CAPTCHA, $data);
  }

  function captchaImage($data) {
    $captcha = new UserSubscription\Captcha;
    $width = !empty($data['width']) ? (int)$data['width'] : null;
    $height = !empty($data['height']) ? (int)$data['height'] : null;
    return $captcha->renderImage($width, $height);
  }

  function confirm($data) {
    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_CONFIRM, $data);
    $subscription->confirm();
  }

  function manage($data) {
    $this->initSubscriptionPage(UserSubscription\Pages::ACTION_MANAGE, $data);
  }

  function unsubscribe($data) {
    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_UNSUBSCRIBE, $data);
    $subscription->unsubscribe();
  }

  private function initSubscriptionPage($action, $data) {
    return $this->subscription_pages->init($action, $data, true, true);
  }
}
