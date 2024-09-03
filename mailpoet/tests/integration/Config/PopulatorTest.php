<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use MailPoet\Config\Populator;
use MailPoet\Entities\NewsletterTemplateEntity;
use MailPoetTest;
use MailPoetVendor\Doctrine\ORM\Query;

class PopulatorTest extends MailPoetTest {
  private const TEMPLATE_COUNT = 76;

  public function testItInsertsTemplates(): void {
    $populator = $this->diContainer->get(Populator::class);

    // no templates
    $templates = $this->getAllTemplates();
    $this->assertSame(0, count($templates));

    // insert all templates
    $populator->up();
    $templates = $this->getAllTemplates();
    $this->assertSame(self::TEMPLATE_COUNT, count($templates));

    // delete some templates
    $this->entityManager->createQueryBuilder()
      ->delete(NewsletterTemplateEntity::class, 't')
      ->where('t.id IN (:ids)')
      ->setParameter('ids', array_map(fn($template) => $template->getId(), array_slice($templates, 0, 10)))
      ->getQuery()
      ->execute();
    $templates = $this->getAllTemplates();
    $this->assertSame(self::TEMPLATE_COUNT - 10, count($templates));

    // insert new templates
    $populator->up();
    $templates = $this->getAllTemplates();
    $this->assertSame(self::TEMPLATE_COUNT, count($templates));
  }

  public function testItUpdatesTemplates(): void {
    $populator = $this->diContainer->get(Populator::class);

    // insert all templates
    $populator->up();
    $templates = $this->getAllTemplates();
    $this->assertSame(self::TEMPLATE_COUNT, count($templates));

    // update some templates in the database
    $templates[0]->setBody(['blocks' => ['test']]);
    $templates[1]->setCategories('["test-cat-1,test-cat-2,test-cat-3"]');
    $templates[2]->setThumbnail('test-thumbnail.jpg');
    $templates[3]->setName('Test template'); // this will cause a new template to be created
    $this->entityManager->flush();

    // update templates
    $populator->up();
    $templates = $this->getAllTemplates();
    $this->assertSame(self::TEMPLATE_COUNT + 1, count($templates));
    $this->assertNotEquals(['blocks' => ['test']], $templates[0]->getBody());
    $this->assertNotEquals('["test-cat-1,test-cat-2,test-cat-3"]', $templates[1]->getCategories());
    $this->assertNotEquals('test-thumbnail.jpg', $templates[2]->getThumbnail());
    $this->assertSame('Test template', $templates[3]->getName());
    $this->assertNotEmpty(array_filter($templates, fn($template) => $template->getName() === 'Test template'));
  }

  public function testItRemovesDuplicates(): void {
    $populator = $this->diContainer->get(Populator::class);

    // insert all templates
    $populator->up();
    $templates = $this->getAllTemplates();
    $this->assertSame(self::TEMPLATE_COUNT, count($templates));

    // create some duplicates
    $this->entityManager->persist(clone $templates[0]);
    $this->entityManager->persist(clone $templates[1]);
    $this->entityManager->persist(clone $templates[2]);
    $this->entityManager->flush();
    $templates = $this->getAllTemplates();
    $this->assertSame(self::TEMPLATE_COUNT + 3, count($templates));

    // remove duplicates
    $populator->up();
    $templates = $this->getAllTemplates();
    $this->assertSame(self::TEMPLATE_COUNT, count($templates));
  }

  private function getAllTemplates(): array {
    return (array)$this->entityManager->createQueryBuilder()
      ->select('t')
      ->from(NewsletterTemplateEntity::class, 't')
      ->orderBy('t.id')
      ->getQuery()
      ->setHint(Query::HINT_REFRESH, true)
      ->getResult();
  }
}
