<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\Setting;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscription\Url as SubscriptionUrl;

class Link {
  static function process(
    $action,
    $action_argument,
    $action_argument_value,
    $newsletter,
    $subscriber,
    $queue,
    $content,
    $wp_user_preview
  ) {
    switch($action) {
      case 'subscription_unsubscribe_url':
        return self::processUrl(
          $action,
          SubscriptionUrl::getUnsubscribeUrl($subscriber),
          $queue,
          $wp_user_preview
        );

      case 'subscription_manage_url':
        return self::processUrl(
          $action,
          SubscriptionUrl::getManageUrl($subscriber),
          $queue,
          $wp_user_preview
        );

      case 'newsletter_view_in_browser_url':
        $url = NewsletterUrl::getViewInBrowserUrl(
          $type = null,
          $newsletter,
          $subscriber,
          $queue,
          $wp_user_preview
        );
        return self::processUrl($action, $url, $queue, $wp_user_preview);

      default:
        $shortcode = self::getFullShortcode($action);
        $url = apply_filters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue,
          $wp_user_preview
        );
        return ($url !== $shortcode) ?
          self::processUrl($action, $url, $queue, $wp_user_preview) :
          false;
    }
  }

  static function processUrl($action, $url, $queue, $wp_user_preview = false) {
    if($wp_user_preview) return '#';
    return ($queue !== false && (boolean)Setting::getValue('tracking.enabled')) ?
      self::getFullShortcode($action) :
      $url;
  }

  static function processShortcodeAction(
    $shortcode_action, $newsletter, $subscriber, $queue, $wp_user_preview
  ) {
    switch($shortcode_action) {
      case 'subscription_unsubscribe_url':
        // track unsubscribe event
        if((boolean)Setting::getValue('tracking.enabled') && !$wp_user_preview) {
          $unsubscribe_event = new Unsubscribes();
          $unsubscribe_event->track($newsletter->id, $subscriber->id, $queue->id);
        }
        $url = SubscriptionUrl::getUnsubscribeUrl($subscriber);
        break;
      case 'subscription_manage_url':
        $url = SubscriptionUrl::getManageUrl($subscriber);
        break;
      case 'newsletter_view_in_browser_url':
        $url = NewsletterUrl::getViewInBrowserUrl(
          $type = null,
          $newsletter,
          $subscriber,
          $queue
        );
        break;
      default:
        $shortcode = self::getFullShortcode($shortcode_action);
        $url = apply_filters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue,
          $wp_user_preview
        );
        $url = ($url !== $shortcode_action) ? $url : false;
        break;
    }
    return $url;
  }

  private static function getFullShortcode($action) {
    return sprintf('[link:%s]', $action);
  }
}