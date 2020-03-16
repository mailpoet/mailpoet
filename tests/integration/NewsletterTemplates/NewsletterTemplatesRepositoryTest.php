<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\Entities\NewsletterTemplateEntity;
use MailPoet\NewsletterTemplates\NewsletterTemplatesRepository;

class NewsletterTemplatesRepositoryTest extends \MailPoetTest {
  /** @var NewsletterTemplatesRepository */
  private $newsletterTemplatesRepository;

  public function _before() {
    $this->truncateEntity(NewsletterTemplateEntity::class);
    $this->newsletterTemplatesRepository = $this->diContainer->get(NewsletterTemplatesRepository::class);
  }

  public function testItCanCreateOrUpdate() {
    $createdTemplate = $this->newsletterTemplatesRepository->createOrUpdate([
      'name' => 'Another template',
      'body' => '{"content": {}, "globalStyles": {}}',
    ]);
    expect($createdTemplate->getName())->equals('Another template');
    expect($createdTemplate->getBody())->equals(['content' => [], 'globalStyles' => []]);

    $updatedTemplate = $this->newsletterTemplatesRepository->createOrUpdate([
      'id' => $createdTemplate->getId(),
      'name' => 'Another template updated',
      'body' => '{"content": "changed"}',
    ]);
    expect($updatedTemplate->getName())->equals('Another template updated');
    expect($updatedTemplate->getBody())->equals(['content' => 'changed']);
  }

  public function testItCleansRecentlySent() {
    $total = NewsletterTemplatesRepository::RECENTLY_SENT_COUNT + 5;
    for ($i = 0; $i < $total; $i++) {
      $template = new NewsletterTemplateEntity();
      $template->setName('Testing template ' . $i);
      $template->setBody(['key' => 'value']);
      $template->setCategories(NewsletterTemplatesRepository::RECENTLY_SENT_CATEGORIES);
      $this->entityManager->persist($template);
    }
    $this->newsletterTemplatesRepository->flush();

    $this->newsletterTemplatesRepository->cleanRecentlySent();

    $templates = $this->newsletterTemplatesRepository->findBy(
      ['categories' => NewsletterTemplatesRepository::RECENTLY_SENT_CATEGORIES],
      ['id' => 'ASC']
    );
    expect(count($templates))->equals(NewsletterTemplatesRepository::RECENTLY_SENT_COUNT);
    expect($templates[0]->getName())->equals('Testing template 5');
  }

  public function _after() {
    $this->truncateEntity(NewsletterTemplateEntity::class);
  }
}
