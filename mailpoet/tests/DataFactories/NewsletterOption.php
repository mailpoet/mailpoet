<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class NewsletterOption {
  public function createMultipleOptions(NewsletterEntity $newsletter, array $options) {
    foreach ($options as $optionField => $optionValue) {
      $this->create($newsletter, $optionField, $optionValue);
    }
  }

  public function create(NewsletterEntity $newsletter, string $optionField, string $optionValue): NewsletterOptionEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $newsletterOptionFieldRepository = ContainerWrapper::getInstance()->get(NewsletterOptionFieldsRepository::class);
    $newsletterOptionRepository = ContainerWrapper::getInstance()->get(NewsletterOptionsRepository::class);

    $newsletterOptionField = $newsletterOptionFieldRepository->findOneBy(['name' => $optionField]);
    if (!$newsletterOptionField instanceof NewsletterOptionFieldEntity) {
      $newsletterOptionField = new NewsletterOptionFieldEntity();
      $newsletterOptionField->setName($optionField);
      $newsletterOptionField->setNewsletterType($newsletter->getType());
      $entityManager->persist($newsletterOptionField);
    }

    $newsletterOption = $newsletterOptionRepository->findOneBy(['newsletter' => $newsletter, 'optionField' => $newsletterOptionField]);
    if (!$newsletterOption instanceof NewsletterOptionEntity) {
      $newsletterOption = new NewsletterOptionEntity($newsletter, $newsletterOptionField);
      $newsletter->getOptions()->add($newsletterOption);
      $entityManager->persist($newsletterOption);
    }
    $newsletterOption->setValue($optionValue);
    $entityManager->flush();

    return $newsletterOption;
  }
}
