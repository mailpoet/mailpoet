<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Tasks\Sending;
use MailPoet\WP\Functions as WPFunctions;

class Link {
  const CATEGORY_NAME = 'link';

  public static function process(
    $shortcodeDetails,
    $newsletter,
    $subscriber,
    $queue,
    $content,
    $wpUserPreview
  ) {
    $subscriptionUrlFactory = SubscriptionUrlFactory::getInstance();

    switch ($shortcodeDetails['action']) {
      case 'subscription_unsubscribe_url':
        return self::processUrl(
          $shortcodeDetails['action'],
          $subscriptionUrlFactory->getConfirmUnsubscribeUrl($wpUserPreview ? null : $subscriber, self::getSendingQueueId($queue)),
          $queue,
          $wpUserPreview
        );

      case 'subscription_instant_unsubscribe_url':
        return self::processUrl(
          $shortcodeDetails['action'],
          $subscriptionUrlFactory->getUnsubscribeUrl($wpUserPreview ? null : $subscriber, self::getSendingQueueId($queue)),
          $queue,
          $wpUserPreview
        );

      case 'subscription_manage_url':
        return self::processUrl(
          $shortcodeDetails['action'],
          $subscriptionUrlFactory->getManageUrl($wpUserPreview ? null : $subscriber),
          $queue,
          $wpUserPreview
        );

      case 'newsletter_view_in_browser_url':
        $url = NewsletterUrl::getViewInBrowserUrl(
          $newsletter,
          $wpUserPreview ? null : $subscriber,
          $queue,
          $wpUserPreview
        );
        return self::processUrl($shortcodeDetails['action'], $url, $queue, $wpUserPreview);

      default:
        $shortcode = self::getFullShortcode($shortcodeDetails['action']);
        $url = WPFunctions::get()->applyFilters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue,
          $wpUserPreview
        );
        return ($url !== $shortcode) ?
          self::processUrl($shortcodeDetails['action'], $url, $queue, $wpUserPreview) :
          false;
    }
  }

  public static function processUrl($action, $url, $queue, $wpUserPreview = false) {
    if ($wpUserPreview) return $url;
    $settings = SettingsController::getInstance();
    return ($queue !== false && (boolean)$settings->get('tracking.enabled')) ?
      self::getFullShortcode($action) :
      $url;
  }

  public static function processShortcodeAction(
    $shortcodeAction, $newsletter, $subscriber, $queue, $wpUserPreview
  ) {
    $subscriptionUrlFactory = SubscriptionUrlFactory::getInstance();
    switch ($shortcodeAction) {
      case 'subscription_unsubscribe_url':
        $url = $subscriptionUrlFactory->getConfirmUnsubscribeUrl($subscriber, self::getSendingQueueId($queue));
        break;
      case 'subscription_instant_unsubscribe_url':
        $url = $subscriptionUrlFactory->getUnsubscribeUrl($subscriber, self::getSendingQueueId($queue));
        break;
      case 'subscription_manage_url':
        $url = $subscriptionUrlFactory->getManageUrl($subscriber);
        break;
      case 'newsletter_view_in_browser_url':
        $url = NewsletterUrl::getViewInBrowserUrl(
          $newsletter,
          $subscriber,
          $queue,
          false
        );
        break;
      default:
        $shortcode = self::getFullShortcode($shortcodeAction);
        $url = WPFunctions::get()->applyFilters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue,
          $wpUserPreview
        );
        $url = ($url !== $shortcodeAction) ? $url : false;
        break;
    }
    return $url;
  }

  private static function getFullShortcode($action) {
    return sprintf('[link:%s]', $action);
  }

  /**
   * @return int|null
   */
  private static function getSendingQueueId($queue) {
    if ($queue instanceof Sending) {
      return (int)$queue->id;
    }
    if ($queue instanceof SendingQueueEntity) {
      return $queue->getId();
    }
    return null;
  }
}
