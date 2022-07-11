<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;

class NewslettersExporter {

  const LIMIT = 100;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  /*** @var SubscribersRepository */
  private $subscribersRepository;

  /*** @var NewslettersRepository */
  private $newslettersRepository;

  public function __construct(
    NewsletterUrl $newsletterUrl,
    SubscribersRepository $subscribersRepository,
    NewslettersRepository $newslettersRepository
  ) {
    $this->newsletterUrl = $newsletterUrl;
    $this->subscribersRepository = $subscribersRepository;
    $this->newslettersRepository = $newslettersRepository;
  }

  public function export($email, $page = 1) {
    $data = $this->exportSubscriber($this->subscribersRepository->findOneBy(['email' => trim($email)]), $page);
    return [
      'data' => $data,
      'done' => count($data) < self::LIMIT,
    ];
  }

  private function exportSubscriber(?SubscriberEntity $subscriber, $page) {
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
        'value' => $this->newsletterUrl->getViewInBrowserUrl(
          $newsletters[$statisticsRow['newsletter_id']],
          $subscriber
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

    $newsletters = $this->newslettersRepository->findBy(['id' => $newsletterIds]);

    $result = [];
    foreach ($newsletters as $newsletter) {
      $result[$newsletter->getId()] = $newsletter;
    }
    return $result;
  }
}
