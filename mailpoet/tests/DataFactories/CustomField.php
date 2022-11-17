<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class CustomField {
  /** @var array */
  private $data;

  /** @var CustomFieldsRepository  */
  private $repository;

  /** @var EntityManager  */
  private $entityManager;

  public function __construct() {
    $this->entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $this->repository = ContainerWrapper::getInstance(WP_DEBUG)->get(CustomFieldsRepository::class);
    $this->data = [
      'name' => 'Custom Field ' . bin2hex(random_bytes(7)), // phpcs:ignore
      'type' => CustomFieldEntity::TYPE_TEXT,
      'params' => [],
      'subscribers' => [],
    ];
  }

  public function withName(string $name): CustomField {
    $this->data['name'] = $name;
    return $this;
  }

  public function withSubscriber($subscriberId, string $value): CustomField {
    $this->data['subscribers'][] = ['id' => $subscriberId, 'value' => $value];
    return $this;
  }

  public function withType(string $type): CustomField {
    $this->data['type'] = $type;
    return $this;
  }

  public function create(): CustomFieldEntity {
    $customField = $this->repository->createOrUpdate($this->data);
    foreach ($this->data['subscribers'] as $subscriberData) {
      $subscriber = $this->entityManager->getReference('\MailPoet\Entities\SubscriberEntity', $subscriberData['id']);
      if (!$subscriber instanceof SubscriberEntity) {
        throw new \Exception('Subscriber failed to create');
      }
      $scfe = new SubscriberCustomFieldEntity($subscriber, $customField, $subscriberData['value']);
      $subscriber->getSubscriberCustomFields()->add($scfe);
      $this->entityManager->persist($scfe);
    }
    $this->entityManager->flush();
    return $customField;
  }
}
