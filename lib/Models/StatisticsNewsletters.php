<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class StatisticsNewsletters extends Model {
  public static $_table = MP_STATISTICS_NEWSLETTERS_TABLE;

  static function createMultiple(array $data) {
    $values = [];
    foreach ($data as $value) {
      if (!empty($value['newsletter_id']) &&
         !empty($value['subscriber_id']) &&
         !empty($value['queue_id'])
      ) {
        $values[] = $value['newsletter_id'];
        $values[] = $value['subscriber_id'];
        $values[] = $value['queue_id'];
      }
    }
    if (!count($values)) return false;
    return self::rawExecute(
      'INSERT INTO `' . self::$_table . '` ' .
      '(newsletter_id, subscriber_id, queue_id) ' .
      'VALUES ' . rtrim(
        str_repeat('(?,?,?), ', count($values) / 3),
        ', '
      ),
      $values
    );
  }

  static function getAllForSubscriber(Subscriber $subscriber) {
    return static::tableAlias('statistics')
      ->select('statistics.newsletter_id', 'newsletter_id')
      ->select('newsletter_rendered_subject')
      ->select('opens.created_at', 'opened_at')
      ->select('sent_at')
      ->join(
        SendingQueue::$_table,
        ['statistics.queue_id', '=', 'queue.id'],
        'queue'
      )
      ->leftOuterJoin(
        StatisticsOpens::$_table,
        'statistics.newsletter_id = opens.newsletter_id AND statistics.subscriber_id = opens.subscriber_id',
        'opens'
      )
      ->where('statistics.subscriber_id', $subscriber->id())
      ->orderByAsc('newsletter_id');
  }
}
