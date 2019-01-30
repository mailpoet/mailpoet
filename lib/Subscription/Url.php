<?php
namespace MailPoet\Subscription;

use MailPoet\Router\Router;
use MailPoet\Router\Endpoints\Subscription as SubscriptionEndpoint;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsController;

class Url {
  static function getConfirmationUrl(Subscriber $subscriber = null) {
    $post = get_post(self::getSetting('subscription.pages.confirmation'));
    return self::getSubscriptionUrl($post, 'confirm', $subscriber);
  }

  static function getManageUrl(Subscriber $subscriber = null) {
    $post = get_post(self::getSetting('subscription.pages.manage'));
    return self::getSubscriptionUrl($post, 'manage', $subscriber);
  }

  static function getUnsubscribeUrl(Subscriber $subscriber = null) {
    $post = get_post(self::getSetting('subscription.pages.unsubscribe'));
    return self::getSubscriptionUrl($post, 'unsubscribe', $subscriber);
  }

  static function getSubscriptionUrl(
    $post = null, $action = null, Subscriber $subscriber = null
  ) {
    if($post === null || $action === null) return;

    $url = get_permalink($post);

    if($subscriber !== null) {
      $data = array(
        'token' => Subscriber::generateToken($subscriber->email),
        'email' => $subscriber->email
      );
    } else {
      $data = array(
        'preview' => 1
      );
    }

    $params = array(
      Router::NAME,
      'endpoint='.SubscriptionEndpoint::ENDPOINT,
      'action='.$action,
      'data='.Router::encodeRequestData($data)
    );

    // add parameters
    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?').join('&', $params);

    $url_params = parse_url($url);
    if(empty($url_params['scheme'])) {
      $url = get_bloginfo('url').$url;
    }

    return $url;
  }

  static private function getSetting($key) {
    $setting = new SettingsController();
    return $setting->get($key);
  }
}
