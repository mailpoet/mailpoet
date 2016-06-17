<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Statistics {
  static function processAndLogBulkNewsletterStatistics(
    array $processed_subscribers_ids, $newsletter_id, $queue_id
  ) {
    $newsletter_statistics = array();
    foreach($processed_subscribers_ids as $subscriber_id) {
      $newsletter_statistics[] = array(
        $newsletter_id,
        $subscriber_id,
        $queue_id
      );
    }
    $newsletter_statistics = Helpers::flattenArray($newsletter_statistics);
    return self::logStatistics($newsletter_statistics);
  }

  static function logStatistics($newsletter_statistics) {
    return StatisticsNewsletters::createMultiple($newsletter_statistics);
  }
}