<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Assert;

class NewsletterLink {
  protected $data;

  /** @var NewsletterEntity */
  private $newsletter;

  public function __construct(
    NewsletterEntity $newsletter
  ) {
    $this->data = [
      'url' => 'https://example.com/test',
      'hash' => 'hash',
    ];
    $this->newsletter = $newsletter;
  }

  public function withUrl($url) {
    $this->data['url'] = $url;
    return $this;
  }

  public function withHash($hash) {
    $this->data['hash'] = $hash;
    return $this;
  }

  /**
   * @param string $createdAt in format Y-m-d H:i:s
   * @return NewsletterLink
   */
  public function withCreatedAt($createdAt) {
    $this->data['created_at'] = $createdAt;
    return $this;
  }

  public function create(): NewsletterLinkEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $queue = $this->newsletter->getLatestQueue();
    Assert::assertInstanceOf(SendingQueueEntity::class, $queue);
    $entity = new NewsletterLinkEntity(
      $this->newsletter,
      $queue,
      $this->data['url'],
      $this->data['hash']
    );
    $entityManager->persist($entity);
    $entityManager->flush();
    return $entity;
  }
}
