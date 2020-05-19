<?php declare(strict_types=1);

namespace MailPoet\Entities;

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
    $segment = new SegmentEntity();
    $segment->setType(SegmentEntity::TYPE_DEFAULT);
    $segment->setName('Segment');
    $segment->setDescription('Segment description');
    $this->entityManager->persist($segment);
    return $segment;
  }

  private function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterSegmentEntity::class);
    $this->truncateEntity(SegmentEntity::class);
  }
}
