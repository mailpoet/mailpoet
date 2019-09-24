<?php
namespace MailPoet\Subscription;

use MailPoet\Router\Router;
use MailPoet\Router\Endpoints\Subscription as SubscriptionEndpoint;
use MailPoet\Models\Subscriber;
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
  private $link_tokens;

  public function __construct(WPFunctions $wp, SettingsController $settings, LinkTokens $link_tokens) {
    $this->wp = $wp;
    $this->settings = $settings;
    $this->link_tokens = $link_tokens;
  }

  function getCaptchaUrl() {
    $post = $this->getPost($this->settings->get('subscription.pages.captcha'));
    return $this->getSubscriptionUrl($post, 'captcha', null);
  }

  function getCaptchaImageUrl($width, $height) {
    $post = $this->getPost($this->settings->get('subscription.pages.captcha'));
    return $this->getSubscriptionUrl($post, 'captchaImage', null, ['width' => $width, 'height' => $height]);
  }

  function getConfirmationUrl(Subscriber $subscriber = null) {
    $post = $this->getPost($this->settings->get('subscription.pages.confirmation'));
    return $this->getSubscriptionUrl($post, 'confirm', $subscriber);
  }

  function getManageUrl(Subscriber $subscriber = null) {
    $post = $this->getPost($this->settings->get('subscription.pages.manage'));
    return $this->getSubscriptionUrl($post, 'manage', $subscriber);
  }

  function getUnsubscribeUrl(Subscriber $subscriber = null) {
    $post = $this->getPost($this->settings->get('subscription.pages.unsubscribe'));
    return $this->getSubscriptionUrl($post, 'unsubscribe', $subscriber);
  }

  function getSubscriptionUrl(
    $post = null,
    $action = null,
    Subscriber $subscriber = null,
    $data = null
  ) {
    if ($post === null || $action === null) return;

    $url = $this->wp->getPermalink($post);
    if ($subscriber !== null) {
      $data = [
        'token' => $this->link_tokens->getToken($subscriber),
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

    $url_params = parse_url($url);
    if (empty($url_params['scheme'])) {
      $url = $this->wp->getBloginfo('url') . $url;
    }

    return $url;
  }

  /**
   * @return SubscriptionUrlFactory
   */
  static function getInstance() {
    if (!self::$instance instanceof SubscriptionUrlFactory) {
      self::$instance = new SubscriptionUrlFactory(new WPFunctions, new SettingsController, new LinkTokens);
    }
    return self::$instance;
  }

  private function getPost($post = null) {
    if ($post) {
      $post_object = $this->wp->getPost($post);
      if ($post_object) {
        return $post_object;
      }
    }
    // Resort to a default MailPoet page if no page is selected
    $pages = SettingsPages::getMailPoetPages();
    return reset($pages);
  }
}
