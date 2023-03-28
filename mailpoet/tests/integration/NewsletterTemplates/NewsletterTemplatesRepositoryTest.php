<?php declare(strict_types = 1);

namespace MailPoet\NewsletterTemplates;

use MailPoet\Entities\NewsletterTemplateEntity;

class NewsletterTemplatesRepositoryTest extends \MailPoetTest {
  /** @var NewsletterTemplatesRepository */
  private $newsletterTemplatesRepository;

  public function _before() {
    $this->newsletterTemplatesRepository = $this->diContainer->get(NewsletterTemplatesRepository::class);
  }

  public function testItCanCreateOrUpdate() {
    $createdTemplate = $this->newsletterTemplatesRepository->createOrUpdate([
      'name' => 'Another template',
      'body' => '{"content": {}, "globalStyles": {}}',
      'thumbnail_data' => 'data:image/gif;base64,R0lGODlhAQABAAAAACw=',
    ]);
    expect($createdTemplate->getName())->equals('Another template');
    expect($createdTemplate->getBody())->equals(['content' => [], 'globalStyles' => []]);
    expect($createdTemplate->getThumbnailData())->equals('data:image/gif;base64,R0lGODlhAQABAAAAACw=');

    $updatedTemplate = $this->newsletterTemplatesRepository->createOrUpdate([
      'id' => $createdTemplate->getId(),
      'name' => 'Another template updated',
      'body' => '{"content": "changed"}',
      'thumbnail_data' => 'data:image/gif;base64,R0lGO==',
    ]);
    expect($updatedTemplate->getName())->equals('Another template updated');
    expect($updatedTemplate->getBody())->equals(['content' => 'changed']);
    expect($updatedTemplate->getThumbnailData())->equals('data:image/gif;base64,R0lGO==');
  }

  public function testItCleansRecentlySent() {
    $total = NewsletterTemplatesRepository::RECENTLY_SENT_COUNT + 5;
    for ($i = 0; $i < $total; $i++) {
      $template = new NewsletterTemplateEntity('Testing template ' . $i);
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

  public function testItCanCreateFromOldDataFormat() {
    $createdTemplate = $this->newsletterTemplatesRepository->createOrUpdate([
      'name' => 'Another template',
      'body' => '{"content": {}, "globalStyles": {}}',
      'thumbnail' => 'data:image/gif;base64,R0lGODlhAQABAAAAACw=',
    ]);
    expect($createdTemplate->getName())->equals('Another template');
    expect($createdTemplate->getBody())->equals(['content' => [], 'globalStyles' => []]);
    expect($createdTemplate->getThumbnailData())->equals('data:image/gif;base64,R0lGODlhAQABAAAAACw=');
  }
}
