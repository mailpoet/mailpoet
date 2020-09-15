<?php declare(strict_types=1);

namespace MailPoet\Entities;

use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;

class NewsletterEntityTest extends \MailPoetTest {
  public function _before() {
    $this->cleanup();
  }

  public function testItRemovesOrphanedSegmentRelations() {
    $newsletter = $this->createNewsletter();
    $segment = $this->createSegment();
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
    $optionField = $this->createOptionField();
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

  private function createSegment(): SegmentEntity {
    $segment = new SegmentEntity('Segment', SegmentEntity::TYPE_DEFAULT, 'Segment description');
    $this->entityManager->persist($segment);
    return $segment;
  }

  private function createOptionField(): NewsletterOptionFieldEntity {
    $newsletterOptionField = new NewsletterOptionFieldEntity();
    $newsletterOptionField->setName('Option');
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
