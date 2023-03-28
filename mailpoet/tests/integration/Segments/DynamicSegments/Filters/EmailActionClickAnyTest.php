<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class EmailActionClickAnyTest extends \MailPoetTest {
  /** @var EmailActionClickAny */
  private $emailAction;

  /** @var NewsletterEntity */
  private $newsletter;
  /** @var SubscriberEntity */
  public $subscriberOpenedNotClicked;
  /** @var SubscriberEntity */
  public $subscriberNotSent;
  /** @var SubscriberEntity */
  public $subscriberNotOpened;
  /** @var SubscriberEntity */
  public $subscriberOpenedClicked;

  public function _before(): void {
    $this->emailAction = $this->diContainer->get(EmailActionClickAny::class);
    $this->newsletter = new NewsletterEntity();
    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    $queue = new SendingQueueEntity();
    $queue->setNewsletter($this->newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $this->newsletter->getQueues()->add($queue);
    $this->newsletter->setSubject('newsletter 1');
    $this->newsletter->setStatus('sent');
    $this->newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($this->newsletter);
    $this->entityManager->flush();

    $this->subscriberOpenedClicked = $this->createSubscriber('opened_clicked@example.com');
    $this->subscriberOpenedNotClicked = $this->createSubscriber('opened_not_clicked@example.com');
    $this->subscriberNotOpened = $this->createSubscriber('not_opened@example.com');
    $this->subscriberNotSent = $this->createSubscriber('not_sent@example.com');

    $this->createStatsNewsletter($this->subscriberOpenedClicked);
    $this->createStatsNewsletter($this->subscriberOpenedNotClicked);
    $this->createStatsNewsletter($this->subscriberNotOpened);

    $this->createStatisticsOpens($this->subscriberOpenedClicked);
    $this->createStatisticsOpens($this->subscriberOpenedNotClicked);

    $this->addClickedToLink('http://example.com', $this->newsletter, $this->subscriberOpenedClicked);
  }

  public function testGetClickedAnyLink(): void {
    $subscriberClickedExcludedLinks = $this->createSubscriber('opened_clicked_excluded@example.com');
    $this->createStatsNewsletter($subscriberClickedExcludedLinks);
    $this->createStatisticsOpens($subscriberClickedExcludedLinks);
    $this->addClickedToLink('[link:subscription_unsubscribe_url]', $this->newsletter, $subscriberClickedExcludedLinks);
    $this->addClickedToLink('[link:subscription_instant_unsubscribe_url]', $this->newsletter, $subscriberClickedExcludedLinks);
    $this->addClickedToLink('[link:newsletter_view_in_browser_url]', $this->newsletter, $subscriberClickedExcludedLinks);
    $this->addClickedToLink('[link:subscription_manage_url]', $this->newsletter, $subscriberClickedExcludedLinks);

    $segmentFilter = $this->getSegmentFilter(EmailActionClickAny::TYPE);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $this->assertIsArray($result[0]);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
  }

  private function getQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id")
      ->from($subscribersTable);
  }

  private function getSegmentFilter(string $action, int $newsletterId = null, int $linkId = null): DynamicSegmentFilterEntity {
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_EMAIL, $action, [
      'newsletter_id' => $newsletterId,
      'link_id' => $linkId,
    ]);
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $segmentFilterData);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
  }

  private function createSubscriber(string $email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setLastName('Last');
    $subscriber->setFirstName('First');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createStatsNewsletter(SubscriberEntity $subscriber): StatisticsNewsletterEntity {
    $queue = $this->newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $stats = new StatisticsNewsletterEntity($this->newsletter, $queue, $subscriber);
    $this->entityManager->persist($stats);
    $this->entityManager->flush();
    return $stats;
  }

  private function createStatisticsOpens(SubscriberEntity $subscriber): StatisticsOpenEntity {
    $queue = $this->newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $open = new StatisticsOpenEntity($this->newsletter, $queue, $subscriber);
    $this->entityManager->persist($open);
    $this->entityManager->flush();
    return $open;
  }

  private function addClickedToLink(string $link, NewsletterEntity $newsletter, SubscriberEntity $subscriber): void {
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $link = new NewsletterLinkEntity($this->newsletter, $queue, $link, uniqid());
    $this->entityManager->persist($link);
    $this->entityManager->flush();
    $click = new StatisticsClickEntity(
      $newsletter,
      $queue,
      $subscriber,
      $link,
      1
    );
    $this->entityManager->persist($click);
    $this->entityManager->flush();
  }
}
