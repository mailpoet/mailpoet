<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\StatisticsOpens;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterOpensExporter extends NewsletterStatsBaseExporter {
  protected $statsClass = StatisticsOpens::class;

  protected function getEmailStats(array $row): array {
    $newsletterData = [];
    $newsletterData[] = [
      'name' => WPFunctions::get()->__('Email subject', 'mailpoet'),
      'value' => $row['newsletter_rendered_subject'],
    ];
    $newsletterData[] = [
      'name' => WPFunctions::get()->__('Timestamp of the open event', 'mailpoet'),
      'value' => $row['created_at'],
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
      'group_id' => 'mailpoet-newsletter-opens',
      'group_label' => WPFunctions::get()->__('MailPoet Emails Opens', 'mailpoet'),
      'item_id' => 'newsletter-' . $row['id'],
      'data' => $newsletterData,
    ];
  }
}
