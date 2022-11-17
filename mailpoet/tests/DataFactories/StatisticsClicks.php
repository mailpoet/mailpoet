<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Assert;

class StatisticsClicks {
  protected $data;

  /** @var NewsletterLinkEntity */
  private $newsletterLink;

  /** @var SubscriberEntity */
  private $subscriber;

  public function __construct(
    NewsletterLinkEntity $newsletterLink,
    SubscriberEntity $subscriber
  ) {
    $this->data = [
      'count' => 1,
    ];
    $this->newsletterLink = $newsletterLink;
    $this->subscriber = $subscriber;
  }

  public function withCount($count) {
    $this->data['count'] = $count;
    return $this;
  }

  public function create(): StatisticsClickEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $newsletter = $this->newsletterLink->getNewsletter();
    Assert::assertInstanceOf(NewsletterEntity::class, $newsletter);
    $queue = $newsletter->getLatestQueue();
    Assert::assertInstanceOf(SendingQueueEntity::class, $queue);
    $entity = new StatisticsClickEntity(
      $newsletter,
      $queue,
      $this->subscriber,
      $this->newsletterLink,
      $this->data['count']
    );
    $entityManager->persist($entity);
    $entityManager->flush();
    return $entity;
  }
}
