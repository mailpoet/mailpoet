<?php
namespace MailPoet\Subscription;

use MailPoet\Router\Router;
use MailPoet\Router\Endpoints\Subscription as SubscriptionEndpoint;
use MailPoet\Models\Subscriber;
use MailPoet\Models\Setting;

class Url {
  static function getConfirmationUrl($subscriber = false) {
    $post = get_post(Setting::getValue('subscription.pages.confirmation'));
    return self::getSubscriptionUrl($post, 'confirm', $subscriber);
  }

  static function getManageUrl($subscriber = false) {
    $post = get_post(Setting::getValue('subscription.pages.manage'));
    return self::getSubscriptionUrl($post, 'manage', $subscriber);
  }

  static function getUnsubscribeUrl($subscriber = false) {
    $post = get_post(Setting::getValue('subscription.pages.unsubscribe'));
    return self::getSubscriptionUrl($post, 'unsubscribe', $subscriber);
  }

  static function getSubscriptionUrl(
    $post = null, $action = null, $subscriber = false
  ) {
    if($post === null || $action === null) return;

    $url = get_permalink($post);

    if($subscriber !== false) {
      if(is_object($subscriber)) {
        $subscriber = $subscriber->asArray();
      }

      $data = array(
        'token' => Subscriber::generateToken($subscriber['email']),
        'email' => $subscriber['email']
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
}
