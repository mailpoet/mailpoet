<?php
namespace MailPoet\Subscription;

use \MailPoet\Models\Subscriber;
use \MailPoet\Models\Setting;

class Url {
  static function getConfirmationUrl($subscriber = false) {
    $post = get_post(Setting::getValue('signup_confirmation.page'));
    return self::getSubscriptionUrl($post, 'confirm', $subscriber);
  }

  static function getManageUrl($subscriber = false) {
    $post = get_post(Setting::getValue('subscription.page'));
    return self::getSubscriptionUrl($post, 'manage', $subscriber);
  }

  static function getUnsubscribeUrl($subscriber = false) {
    $post = get_post(Setting::getValue('subscription.page'));
    return self::getSubscriptionUrl($post, 'unsubscribe', $subscriber);
  }

  private static function getSubscriptionUrl(
    $post = null, $action = null, $subscriber = false
  ) {
    if($post === null || $action === null) return;

    $url = get_permalink($post);

    if($subscriber !== false) {
      $params = array(
        'mailpoet_action='.$action,
        'mailpoet_token='.Subscriber::generateToken($subscriber->email),
        'mailpoet_email='.$subscriber->email
      );
    } else {
      $params = array(
        'mailpoet_action='.$action,
        'preview'
      );
    }
    // add parameters
    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?').join('&', $params);

    $url_params = parse_url($url);
    if(empty($url_params['scheme'])) {
      $url = get_bloginfo('url').$url;
    }

    return $url;
  }
}