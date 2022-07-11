<?php

namespace MailPoet\Config;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\Widget;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Shortcodes\Categories\Date;
use MailPoet\Newsletter\Shortcodes\Categories\Link;
use MailPoet\Newsletter\Shortcodes\Categories\Newsletter;
use MailPoet\Newsletter\Shortcodes\Categories\Subscriber as SubscriberCategory;
use MailPoet\Newsletter\Shortcodes\Shortcodes as NewsletterShortcodes;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\Pages;
use MailPoet\WP\Functions as WPFunctions;

class Shortcodes {
  /** @var Pages */
  private $subscriptionPages;

  /** @var WPFunctions */
  private $wp;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var Date */
  private $dateCategory;

  /** @var Link */
  private $linkCategory;

  /** @var Newsletter */
  private $newsletterCategory;

  /** @var SubscriberCategory */
  private $subscriberCategory;

  public function __construct(
    Pages $subscriptionPages,
    WPFunctions $wp,
    SegmentSubscribersRepository $segmentSubscribersRepository,
    SubscribersRepository $subscribersRepository,
    NewsletterUrl $newsletterUrl,
    NewslettersRepository $newslettersRepository,
    Date $dateCategory,
    Link $linkCategory,
    Newsletter $newsletterCategory,
    SubscriberCategory $subscriberCategory
  ) {
    $this->subscriptionPages = $subscriptionPages;
    $this->wp = $wp;
    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->newsletterUrl = $newsletterUrl;
    $this->newslettersRepository = $newslettersRepository;
    $this->dateCategory = $dateCategory;
    $this->linkCategory = $linkCategory;
    $this->newsletterCategory = $newsletterCategory;
    $this->subscriberCategory = $subscriberCategory;
  }

  public function init() {
    // form widget shortcode
    $this->wp->addShortcode('mailpoet_form', [$this, 'formWidget']);

    // subscribers count shortcode
    $this->wp->addShortcode('mailpoet_subscribers_count', [
      $this, 'getSubscribersCount',
    ]);
    $this->wp->addShortcode('wysija_subscribers_count', [
      $this, 'getSubscribersCount',
    ]);

    // archives page
    $this->wp->addShortcode('mailpoet_archive', [
      $this, 'getArchive',
    ]);

    $this->wp->addFilter('mailpoet_archive_email_processed_date', [
      $this, 'renderArchiveDate',
    ], 2);
    $this->wp->addFilter('mailpoet_archive_email_subject_line', [
      $this, 'renderArchiveSubject',
    ], 2, 3);

    // This deprecated notice can be removed after 2023-01-11
    if ($this->wp->hasFilter('mailpoet_archive_email_subject')) {
      $this->wp->deprecatedHook(
        'mailpoet_archive_email_subject_line',
        '3.92.1',
        'mailpoet_archive_email_subject',
        __('Please note that mailpoet_archive_email_subject no longer runs and that the list of parameters of the new filter is different.', 'mailpoet')
      );
    }

    // initialize subscription pages data
    $this->subscriptionPages->init();
    // initialize subscription management shortcodes
    $this->subscriptionPages->initShortcodes();
  }

  public function formWidget($params = []) {
    // IMPORTANT: fixes conflict with MagicMember
    $this->wp->removeShortcode('user_list');

    if (isset($params['id']) && (int)$params['id'] > 0) {
      $formWidget = new Widget();
      return $formWidget->widget([
        'form' => (int)$params['id'],
        'form_type' => 'shortcode',
      ]);
    }
  }

  public function getSubscribersCount($params) {
    if (!empty($params['segments'])) {
      $segmentIds = array_map(function($segmentId) {
        return (int)trim($segmentId);
      }, explode(',', $params['segments']));
    }

    if (empty($segmentIds)) {
      return $this->wp->numberFormatI18n(
        $this->subscribersRepository->countBy(['status' => SubscriberEntity::STATUS_SUBSCRIBED, 'deletedAt' => null])
      );
    } else {
      return $this->wp->numberFormatI18n(
        $this->segmentSubscribersRepository->getSubscribersCountBySegmentIds($segmentIds, SubscriberEntity::STATUS_SUBSCRIBED)
      );
    }
  }

  public function getArchive($params) {
    $segmentIds = [];
    if (!empty($params['segments'])) {
      $segmentIds = array_map(function($segmentId) {
        return (int)trim($segmentId);
      }, explode(',', $params['segments']));
    }

    $html = '';

    $newsletters = $this->newslettersRepository->getArchives($segmentIds);

    $subscriber = $this->subscribersRepository->getCurrentWPUser();

    if (empty($newsletters)) {
      return $this->wp->applyFilters(
        'mailpoet_archive_no_newsletters',
        $this->wp->__('Oops! There are no newsletters to display.', 'mailpoet')
      );
    } else {
      $title = $this->wp->applyFilters('mailpoet_archive_title', '');
      if (!empty($title)) {
        $html .= '<h3 class="mailpoet_archive_title">' . $title . '</h3>';
      }
      $html .= '<ul class="mailpoet_archive">';
      foreach ($newsletters as $newsletter) {
        $queue = $newsletter->getLatestQueue();

        $html .= '<li>' .
          '<span class="mailpoet_archive_date">' .
            $this->wp->applyFilters('mailpoet_archive_email_processed_date', $newsletter) .
          '</span>
          <span class="mailpoet_archive_subject">' .
            $this->wp->applyFilters('mailpoet_archive_email_subject_line', $newsletter, $subscriber, $queue) .
          '</span>
        </li>';
      }
      $html .= '</ul>';
    }
    return $html;
  }

  public function renderArchiveDate(NewsletterEntity $newsletter) {
    $timestamp = null;
    $processedAt = $newsletter->getProcessedAt();

    if (!is_null($processedAt)) {
      $timestamp = $processedAt->getTimestamp();
    }

    return $this->wp->dateI18n(
      $this->wp->getOption('date_format'),
      $timestamp
    );
  }

  public function renderArchiveSubject(NewsletterEntity $newsletter, ?SubscriberEntity $subscriber, ?SendingQueueEntity $queue) {
    if (is_null($subscriber)) {
      $subscriber = new SubscriberEntity();
    }

    $previewUrl = $this->newsletterUrl->getViewInBrowserUrl($newsletter, $subscriber, $queue);
    /**
     * An ugly workaround to make sure state is not shared via NewsletterShortcodes service
     * This should be replaced with injected service when state is removed from this service
     * (i.e. after https://mailpoet.atlassian.net/browse/MAILPOET-4087 is done and merged to master)
     */
    $shortcodeProcessor = new NewsletterShortcodes(
      $this->dateCategory,
      $this->linkCategory,
      $this->newsletterCategory,
      $this->subscriberCategory,
      $this->wp
    );

    $shortcodeProcessor->setNewsletter($newsletter);

    $shortcodeProcessor->setSubscriber($subscriber);
    $shortcodeProcessor->setQueue($queue);
    return '<a href="' . esc_attr($previewUrl) . '" target="_blank" title="'
      . esc_attr(__('Preview in a new tab', 'mailpoet')) . '">'
      . esc_attr((string)$shortcodeProcessor->replace($queue ? $queue->getNewsletterRenderedSubject() : '')) .
      '</a>';
  }
}
