<?php

namespace MailPoet\Config;

use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Subscription\Pages;
use MailPoet\WP\Functions as WPFunctions;

class Shortcodes {
  private $wp;

  function __construct() {
    $this->wp = new WPFunctions;
  }

  function init() {
    // form widget shortcode
    WPFunctions::get()->addShortcode('mailpoet_form', array($this, 'formWidget'));

    // subscribers count shortcode
    WPFunctions::get()->addShortcode('mailpoet_subscribers_count', array(
      $this, 'getSubscribersCount'
    ));
    WPFunctions::get()->addShortcode('wysija_subscribers_count', array(
      $this, 'getSubscribersCount'
    ));

    // archives page
    WPFunctions::get()->addShortcode('mailpoet_archive', array(
      $this, 'getArchive'
    ));

    $this->wp->addFilter('mailpoet_archive_date', array(
      $this, 'renderArchiveDate'
    ), 2);
    $this->wp->addFilter('mailpoet_archive_subject', array(
      $this, 'renderArchiveSubject'
    ), 2, 3);

    // initialize subscription management shortcodes
    $subscription_page = new Pages();
    $subscription_page->initShortcodes();
  }

  function formWidget($params = array()) {
    // IMPORTANT: fixes conflict with MagicMember
    WPFunctions::get()->removeShortcode('user_list');

    if (isset($params['id']) && (int)$params['id'] > 0) {
      $form_widget = new \MailPoet\Form\Widget();
      return $form_widget->widget(array(
        'form' => (int)$params['id'],
        'form_type' => 'shortcode'
      ));
    }
  }

  function getSubscribersCount($params) {
    if (!empty($params['segments'])) {
      $segment_ids = array_map(function($segment_id) {
        return (int)trim($segment_id);
      }, explode(',', $params['segments']));
    }

    if (empty($segment_ids)) {
      return WPFunctions::get()->numberFormatI18n(Subscriber::filter('subscribed')->count());
    } else {
      return WPFunctions::get()->numberFormatI18n(
        SubscriberSegment::whereIn('segment_id', $segment_ids)
          ->select('subscriber_id')->distinct()
          ->filter('subscribed')
          ->findResultSet()->count()
      );
    }
  }

  function getArchive($params) {
    $segment_ids = array();
    if (!empty($params['segments'])) {
      $segment_ids = array_map(function($segment_id) {
        return (int)trim($segment_id);
      }, explode(',', $params['segments']));
    }

    $html = '';

    $newsletters = Newsletter::getArchives($segment_ids);

    $subscriber = Subscriber::getCurrentWPUser();

    if (empty($newsletters)) {
      return $this->wp->applyFilters(
        'mailpoet_archive_no_newsletters',
        WPFunctions::get()->__('Oops! There are no newsletters to display.', 'mailpoet')
      );
    } else {
      $title = $this->wp->applyFilters('mailpoet_archive_title', '');
      if (!empty($title)) {
        $html .= '<h3 class="mailpoet_archive_title">'.$title.'</h3>';
      }
      $html .= '<ul class="mailpoet_archive">';
      foreach ($newsletters as $newsletter) {
        $queue = $newsletter->queue()->findOne();
        $html .= '<li>'.
          '<span class="mailpoet_archive_date">'.
            $this->wp->applyFilters('mailpoet_archive_date', $newsletter).
          '</span>
          <span class="mailpoet_archive_subject">'.
            $this->wp->applyFilters('mailpoet_archive_subject', $newsletter, $subscriber, $queue).
          '</span>
        </li>';
      }
      $html .= '</ul>';
    }
    return $html;
  }

  function renderArchiveDate($newsletter) {
    return WPFunctions::get()->dateI18n(
      WPFunctions::get()->getOption('date_format'),
      strtotime($newsletter->processed_at)
    );
  }

  function renderArchiveSubject($newsletter, $subscriber, $queue) {
    $preview_url = NewsletterUrl::getViewInBrowserUrl(
      NewsletterUrl::TYPE_ARCHIVE,
      $newsletter,
      $subscriber,
      $queue
    );
    return '<a href="'.esc_attr($preview_url).'" target="_blank" title="'
      .esc_attr(__('Preview in a new tab', 'mailpoet')).'">'
      .esc_attr($newsletter->newsletter_rendered_subject).
    '</a>';
  }
}
