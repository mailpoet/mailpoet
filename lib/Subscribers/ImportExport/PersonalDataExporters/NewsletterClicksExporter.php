<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\Subscriber;

class NewsletterClicksExporter {

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

    $statistics = StatisticsClicks::getAllForSubsciber($subscriber)
       ->limit(self::LIMIT)
       ->offset(self::LIMIT * ($page - 1))
       ->findArray();

    foreach($statistics as $row) {
      $result[] = $this->exportNewsletter($row);
    }

    return $result;
  }

  private function exportNewsletter($row) {
    $newsletter_data = array();
    $newsletter_data[] = array(
      'name' => __('Email subject', 'mailpoet'),
      'value' => $row['newsletter_rendered_subject'],
    );
    $newsletter_data[] = array(
      'name' => __('Timestamp of the click event', 'mailpoet'),
      'value' => $row['created_at'],
    );
    $newsletter_data[] = array(
      'name' => __('Url', 'mailpoet'),
      'value' => $row['url'],
    );
    return array(
      'group_id' => 'mailpoet-newsletter-clicks',
      'group_label' => __('MailPoet Emails Clicks', 'mailpoet'),
      'item_id' => 'newsletter-' . $row['id'],
      'data' => $newsletter_data,
    );
  }

}