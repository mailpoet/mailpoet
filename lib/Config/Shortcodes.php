<?php

namespace MailPoet\Config;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\Widget;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Subscription\Pages;
use MailPoet\WP\Functions as WPFunctions;

class Shortcodes {
  /** @var Pages */
  private $subscriptionPages;

  /** @var WPFunctions */
  private $wp;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  public function __construct(
    Pages $subscriptionPages,
    WPFunctions $wp,
    SegmentSubscribersRepository $segmentSubscribersRepository
  ) {
    $this->subscriptionPages = $subscriptionPages;
    $this->wp = $wp;
    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
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

    $this->wp->addFilter('mailpoet_archive_date', [
      $this, 'renderArchiveDate',
    ], 2);
    $this->wp->addFilter('mailpoet_archive_subject', [
      $this, 'renderArchiveSubject',
    ], 2, 3);
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
      return $this->wp->numberFormatI18n(Subscriber::filter('subscribed')->count());
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

    $newsletters = Newsletter::getArchives($segmentIds);

    $subscriber = Subscriber::getCurrentWPUser();

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
        $queue = $newsletter->queue()->findOne();
        $html .= '<li>' .
          '<span class="mailpoet_archive_date">' .
            $this->wp->applyFilters('mailpoet_archive_date', $newsletter) .
          '</span>
          <span class="mailpoet_archive_subject">' .
            $this->wp->applyFilters('mailpoet_archive_subject', $newsletter, $subscriber, $queue) .
          '</span>
        </li>';
      }
      $html .= '</ul>';
    }
    return $html;
  }

  public function renderArchiveDate($newsletter) {
    return $this->wp->dateI18n(
      $this->wp->getOption('date_format'),
      strtotime($newsletter->processedAt)
    );
  }

  public function renderArchiveSubject($newsletter, $subscriber, $queue) {
    $previewUrl = NewsletterUrl::getViewInBrowserUrl($newsletter, $subscriber, $queue);
    return '<a href="' . esc_attr($previewUrl) . '" target="_blank" title="'
      . esc_attr(__('Preview in a new tab', 'mailpoet')) . '">'
      . esc_attr($newsletter->newsletterRenderedSubject) .
    '</a>';
  }
}
