<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\WP\Functions as WPFunctions;

class Link implements CategoryInterface {
  const CATEGORY_NAME = 'link';

  /** @var SettingsController */
  private $settings;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SettingsController $settings,
    NewsletterUrl $newsletterUrl,
    WPFunctions $wp
  ) {
    $this->settings = $settings;
    $this->newsletterUrl = $newsletterUrl;
    $this->wp = $wp;
  }

  public function process(
    array $shortcodeDetails,
    NewsletterEntity $newsletter = null,
    SubscriberEntity $subscriber = null,
    SendingQueueEntity $queue = null,
    string $content = '',
    bool $wpUserPreview = false
  ): ?string {
    $subscriptionUrlFactory = SubscriptionUrlFactory::getInstance();
    $subscriberModel = $this->getSubscriberModel($subscriber);
    $newsletterModel = $this->getNewsletterModel($newsletter);
    $queueModel = $this->getQueueModel($queue);

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
        $url = $this->newsletterUrl->getViewInBrowserUrl(
          $newsletterModel,
          $wpUserPreview ? null : $subscriberModel,
          $queueModel,
          $wpUserPreview
        );
        return self::processUrl($shortcodeDetails['action'], $url, $queue, $wpUserPreview);

      default:
        $shortcode = self::getFullShortcode($shortcodeDetails['action']);
        $url = $this->wp->applyFilters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue,
          $shortcodeDetails['arguments'],
          $wpUserPreview
        );

        return ($url !== $shortcode) ?
          self::processUrl($shortcodeDetails['action'], $url, $queue, $wpUserPreview) :
          null;
    }
  }

  public function processUrl($action, $url, $queue, $wpUserPreview = false): string {
    if ($wpUserPreview) return $url;
    return ($queue !== false && (boolean)$this->settings->get('tracking.enabled')) ?
      self::getFullShortcode($action) :
      $url;
  }

  public function processShortcodeAction(
    $shortcodeAction,
    NewsletterEntity $newsletter = null,
    SubscriberEntity $subscriber = null,
    SendingQueueEntity $queue = null,
    $wpUserPreview = false
  ): ?string {
    $subscriberModel = $this->getSubscriberModel($subscriber);
    $newsletterModel = $this->getNewsletterModel($newsletter);
    $queueModel = $this->getQueueModel($queue);
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
        $url = $this->newsletterUrl->getViewInBrowserUrl(
          $newsletterModel,
          $subscriberModel,
          $queueModel,
          false
        );
        break;
      default:
        $shortcode = self::getFullShortcode($shortcodeAction);
        $url = $this->wp->applyFilters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue,
          $wpUserPreview
        );
        $url = ($url !== $shortcodeAction) ? $url : null;
        break;
    }
    return $url;
  }

  private function getFullShortcode($action): string {
    return sprintf('[link:%s]', $action);
  }

  private function getSendingQueueId($queue): ?int {
    if ($queue instanceof SendingQueueEntity) {
      return $queue->getId();
    }
    return null;
  }

  // temporary function until Links are refactored to Doctrine
  private function getSubscriberModel(SubscriberEntity $subscriber = null): ?SubscriberModel {
    if (!$subscriber) return null;
    $subscriberModel = SubscriberModel::where('id', $subscriber->getId())->findOne();
    if ($subscriberModel) return $subscriberModel;
    return null;
  }

  // temporary function until Links are refactored to Doctrine
  private function getNewsletterModel(NewsletterEntity $newsletter = null): ?NewsletterModel {
    if (!$newsletter) return null;
    $newsletterModel = NewsletterModel::where('id', $newsletter->getId())->findOne();
    if ($newsletterModel) return $newsletterModel;
    return null;
  }

  // temporary function until Links are refactored to Doctrine
  private function getQueueModel(SendingQueueEntity $queue = null): ?SendingQueue {
    if (!$queue) return null;
    $queueModel = SendingQueue::where('id', $queue->getId())->findOne();
    if ($queueModel) return $queueModel;
    return null;
  }
}
