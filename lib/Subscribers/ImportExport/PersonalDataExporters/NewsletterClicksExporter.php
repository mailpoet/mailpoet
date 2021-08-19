<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\Subscriber;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterClicksExporter {

  const LIMIT = 100;

  public function export($email, $page = 1) {
    $data = $this->exportSubscriber(Subscriber::findOne(trim($email)), $page);
    return [
      'data' => $data,
      'done' => count($data) < self::LIMIT,
    ];
  }

  private function exportSubscriber($subscriber, $page) {
    if (!$subscriber) return [];

    $result = [];

    $statistics = StatisticsClicks::getAllForSubscriber($subscriber)
       ->limit(self::LIMIT)
       ->offset(self::LIMIT * ($page - 1))
       ->findArray();

    foreach ($statistics as $row) {
      $result[] = $this->exportNewsletter($row);
    }

    return $result;
  }

  private function exportNewsletter($row) {
    $newsletterData = [];
    $newsletterData[] = [
      'name' => WPFunctions::get()->__('Email subject', 'mailpoet'),
      'value' => $row['newsletter_rendered_subject'],
    ];
    $newsletterData[] = [
      'name' => WPFunctions::get()->__('Timestamp of the click event', 'mailpoet'),
      'value' => $row['created_at'],
    ];
    $newsletterData[] = [
      'name' => WPFunctions::get()->__('URL', 'mailpoet'),
      'value' => $row['url'],
    ];

    if (!is_null($row['user_agent'])) {
      $userAgent = $row['user_agent'];
    } else {
      $userAgent = WPFunctions::get()->__('Unknown', 'mailpoet');
    }

    $newsletterData[] = [
      'name' => WPFunctions::get()->__('User-agent', 'mailpoet'),
      'value' => $userAgent,
    ];

    return [
      'group_id' => 'mailpoet-newsletter-clicks',
      'group_label' => WPFunctions::get()->__('MailPoet Emails Clicks', 'mailpoet'),
      'item_id' => 'newsletter-' . $row['id'],
      'data' => $newsletterData,
    ];
  }
}
