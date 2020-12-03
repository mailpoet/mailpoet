<?php declare(strict_types=1);

namespace MailPoet\Entities;

use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Segments\SegmentsRepository;

class NewsletterEntityTest extends \MailPoetTest {
  
  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var SegmentsRepository */
  private $segmentRepository;

  public function _before() {
    $this->cleanup();
    $this->newsletterRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
  }

  public function testItRemovesOrphanedSegmentRelations() {
    $newsletter = $this->createNewsletter();
    $segment = $this->segmentRepository->createOrUpdate('Segment', 'Segment description');
    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
    $this->entityManager->persist($newsletterSegment);
    $this->entityManager->flush();

    $this->entityManager->refresh($newsletter);
    expect($newsletter->getNewsletterSegments()->count())->same(1);

    $newsletter->getNewsletterSegments()->removeElement($newsletterSegment);
    $this->entityManager->flush();
    expect($newsletter->getNewsletterSegments()->count())->same(0);

    $newsletterSegments = $this->diContainer->get(NewsletterSegmentRepository::class)->findBy(['newsletter' => $newsletter]);
    expect($newsletterSegments)->count(0);
  }

  public function testItRemovesOrphanedOptionRelations() {
    $newsletter = $this->createNewsletter();
    $optionField = $this->createOptionField(NewsletterOptionFieldEntity::NAME_GROUP);
    $newsletterOption = new NewsletterOptionEntity($newsletter, $optionField);
    $this->entityManager->persist($newsletterOption);
    $this->entityManager->flush();

    $this->entityManager->refresh($newsletter);
    expect($newsletter->getOptions()->count())->same(1);

    $newsletter->getOptions()->removeElement($newsletterOption);
    $this->entityManager->flush();
    expect($newsletter->getOptions()->count())->same(0);

    $newsletterSegments = $this->diContainer->get(NewsletterOptionsRepository::class)->findBy(['newsletter' => $newsletter]);
    expect($newsletterSegments)->count(0);
  }

  public function testGetOptionReturnsCorrectData(): void {
    $optionValue = 'Some Value';
    $newsletter = $this->createNewsletter();
    $optionField = $this->createOptionField(NewsletterOptionFieldEntity::NAME_EVENT);
    $newsletterOption = new NewsletterOptionEntity($newsletter, $optionField);
    $newsletterOption->setValue($optionValue);
    
    $this->entityManager->persist($newsletterOption);
    $this->entityManager->flush();
    $this->entityManager->clear();
    $newsletterId = $newsletter->getId();

    $newsletter = $this->newsletterRepository->findOneById($newsletterId);
    assert($newsletter instanceof NewsletterEntity);
    $newsletterOptionField = $newsletter->getOption($optionField->getName());
    assert($newsletterOption instanceof NewsletterOptionEntity);

    expect($newsletterOptionField)->notNull();
    expect($newsletterOption->getValue())->equals($optionValue);
    expect($newsletter->getOption(NewsletterOptionFieldEntity::NAME_SEGMENT))->null();
  }

  public function _after() {
    $this->cleanup();
  }

  private function createNewsletter(): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Subject');
    $this->entityManager->persist($newsletter);
    return $newsletter;
  }

  private function createOptionField(string $name): NewsletterOptionFieldEntity {
    $newsletterOptionField = new NewsletterOptionFieldEntity();
    $newsletterOptionField->setName($name);
    $newsletterOptionField->setNewsletterType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($newsletterOptionField);
    return $newsletterOptionField;
  }

  private function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(NewsletterSegmentEntity::class);
    $this->truncateEntity(SegmentEntity::class);
  }
}
