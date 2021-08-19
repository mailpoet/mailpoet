<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\StatisticsClicks;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterClicksExporter extends NewsletterStatsBaseExporter {
  protected $statsClass = StatisticsClicks::class;

  protected function getEmailStats(array $row) {
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
