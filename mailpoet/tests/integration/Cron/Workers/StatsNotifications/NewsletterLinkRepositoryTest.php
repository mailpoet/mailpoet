<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Tasks\Sending as SendingTask;

class NewsletterLinkRepositoryTest extends \MailPoetTest {
  public function testItFetchesTopLink() {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('My Standard Newsletter');
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->persist($newsletter);

    $task = new ScheduledTaskEntity();
    $task->setType(SendingTask::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $newsletter->getQueues()->add($queue);

    $link1 = new NewsletterLinkEntity($newsletter, $queue, 'http://example1.com', 'abcd');
    $link2 = new NewsletterLinkEntity($newsletter, $queue, 'http://example2.com', 'efgh');
    $this->entityManager->persist($link1);
    $this->entityManager->persist($link2);

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail("sub{$newsletter->getId()}@mailpoet.com");
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriber);

    $click1 = new StatisticsClickEntity($newsletter, $queue, $subscriber, $link1, 1);
    $click2 = new StatisticsClickEntity($newsletter, $queue, $subscriber, $link1, 1);
    $click3 = new StatisticsClickEntity($newsletter, $queue, $subscriber, $link2, 1);

    $this->entityManager->persist($click1);
    $this->entityManager->persist($click2);
    $this->entityManager->persist($click3);
    $this->entityManager->flush();

    $repository = $this->diContainer->get(NewsletterLinkRepository::class);
    $topLink = $repository->findTopLinkForNewsletter((int)$newsletter->getId());
    $this->assertInstanceOf(NewsletterLinkEntity::class, $topLink);
    expect($topLink->getUrl())->equals('http://example1.com');

    $newsletter2 = new NewsletterEntity();
    $newsletter2->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter2->setSubject('My Standard Newsletter');
    $newsletter2->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->persist($newsletter2);
    $this->entityManager->flush();

    $nonExistingTopLink = $repository->findTopLinkForNewsletter((int)$newsletter2->getId());
    expect($nonExistingTopLink)->null();
  }
}
