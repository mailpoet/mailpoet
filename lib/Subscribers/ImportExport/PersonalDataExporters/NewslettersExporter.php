<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\Newsletter;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Url;
use MailPoet\WP\Functions as WPFunctions;

class NewslettersExporter {

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

    $statistics = StatisticsNewsletters::getAllForSubscriber($subscriber)
      ->limit(self::LIMIT)
      ->offset(self::LIMIT * ($page - 1))
      ->findArray();

    $newsletters = $this->loadNewsletters($statistics);

    foreach ($statistics as $row) {
      $result[] = $this->exportNewsletter($row, $newsletters, $subscriber);
    }

    return $result;
  }

  private function exportNewsletter($statisticsRow, $newsletters, $subscriber) {
    $newsletterData = [];
    $newsletterData[] = [
      'name' => WPFunctions::get()->__('Email subject', 'mailpoet'),
      'value' => $statisticsRow['newsletter_rendered_subject'],
    ];
    $newsletterData[] = [
      'name' => WPFunctions::get()->__('Sent at', 'mailpoet'),
      'value' => $statisticsRow['sent_at'],
    ];
    if (isset($statisticsRow['opened_at'])) {
      $newsletterData[] = [
        'name' => WPFunctions::get()->__('Opened', 'mailpoet'),
        'value' => 'Yes',
      ];
      $newsletterData[] = [
        'name' => WPFunctions::get()->__('Opened at', 'mailpoet'),
        'value' => $statisticsRow['opened_at'],
      ];
    } else {
      $newsletterData[] = [
        'name' => WPFunctions::get()->__('Opened', 'mailpoet'),
        'value' => WPFunctions::get()->__('No', 'mailpoet'),
      ];
    }
    if (isset($newsletters[$statisticsRow['newsletter_id']])) {
      $newsletterData[] = [
        'name' => WPFunctions::get()->__('Email preview', 'mailpoet'),
        'value' => Url::getViewInBrowserUrl(
          $newsletters[$statisticsRow['newsletter_id']],
          $subscriber,
          false
        ),
      ];
    }
    return [
      'group_id' => 'mailpoet-newsletters',
      'group_label' => WPFunctions::get()->__('MailPoet Emails Sent', 'mailpoet'),
      'item_id' => 'newsletter-' . $statisticsRow['newsletter_id'],
      'data' => $newsletterData,
    ];
  }

  private function loadNewsletters($statistics) {
    $newsletterIds = array_map(function ($statisticsRow) {
      return $statisticsRow['newsletter_id'];
    }, $statistics);

    if (empty($newsletterIds)) return [];

    $newsletters = Newsletter::whereIn('id', $newsletterIds)->findMany();

    $result = [];
    foreach ($newsletters as $newsletter) {
      $result[$newsletter->id()] = $newsletter;
    }
    return $result;
  }

}
