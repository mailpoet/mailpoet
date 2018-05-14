<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\Newsletter;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Url;

class NewslettersExporter {

  const LIMIT = 100;

  function export($email, $page = 1) {
    $data = $this->exportSubscriber(Subscriber::findOne(trim($email)), $page);
    return array(
      'data' => $data,
      'done' => count($data) < self::LIMIT,
    );
  }

  private function exportSubscriber($subscriber, $page) {
    if(!$subscriber) return array();

    $result = array();

    $statistics = StatisticsNewsletters::getAllForSubsciber($subscriber)
      ->limit(self::LIMIT)
      ->offset(self::LIMIT * ($page - 1))
      ->findArray();

    $newsletters = $this->loadNewsletters($statistics);

    foreach($statistics as $row) {
      $result[] = $this->exportNewsletter($row, $newsletters, $subscriber);
    }

    return $result;
  }

  private function exportNewsletter($statistics_row, $newsletters, $subscriber) {
    $newsletter_data = array();
    $newsletter_data[] = array(
      'name' => __('Email subject', 'mailpoet'),
      'value' => $statistics_row['newsletter_rendered_subject'],
    );
    $newsletter_data[] = array(
      'name' => __('Sent at', 'mailpoet'),
      'value' => $statistics_row['sent_at'],
    );
    if(isset($statistics_row['opened_at'])) {
      $newsletter_data[] = array(
        'name' => __('Opened', 'mailpoet'),
        'value' => 'Yes',
      );
      $newsletter_data[] = array(
        'name' => __('Opened at', 'mailpoet'),
        'value' => $statistics_row['opened_at'],
      );
    } else {
      $newsletter_data[] = array(
        'name' => __('Opened', 'mailpoet'),
        'value' => __('No', 'mailpoet'),
      );
    }
    if(isset($newsletters[$statistics_row['newsletter_id']])) {
      $newsletter_data[] = array(
        'name' => __('Email preview', 'mailpoet'),
        'value' => Url::getViewInBrowserUrl(
          '',
          $newsletters[$statistics_row['newsletter_id']],
          $subscriber,
          false,
          true
        ),
      );
    }
    return array(
      'group_id' => 'mailpoet-newsletters',
      'group_label' => __('MailPoet Emails Sent', 'mailpoet'),
      'item_id' => 'newsletter-' . $statistics_row['newsletter_id'],
      'data' => $newsletter_data,
    );
  }

  private function loadNewsletters($statistics) {
    $newsletter_ids = array_map(function ($statistics_row) {
      return $statistics_row['newsletter_id'];
    }, $statistics);

    if(empty($newsletter_ids)) return array();

    $newsletters = Newsletter::whereIn('id', $newsletter_ids)->findMany();

    $result = array();
    foreach($newsletters as $newsletter) {
      $result[$newsletter->id()] = $newsletter;
    }
    return $result;
  }

}