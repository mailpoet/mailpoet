<?php

namespace MailPoet\Models;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\UserAgentEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * @property int $newsletterId
 * @property int $subscriberId
 * @property int $queueId
 */
class StatisticsOpens extends Model {
  public static $_table = MP_STATISTICS_OPENS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public static function getOrCreate($subscriberId, $newsletterId, $queueId) {
    $statistics = self::where('subscriber_id', $subscriberId)
      ->where('newsletter_id', $newsletterId)
      ->where('queue_id', $queueId)
      ->findOne();
    if (!$statistics) {
      $statistics = self::create();
      $statistics->subscriberId = $subscriberId;
      $statistics->newsletterId = $newsletterId;
      $statistics->queueId = $queueId;
      $statistics->save();
    }
    return $statistics;
  }

  public static function getAllForSubscriber(Subscriber $subscriber) {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $userAgentsTable = $entityManager->getClassMetadata(UserAgentEntity::class)->getTableName();

    return static::tableAlias('opens')
      ->select('opens.id', 'id')
      ->select('newsletter_rendered_subject')
      ->select('opens.created_at', 'created_at')
      ->select('user_agent.user_agent')
      ->join(
        SendingQueue::$_table,
        ['opens.queue_id', '=', 'queue.id'],
        'queue'
      )
      ->leftOuterJoin(
        $userAgentsTable,
        ['opens.user_agent_id', '=', 'user_agent.id'],
        'user_agent'
      )
      ->where('opens.subscriber_id', $subscriber->id())
      ->orderByAsc('newsletter_rendered_subject');
  }
}
