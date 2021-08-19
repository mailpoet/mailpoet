<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\Subscriber;

abstract class NewsletterStatsBaseExporter {

  const LIMIT = 100;

  protected $statsClass;

  public function export($email, $page = 1) {
    $data = $this->getSubscriberData(Subscriber::findOne(trim($email)), $page);
    return [
      'data' => $data,
      'done' => count($data) < self::LIMIT,
    ];
  }

  private function getSubscriberData($subscriber, $page) {
    if (!$subscriber) {
      return [];
    }

    $result = [];

    $statistics = $this->statsClass::getAllForSubscriber($subscriber)
      ->limit(self::LIMIT)
      ->offset(self::LIMIT * ($page - 1))
      ->findArray();

    foreach ($statistics as $row) {
      $result[] = $this->getEmailStats($row);
    }

    return $result;
  }

  protected abstract function getEmailStats(array $row);
}
