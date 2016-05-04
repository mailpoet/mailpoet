<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscription\Url as SubscriptionUrl;

class Link {
  /*
    {
      text: '<%= __('Unsubscribe') %>',-
      shortcode: 'subscription:unsubscribe',
    },
    {
      text: '<%= __('Manage subscription') %>',
      shortcode: 'subscription:manage',
    },
    {
      text: '<%= __('View in browser link') %>',
      shortcode: 'newsletter:view_in_browser',
    }
   */
  static function process($action,
    $default_value = false,
    $newsletter,
    $subscriber,
    $queue = false
  ) {
    switch($action) {
      case 'subscription_unsubscribe':
        $action = 'subscription_unsubscribe_url';
        $url = self::processUrl(
          $action,
          esc_attr(SubscriptionUrl::getUnsubscribeUrl($subscriber)),
          $queue
        );
        return sprintf(
          '<a target="_blank" href="%s">%s</a>',
          $url,
          __('Unsubscribe')
        );
      break;

      case 'subscription_unsubscribe_url':
        return self::processUrl(
          $action,
          SubscriptionUrl::getUnsubscribeUrl($subscriber),
          $queue
        );
      break;

      case 'subscription_manage':
        $url = self::processUrl(
          $action = 'subscription_manage_url',
          esc_attr(SubscriptionUrl::getManageUrl($subscriber)),
          $queue
        );
        return sprintf(
          '<a target="_blank" href="%s">%s</a>',
          $url,
          __('Manage subscription')
        );
      break;

      case 'subscription_manage_url':
        return self::processUrl(
          $action,
          SubscriptionUrl::getManageUrl($subscriber),
          $queue
        );
      break;

      case 'newsletter_view_in_browser':
        $action = 'view_in_browser_url';
        $url = esc_attr(self::getViewInBrowserUrl($newsletter, $subscriber, $queue));
        $url = self::processUrl($action, $url, $queue);
        return sprintf(
          '<a target="_blank" href="%s">%s</a>',
          $url,
          __('View in your browser')
        );
      break;

      case 'newsletter_view_in_browser_url':
        $url = self::getViewInBrowserUrl($newsletter, $subscriber, $queue);
        return self::processUrl($action, $url, $queue);
        break;

      default:
        $shortcode = self::getShortcode($action);
        $url = apply_filters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue
        );
        return ($url !== $shortcode) ?
          self::processUrl($action, $url, $queue) :
          false;
      break;
    }
  }

  static function getViewInBrowserUrl(
    $newsletter,
    $subscriber = false,
    $queue = false
  ) {
    $data = array(
      'newsletter' => (isset($newsletter['id'])) ?
        $newsletter['id'] :
        $newsletter,
      'subscriber' => (isset($subscriber['id'])) ?
        $subscriber['id'] :
        $subscriber,
      'subscriber_token' => (isset($subscriber['id'])) ?
        Subscriber::generateToken($subscriber['email']) :
        false,
      'queue' => (isset($queue['id'])) ?
        $queue['id'] :
        $queue
    );
    $data = rtrim(base64_encode(serialize($data)), '=');
    return home_url() . '/?mailpoet&endpoint=view_in_browser&data=' . $data;
  }

  static function processUrl($action, $url, $queue) {
    return ($queue !== false && (boolean) Setting::getValue('tracking.enabled')) ?
      self::getShortcode($action) :
      $url;
  }

  static function processShortcodeAction(
    $shortcode_action, $newsletter, $subscriber, $queue
  ) {
    switch($shortcode_action) {
      case 'subscription_unsubscribe_url':
        // track unsubscribe event
        if((boolean) Setting::getValue('tracking.enabled')) {
          $unsubscribe = new Unsubscribes();
          $unsubscribe->track($subscriber['id'], $queue['id'], $newsletter['id']);
        }
        $url = SubscriptionUrl::getUnsubscribeUrl($subscriber);
        break;
      case 'subscription_manage_url':
        $url = SubscriptionUrl::getManageUrl($subscriber);
        break;
      case 'newsletter_view_in_browser_url':
        $url = Link::getViewInBrowserUrl($newsletter, $subscriber, $queue);
        break;
      default:
        $shortcode = self::getShortcode($shortcode_action);
        $url = apply_filters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue
        );
        $url = ($url !== $shortcode_action) ? $url : false;
        break;
    }
    return $url;
  }

  private static function getShortcode($action) {
    return sprintf('[link:%s]', $action);
  }
}