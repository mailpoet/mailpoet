<?php

namespace MailPoet\Models;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * @property string|null $sentAt
 */
class StatisticsNewsletters extends Model {
  public static $_table = MP_STATISTICS_NEWSLETTERS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public static function createMultiple(array $data) {
    $values = [];
    foreach ($data as $value) {
      if (
        !empty($value['newsletter_id']) &&
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
        str_repeat('(?,?,?), ', (int)(count($values) / 3)),
        ', '
      ),
      $values
    );
  }

  public static function getAllForSubscriber(SubscriberEntity $subscriber) {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);

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
        $entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName(),
        'statistics.newsletter_id = opens.newsletter_id AND statistics.subscriber_id = opens.subscriber_id',
        'opens'
      )
      ->where('statistics.subscriber_id', $subscriber->getId())
      ->orderByAsc('newsletter_id');
  }
}
