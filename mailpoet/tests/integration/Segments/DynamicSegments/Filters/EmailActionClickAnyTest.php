<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;

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
    $this->cleanData();
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

    $data = $this->getSegmentFilterData(EmailActionClickAny::TYPE);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($data, $this->emailAction);
    expect($emails)->count(1);
    expect($emails[0])->equals('opened_clicked@example.com');
  }

  private function getSegmentFilterData(string $action, int $newsletterId = null, int $linkId = null): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_EMAIL, $action, [
      'newsletter_id' => $newsletterId,
      'link_id' => $linkId,
    ]);
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

  public function _after(): void {
    $this->cleanData();
  }

  private function cleanData(): void {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(StatisticsOpenEntity::class);
    $this->truncateEntity(StatisticsClickEntity::class);
    $this->truncateEntity(StatisticsNewsletterEntity::class);
    $this->truncateEntity(NewsletterLinkEntity::class);
    $this->truncateEntity(UserAgentEntity::class);
  }
}
