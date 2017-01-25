<?php
namespace MailPoet\Config;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Url as NewsletterUrl;

class Shortcodes {
  function __construct() {
  }

  function init() {
    // form widget shortcode
    add_shortcode('mailpoet_form', array($this, 'formWidget'));
    add_shortcode('wysija_form', array($this, 'formWidget'));

    // subscribers count shortcode
    add_shortcode('mailpoet_subscribers_count', array(
      $this, 'getSubscribersCount'
    ));
    add_shortcode('wysija_subscribers_count', array(
      $this, 'getSubscribersCount'
    ));

    // archives page
    add_shortcode('mailpoet_archive', array(
      $this, 'getArchive'
    ));

    add_filter('mailpoet_archive_date', array(
      $this, 'renderArchiveDate'
    ), 2);
    add_filter('mailpoet_archive_subject', array(
      $this, 'renderArchiveSubject'
    ), 2, 3);
  }

  function formWidget($params = array()) {
    // IMPORTANT: fixes conflict with MagicMember
    remove_shortcode('user_list');

    if(isset($params['id']) && (int)$params['id'] > 0) {
      $form_widget = new \MailPoet\Form\Widget();
      return $form_widget->widget(array(
        'form' => (int)$params['id'],
        'form_type' => 'shortcode'
      ));
    }
  }

  function getSubscribersCount($params) {
    if(!empty($params['segments'])) {
      $segment_ids = array_map(function($segment_id) {
        return (int)trim($segment_id);
      }, explode(',', $params['segments']));
    }

    if(empty($segment_ids)) {
      return Subscriber::filter('subscribed')->count();
    } else {
      return SubscriberSegment::whereIn('segment_id', $segment_ids)
        ->select('subscriber_id')->distinct()
        ->filter('subscribed')
        ->findResultSet()->count();
    }
  }

  function getArchive($params) {
    $segment_ids = array();
    if(!empty($params['segments'])) {
      $segment_ids = array_map(function($segment_id) {
        return (int)trim($segment_id);
      }, explode(',', $params['segments']));
    }

    $html = '';

    $newsletters = Newsletter::getArchives($segment_ids);

    $subscriber = Subscriber::getCurrentWPUser();

    if(empty($newsletters)) {
      return apply_filters(
        'mailpoet_archive_no_newsletters',
        __('Oops! There are no newsletters to display.', 'mailpoet')
      );
    } else {
      $title = apply_filters('mailpoet_archive_title', '');
      if(!empty($title)) {
        $html .= '<h3 class="mailpoet_archive_title">'.$title.'</h3>';
      }

      $html .= '<ul class="mailpoet_archive">';
      foreach($newsletters as $newsletter) {
        $queue = $newsletter->queue()->findOne();
        $html .= '<li>'.
          '<span class="mailpoet_archive_date">'.
            apply_filters('mailpoet_archive_date', $newsletter).
          '</span>
          <span class="mailpoet_archive_subject">'.
            apply_filters('mailpoet_archive_subject', $newsletter, $subscriber, $queue).
          '</span>
        </li>';
      }
      $html .= '</ul>';
    }
    return $html;
  }

  function renderArchiveDate($newsletter) {
    return date_i18n(
      get_option('date_format'),
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
      .esc_attr($newsletter->subject).
    '</a>';
  }
}