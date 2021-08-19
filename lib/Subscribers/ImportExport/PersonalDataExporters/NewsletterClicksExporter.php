<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Statistics\StatisticsClicksRepository;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterClicksExporter extends NewsletterStatsBaseExporter {
  protected $statsClassName = StatisticsClicksRepository::class;

  protected function getEmailStats(array $row) {
    $newsletterData = [];
    $newsletterData[] = [
      'name' => WPFunctions::get()->__('Email subject', 'mailpoet'),
      'value' => $row['newsletterRenderedSubject'],
    ];
    $newsletterData[] = [
      'name' => WPFunctions::get()->__('Timestamp of the click event', 'mailpoet'),
      'value' => $row['createdAt']->format("Y-m-d H:i:s"),
    ];
    $newsletterData[] = [
      'name' => WPFunctions::get()->__('URL', 'mailpoet'),
      'value' => $row['url'],
    ];

    if (!is_null($row['userAgent'])) {
      $userAgent = $row['userAgent'];
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
