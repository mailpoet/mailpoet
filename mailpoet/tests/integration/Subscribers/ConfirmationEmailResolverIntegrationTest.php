<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Test\DataFactories\Segment as SegmentFactory;

class ConfirmationEmailResolverIntegrationTest extends \MailPoetTest {
  /** @var ConfirmationEmailResolver */
  private $resolver;

  /** @var SegmentFactory */
  private $segmentFactory;

  public function _before() {
    parent::_before();
    $this->resolver = $this->diContainer->get(ConfirmationEmailResolver::class);
    $this->segmentFactory = new SegmentFactory();
  }

  public function testItResolvesFromPersistedSegments(): void {
    $segment1 = $this->segmentFactory->withName('List A')->create();
    $segment1->setConfirmationEmailId(42);
    $this->entityManager->flush();

    $segment2 = $this->segmentFactory->withName('List B')->create();

    [$emailId, $pageId] = $this->resolver->resolveFromSegments([$segment1, $segment2]);

    verify($emailId)->equals(42);
    verify($pageId)->null();
  }

  public function testItFallsBackOnConflict(): void {
    $segment1 = $this->segmentFactory->withName('List C')->create();
    $segment1->setConfirmationEmailId(42);

    $segment2 = $this->segmentFactory->withName('List D')->create();
    $segment2->setConfirmationEmailId(43);

    $this->entityManager->flush();

    [$emailId, $pageId] = $this->resolver->resolveFromSegments([$segment1, $segment2]);

    verify($emailId)->null();
    verify($pageId)->null();
  }
}
