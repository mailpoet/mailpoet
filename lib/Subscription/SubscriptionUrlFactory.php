<?php

namespace MailPoet\Subscription;

use MailPoet\Models\Subscriber;
use MailPoet\Router\Endpoints\Subscription as SubscriptionEndpoint;
use MailPoet\Router\Router;
use MailPoet\Settings\Pages as SettingsPages;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\WP\Functions as WPFunctions;

class SubscriptionUrlFactory {

  /** @var SubscriptionUrlFactory */
  private static $instance;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var LinkTokens */
  private $linkTokens;

  public function __construct(WPFunctions $wp, SettingsController $settings, LinkTokens $linkTokens) {
    $this->wp = $wp;
    $this->settings = $settings;
    $this->linkTokens = $linkTokens;
  }

  public function getCaptchaUrl($sessionId) {
    $post = $this->getPost($this->settings->get('subscription.pages.captcha'));
    return $this->getSubscriptionUrl($post, 'captcha', null, ['captcha_session_id' => $sessionId]);
  }

  public function getCaptchaImageUrl($width, $height, $sessionId) {
    $post = $this->getPost($this->settings->get('subscription.pages.captcha'));
    return $this->getSubscriptionUrl(
      $post,
      'captchaImage',
      null,
      ['width' => $width, 'height' => $height, 'captcha_session_id' => $sessionId]
    );
  }

  public function getConfirmationUrl(Subscriber $subscriber = null) {
    $post = $this->getPost($this->settings->get('subscription.pages.confirmation'));
    return $this->getSubscriptionUrl($post, 'confirm', $subscriber);
  }

  public function getManageUrl(Subscriber $subscriber = null) {
    $post = $this->getPost($this->settings->get('subscription.pages.manage'));
    return $this->getSubscriptionUrl($post, 'manage', $subscriber);
  }

  public function getUnsubscribeUrl(Subscriber $subscriber = null) {
    $post = $this->getPost($this->settings->get('subscription.pages.unsubscribe'));
    return $this->getSubscriptionUrl($post, 'unsubscribe', $subscriber);
  }

  public function getSubscriptionUrl(
    $post = null,
    $action = null,
    Subscriber $subscriber = null,
    $data = null
  ) {
    if ($post === null || $action === null) return;

    $url = $this->wp->getPermalink($post);
    if ($subscriber !== null) {
      $data = [
        'token' => $this->linkTokens->getToken($subscriber),
        'email' => $subscriber->email,
      ];
    } elseif (is_null($data)) {
      $data = [
        'preview' => 1,
      ];
    }

    $params = [
      Router::NAME,
      'endpoint=' . SubscriptionEndpoint::ENDPOINT,
      'action=' . $action,
      'data=' . Router::encodeRequestData($data),
    ];

    // add parameters
    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . join('&', $params);

    $urlParams = parse_url($url);
    if (empty($urlParams['scheme'])) {
      $url = $this->wp->getBloginfo('url') . $url;
    }

    return $url;
  }

  /**
   * @return SubscriptionUrlFactory
   */
  public static function getInstance() {
    if (!self::$instance instanceof SubscriptionUrlFactory) {
      self::$instance = new SubscriptionUrlFactory(new WPFunctions, SettingsController::getInstance(), new LinkTokens);
    }
    return self::$instance;
  }

  private function getPost($post = null) {
    if ($post) {
      $postObject = $this->wp->getPost($post);
      if ($postObject) {
        return $postObject;
      }
    }
    // Resort to a default MailPoet page if no page is selected
    $pages = SettingsPages::getMailPoetPages();
    return reset($pages);
  }
}
