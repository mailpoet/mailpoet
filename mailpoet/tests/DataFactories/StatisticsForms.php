<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\StatisticsFormEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class StatisticsForms {
  protected $data;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var FormEntity */
  private $form;

  public function __construct(
    FormEntity $form,
    SubscriberEntity $subscriber
  ) {
    $this->form = $form;
    $this->subscriber = $subscriber;
  }

  public function create(): StatisticsFormEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $entity = new StatisticsFormEntity($this->form, $this->subscriber);
    $entityManager->persist($entity);
    $entityManager->flush();
    return $entity;
  }
}
