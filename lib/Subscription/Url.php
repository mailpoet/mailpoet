<?php
namespace MailPoet\Subscription;

use MailPoet\Router\Router;
use MailPoet\Router\Endpoints\Subscription as SubscriptionEndpoint;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\Pages as SettingsPages;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\WP\Functions as WPFunctions;

class Url {
  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  public function __construct(WPFunctions $wp, SettingsController $settings) {
    $this->wp = $wp;
    $this->settings = $settings;
  }

  function getCaptchaUrl() {
    $post = self::getPost($this->settings->get('subscription.pages.captcha'));
    return self::getSubscriptionUrl($post, 'captcha', null);
  }

  function getCaptchaImageUrl($width, $height) {
    $post = self::getPost($this->settings->get('subscription.pages.captcha'));
    return self::getSubscriptionUrl($post, 'captchaImage', null, ['width' => $width, 'height' => $height]);
  }

  function getConfirmationUrl(Subscriber $subscriber = null) {
    $post = self::getPost($this->settings->get('subscription.pages.confirmation'));
    return self::getSubscriptionUrl($post, 'confirm', $subscriber);
  }

  static function getManageUrl(Subscriber $subscriber = null) {
    $post = self::getPost(self::getSetting('subscription.pages.manage'));
    return self::getSubscriptionUrl($post, 'manage', $subscriber);
  }

  static function getUnsubscribeUrl(Subscriber $subscriber = null) {
    $post = self::getPost(self::getSetting('subscription.pages.unsubscribe'));
    return self::getSubscriptionUrl($post, 'unsubscribe', $subscriber);
  }

  static function getSubscriptionUrl(
    $post = null,
    $action = null,
    Subscriber $subscriber = null,
    $data = null
  ) {
    if ($post === null || $action === null) return;

    $url = WPFunctions::get()->getPermalink($post);
    $link_tokens = new LinkTokens;
    if ($subscriber !== null) {
      $data = [
        'token' => $link_tokens->getToken($subscriber),
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
      $url = WPFunctions::get()->getBloginfo('url') . $url;
    }

    return $url;
  }

  static private function getPost($post = null) {
    if ($post) {
      $post_object = WPFunctions::get()->getPost($post);
      if ($post_object) {
        return $post_object;
      }
    }
    // Resort to a default MailPoet page if no page is selected
    $pages = SettingsPages::getMailPoetPages();
    return reset($pages);
  }

  static private function getSetting($key) {
    $setting = new SettingsController();
    return $setting->get($key);
  }
}
