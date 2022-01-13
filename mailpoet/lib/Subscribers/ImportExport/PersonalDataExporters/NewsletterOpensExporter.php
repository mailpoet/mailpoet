<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Statistics\StatisticsOpensRepository;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterOpensExporter extends NewsletterStatsBaseExporter {
  protected $statsClassName = StatisticsOpensRepository::class;

  protected function getEmailStats(array $row): array {
    $newsletterData = [];
    $newsletterData[] = [
      'name' => WPFunctions::get()->__('Email subject', 'mailpoet'),
      'value' => $row['newsletterRenderedSubject'],
    ];
    $newsletterData[] = [
      'name' => WPFunctions::get()->__('Timestamp of the open event', 'mailpoet'),
      'value' => $row['createdAt']->format("Y-m-d H:i:s"),
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
      'group_id' => 'mailpoet-newsletter-opens',
      'group_label' => WPFunctions::get()->__('MailPoet Emails Opens', 'mailpoet'),
      'item_id' => 'newsletter-' . $row['id'],
      'data' => $newsletterData,
    ];
  }
}
