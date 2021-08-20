<?php declare(strict_types=1);

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;

/**
 * @extends Repository<StatisticsClickEntity>
 */
class StatisticsClicksRepository extends Repository {
  protected function getEntityClassName(): string {
    return StatisticsClickEntity::class;
  }

  public function createOrUpdateClickCount(
    NewsletterLinkEntity $link,
    SubscriberEntity $subscriber,
    NewsletterEntity $newsletter,
    SendingQueueEntity $queue,
    ?UserAgentEntity $userAgent
  ): StatisticsClickEntity {
    $statistics = $this->findOneBy([
      'link' => $link,
      'newsletter' => $newsletter,
      'subscriber' => $subscriber,
      'queue' => $queue,
    ]);
    if (!$statistics instanceof StatisticsClickEntity) {
      $statistics = new StatisticsClickEntity($newsletter, $queue, $subscriber, $link, 1);
      if ($userAgent) {
        $statistics->setUserAgent($userAgent);
        $statistics->setUserAgentType($userAgent->getUserAgentType());
      }
      $this->persist($statistics);
    } else {
      $statistics->setCount($statistics->getCount() + 1);
    }
    return $statistics;
  }
}
