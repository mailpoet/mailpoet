<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;

class NewsletterOptionField {
  /** @var NewsletterOptionFieldsRepository */
  private $newsletterOptionFieldsRepository;

  public function __construct() {
    $this->newsletterOptionFieldsRepository = ContainerWrapper::getInstance()->get(NewsletterOptionFieldsRepository::class);
  }

  public function findOrCreate(string $name, string $newsletterType = NewsletterEntity::TYPE_STANDARD): NewsletterOptionFieldEntity {
    $newsletterOptionField = $this->newsletterOptionFieldsRepository->findOneBy([
      'name' => $name,
      'newsletterType' => $newsletterType,
    ]);
    if (!$newsletterOptionField) {
      $newsletterOptionField = new NewsletterOptionFieldEntity();
      $newsletterOptionField->setNewsletterType($newsletterType);
      $newsletterOptionField->setName($name);
      $this->newsletterOptionFieldsRepository->persist($newsletterOptionField);
    }

    return $newsletterOptionField;
  }
}
