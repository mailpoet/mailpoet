<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class NewsletterPost {
  protected $data;

  /** @var NewsletterEntity */
  private $newsletter;

  public function __construct(
    NewsletterEntity $newsletter
  ) {
    $this->data = [
      'post_id' => 1,
    ];
    $this->newsletter = $newsletter;
  }

  public function withPostId($id) {
    $this->data['post_id'] = $id;
    return $this;
  }

  /**
   * @param string $createdAt in format Y-m-d H:i:s
   * @return self
   */
  public function withCreatedAt($createdAt) {
    $this->data['created_at'] = $createdAt;
    return $this;
  }

  public function create(): NewsletterPostEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $entity = new NewsletterPostEntity(
      $this->newsletter,
      $this->data['post_id'],
    );
    $entityManager->persist($entity);
    $entityManager->flush();
    return $entity;
  }
}
