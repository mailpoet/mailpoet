<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;

class NewsletterOption {
  /** @var NewsletterOptionFieldsRepository */
  private $newsletterOptionFieldsRepository;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  public function __construct() {
    $this->newsletterOptionFieldsRepository = ContainerWrapper::getInstance()->get(NewsletterOptionFieldsRepository::class);
    $this->newsletterOptionsRepository = ContainerWrapper::getInstance()->get(NewsletterOptionsRepository::class);
  }

  public function createMultipleOptions(NewsletterEntity $newsletter, array $options): void {
    foreach ($options as $optionField => $optionValue) {
      $this->create($newsletter, $optionField, $optionValue);
    }
  }

  /**
   * @param string|int $optionValue
   */
  public function create(NewsletterEntity $newsletter, string $optionName, $optionValue): NewsletterOptionEntity {
    $newsletterOptionField = $this->newsletterOptionFieldsRepository->findOneBy([
      'name' => $optionName,
      'newsletterType' => $newsletter->getType(),
    ]);
    if (!$newsletterOptionField) {
      $newsletterOptionField = new NewsletterOptionFieldEntity();
      $newsletterOptionField->setNewsletterType($newsletter->getType());
      $newsletterOptionField->setName($optionName);
      $this->newsletterOptionFieldsRepository->persist($newsletterOptionField);
    }

    $newsletterOption = $this->newsletterOptionsRepository->findOneBy([
      'newsletter' => $newsletter,
      'optionField' => $newsletterOptionField,
    ]);
    if (!$newsletterOption instanceof NewsletterOptionEntity) {
      $newsletterOption = new NewsletterOptionEntity($newsletter, $newsletterOptionField);
      $newsletter->getOptions()->add($newsletterOption);
      $this->newsletterOptionsRepository->persist($newsletterOption);
    }
    $newsletterOption->setValue((string)$optionValue);
    $this->newsletterOptionsRepository->flush();

    return $newsletterOption;
  }
}
